<?php
namespace app\services;

use app\core\Database;

/**
 * The read model behind /account/portfolio.
 *
 * Two rules run through all of it:
 *
 *   1. A figure is either derived from a stored record or it isn't shown. There
 *      is no NAV history table, so there is no value-over-time chart; there is
 *      no distributions table, so nothing here claims money has been paid out.
 *   2. Where a number can only be partly known - profit and loss on holdings
 *      settled before cost basis was recorded - the unknown part is reported as
 *      unknown rather than averaged away.
 */
class PortfolioService
{
    /** Shareholders' share of net operating profit. Mirrors the fees page. */
    private const DISTRIBUTABLE_SHARE = 0.60;

    /**
     * One row per company, aggregating the append-only ledger.
     *
     * An investor can have several shareholding rows for the same company -
     * separate settlements, or a correction posted as an offsetting entry - and
     * the portfolio should show one line per company, not one per ledger entry.
     */
    public static function holdings(int $userId): array
    {
        $latestActivity = "(SELECT ca.company_id, ca.activity_type_id, ca.location
                            FROM commercial_activities ca
                            WHERE ca.id = (SELECT MAX(x.id) FROM commercial_activities x
                                           WHERE x.company_id = ca.company_id))";

        $firstAsset = "(SELECT a.company_id, a.make, a.model
                        FROM assets a
                        WHERE a.id = (SELECT MIN(x.id) FROM assets x WHERE x.company_id = a.company_id))";

        $sql = "SELECT c.id AS company_id, c.reference, c.name, c.nav_per_share, c.shares_issued,
                       SUM(s.shares) AS shares,
                       MIN(s.settled_at) AS first_settled,
                       -- Cost is only meaningful when every parcel has one. A
                       -- partial sum would understate what was paid and overstate
                       -- the gain, which is the wrong direction to be wrong in.
                       SUM(CASE WHEN s.nav_at_settlement IS NULL THEN 1 ELSE 0 END) AS parcels_without_cost,
                       COALESCE(SUM(s.shares * s.nav_at_settlement), 0) AS cost_basis,
                       fa.make, fa.model,
                       att.name AS activity_name,
                       sec.name AS sector_name, sec.slug AS sector_slug,
                       COALESCE(sec.icon, 'ti-car') AS sector_icon,
                       la.location
                FROM shareholdings s
                JOIN companies c ON c.id = s.company_id
                LEFT JOIN {$firstAsset} fa ON fa.company_id = c.id
                LEFT JOIN {$latestActivity} la ON la.company_id = c.id
                LEFT JOIN activity_types att ON att.id = la.activity_type_id
                LEFT JOIN sectors sec ON sec.id = att.sector_id
                WHERE s.user_id = :user_id
                GROUP BY c.id, c.reference, c.name, c.nav_per_share, c.shares_issued,
                         fa.make, fa.model, att.name, sec.name, sec.slug, sec.icon, la.location
                HAVING SUM(s.shares) > 0
                ORDER BY SUM(s.shares) * c.nav_per_share DESC";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $shares = (int) $row['shares'];
            $row['shares'] = $shares;
            $row['value'] = $shares * (float) $row['nav_per_share'];
            $row['cost_known'] = (int) $row['parcels_without_cost'] === 0;
            $row['cost_basis'] = $row['cost_known'] ? (float) $row['cost_basis'] : null;

            if ($row['cost_known'] && $row['cost_basis'] > 0) {
                $row['movement'] = $row['value'] - $row['cost_basis'];
                $row['movement_pct'] = ($row['movement'] / $row['cost_basis']) * 100;
                $row['average_cost'] = $row['cost_basis'] / max(1, $shares);
            } else {
                $row['movement'] = null;
                $row['movement_pct'] = null;
                $row['average_cost'] = null;
            }

            // What proportion of the whole company this investor owns. Needed
            // for the income estimate below and worth showing on its own.
            $row['ownership_pct'] = (int) $row['shares_issued'] > 0
                ? ($shares / (int) $row['shares_issued']) * 100
                : 0.0;
        }
        unset($row);

        return $rows;
    }

    /**
     * Portfolio totals.
     *
     * `cost_known_value` is the slice of the portfolio that has a cost basis, so
     * the page can say "movement on R4,200 of your R6,800" instead of implying
     * the percentage covers everything.
     */
    public static function totals(array $holdings): array
    {
        $value = 0.0;
        $cost = 0.0;
        $costKnownValue = 0.0;
        $unknownCount = 0;

        foreach ($holdings as $holding) {
            $value += $holding['value'];

            if ($holding['cost_known'] && $holding['cost_basis'] > 0) {
                $cost += $holding['cost_basis'];
                $costKnownValue += $holding['value'];
            } else {
                $unknownCount++;
            }
        }

        $movement = $cost > 0 ? $costKnownValue - $cost : null;

        return [
            'value'             => $value,
            'cost'              => $cost,
            'cost_known_value'  => $costKnownValue,
            'movement'          => $movement,
            'movement_pct'      => ($cost > 0) ? ($movement / $cost) * 100 : null,
            'holdings'          => count($holdings),
            'unknown_cost'      => $unknownCount,
            // True only when every holding has a cost basis, which is what lets
            // the headline percentage stand without a qualifier.
            'cost_complete'     => $holdings !== [] && $unknownCount === 0,
        ];
    }

    /**
     * Value grouped by industry, for the allocation breakdown.
     *
     * Concentration is the risk an investor is least likely to notice on their
     * own: five holdings that are all minibus taxis on the same route is one
     * bet, not five.
     */
    public static function allocation(array $holdings): array
    {
        $total = 0.0;
        $groups = [];

        foreach ($holdings as $holding) {
            $sector = $holding['sector_name'] ?: 'Unclassified';
            $total += $holding['value'];

            $groups[$sector] ??= [
                'name'  => $sector,
                'icon'  => $holding['sector_icon'] ?: 'ti-briefcase',
                'value' => 0.0,
                'count' => 0,
            ];
            $groups[$sector]['value'] += $holding['value'];
            $groups[$sector]['count']++;
        }

        foreach ($groups as &$group) {
            $group['share'] = $total > 0 ? ($group['value'] / $total) * 100 : 0.0;
        }
        unset($group);

        uasort($groups, static fn ($a, $b) => $b['value'] <=> $a['value']);

        return array_values($groups);
    }

    /**
     * The investor's share of profit reported by their companies since they
     * settled.
     *
     * This is an *estimate of what has been earned on their behalf*, not a
     * record of money received - there is no distributions table, and inventing
     * one would put a number on this page that no record backs. It assumes the
     * current holding applied across every period counted, which is true for
     * anyone who hasn't added to a position and overstates slightly for anyone
     * who has. The page says both of those things.
     *
     * @return array{total: float, periods: int, companies: int}
     */
    public static function attributableIncome(int $userId, array $holdings): array
    {
        if ($holdings === []) {
            return ['total' => 0.0, 'periods' => 0, 'companies' => 0];
        }

        $ids = array_column($holdings, 'company_id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = Database::connection()->prepare(
            "SELECT company_id, period_end, net_operating_income
             FROM financial_periods
             WHERE company_id IN ({$placeholders})
             ORDER BY company_id, period_start"
        );
        $stmt->execute(array_map('intval', $ids));

        $byCompany = [];
        foreach ($stmt->fetchAll() as $period) {
            $byCompany[(int) $period['company_id']][] = $period;
        }

        $total = 0.0;
        $periodCount = 0;
        $companyCount = 0;

        foreach ($holdings as $holding) {
            $periods = $byCompany[(int) $holding['company_id']] ?? [];
            $settled = strtotime((string) $holding['first_settled']);
            $counted = 0;

            foreach ($periods as $period) {
                // Only periods that ended after the investor was on the register.
                // Counting earlier ones would credit them with profit earned
                // before they owned anything.
                if (strtotime((string) $period['period_end']) < $settled) {
                    continue;
                }

                $total += (float) $period['net_operating_income']
                    * self::DISTRIBUTABLE_SHARE
                    * ($holding['ownership_pct'] / 100);
                $counted++;
            }

            $periodCount += $counted;
            if ($counted > 0) {
                $companyCount++;
            }
        }

        return [
            'total'     => $total,
            'periods'   => $periodCount,
            'companies' => $companyCount,
        ];
    }
}
