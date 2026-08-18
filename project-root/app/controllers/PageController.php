<?php
namespace app\controllers;

use app\core\Controller;
use app\core\Database;
use app\services\DiscoveryService;

class PageController extends Controller
{
    /**
     * The transaction fee, mirrored from the fees page so the worked examples
     * on both stay in step. If this rate ever changes it has to change in two
     * places - worth pulling into config the moment a third place needs it.
     */
    private const TRANSACTION_FEE = 0.06;

    public function howItWorks(): void
    {
        $this->render('pages/how-it-works', [
            'sectors'     => DiscoveryService::sectorFacets(),
            'totalActive' => DiscoveryService::activeListingCount(),
            // Real numbers, not an illustration. "How much do I need to start"
            // is the question a beginner actually has, and answering it with a
            // made-up figure would be the one dishonest paragraph on a page
            // whose whole job is building trust.
            'entry'       => $this->entryPoint(),
            'feeRate'     => self::TRANSACTION_FEE,
        ]);
    }

    public function fees(): void
    {
        $this->render('pages/fees');
    }

    /**
     * The cheapest and dearest share you could actually buy right now.
     *
     * Restricted to companies with shares still available: a listing that is
     * fully subscribed is not an entry point, however little it costs.
     *
     * @return array{min: ?float, max: ?float, count: int}
     */
    private function entryPoint(): array
    {
        $sql = "SELECT MIN(t.nav_per_share) AS min_price,
                       MAX(t.nav_per_share) AS max_price,
                       COUNT(*) AS available_count
                FROM (
                    SELECT c.nav_per_share,
                           c.shares_issued
                             - COALESCE((SELECT SUM(sh.shares) FROM shareholdings sh
                                         WHERE sh.company_id = c.id), 0)
                             - COALESCE((SELECT SUM(cm.shares_requested) FROM commitments cm
                                         WHERE cm.company_id = c.id AND cm.status = 'pending'), 0)
                           AS shares_available
                    FROM companies c
                    WHERE c.status = 'active'
                ) t
                WHERE t.shares_available > 0";

        $row = Database::connection()->query($sql)->fetch() ?: [];

        return [
            'min'   => isset($row['min_price']) ? (float) $row['min_price'] : null,
            'max'   => isset($row['max_price']) ? (float) $row['max_price'] : null,
            'count' => (int) ($row['available_count'] ?? 0),
        ];
    }
}
