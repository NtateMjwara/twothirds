<?php
namespace app\controllers\admin;

use app\core\AdminController;
use app\core\Database;

class RegistryController extends AdminController
{
    public function index(): void
    {
        $this->requireAdmin();

        $q = trim($_GET['q'] ?? '');

        $sql = "SELECT s.id, s.shares, s.settled_at,
                       c.reference AS company_reference, c.name AS company_name, c.nav_per_share,
                       u.id AS user_id, u.email, p.first_name, p.last_name
                FROM shareholdings s
                JOIN companies c ON c.id = s.company_id
                JOIN users u ON u.id = s.user_id
                LEFT JOIN user_profiles p ON p.user_id = u.id";

        $params = [];
        if ($q !== '') {
            $sql .= " WHERE u.email LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ?
                      OR c.name LIKE ? OR c.reference LIKE ?";
            $like = "%{$q}%";
            $params = [$like, $like, $like, $like, $like];
        }

        $sql .= " ORDER BY s.settled_at DESC";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        $this->render('admin/registry', ['rows' => $stmt->fetchAll(), 'q' => $q]);
    }
}
