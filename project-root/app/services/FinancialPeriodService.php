<?php
namespace app\services;

use app\core\Database;

class FinancialPeriodService
{
    public static function record(int $companyId, array $data, int $adminId): void
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $db->prepare(
                "INSERT INTO financial_periods
                    (company_id, period_start, period_end, gross_revenue, operating_costs, net_operating_income, arf_allocation)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                $companyId,
                $data['period_start'],
                $data['period_end'],
                $data['gross_revenue'],
                $data['operating_costs'],
                $data['net_operating_income'],
                $data['arf_allocation'],
            ]);

            if ($data['arf_allocation'] > 0) {
                $db->prepare("UPDATE companies SET arf_balance = arf_balance + ? WHERE id = ?")
                   ->execute([$data['arf_allocation'], $companyId]);
            }

            $db->prepare(
                "INSERT INTO audit_log (actor_type, actor_id, action, entity_type, entity_id, details)
                 VALUES ('admin', ?, 'record_financial_period', 'companies', ?, ?)"
            )->execute([$adminId, $companyId, "Recorded period {$data['period_start']} to {$data['period_end']}"]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e; // exits here on failure - the notification below never fires
        }

        $stmt = $db->prepare("SELECT name FROM companies WHERE id = ?");
        $stmt->execute([$companyId]);
        $companyName = $stmt->fetch()['name'] ?? 'a company you hold shares in';

        $message = "A new financial period ({$data['period_start']} to {$data['period_end']}) has been recorded for {$companyName}.";

        NotificationService::notifyShareholders(
            $companyId,
            'financial_period_recorded',
            $message,
            "New financial report for {$companyName}",
            "<p>{$message}</p>"
        );
    }
}
