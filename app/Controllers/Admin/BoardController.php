<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Database;
use Helpers\Auth;
use PDO;

class BoardController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        if (!Auth::authorize(['admin'])) {
            header('Location: /fgkirs-admin/dashboard');
            exit;
        }

        $positions = $this->db->query(
            "SELECT * FROM board_positions ORDER BY sort_order ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $assignments = $this->db->query(
            "SELECT ba.*, u.name as user_name
             FROM board_assignments ba
             LEFT JOIN users u ON ba.user_id = u.id
             ORDER BY ba.position_id ASC, ba.sort_order ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        // Index assignments by position_id
        $assignedByPosition = [];
        foreach ($assignments as $a) {
            $assignedByPosition[$a['position_id']][] = $a;
        }

        $senseis = $this->db->query(
            "SELECT id, name FROM users WHERE role IN ('admin','sensei') ORDER BY name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/board/index', [
            'positions'          => $positions,
            'assignedByPosition' => $assignedByPosition,
            'senseis'            => $senseis,
        ]);
    }

    public function save(): void
    {
        if (!Auth::authorize(['admin'])) {
            header('Location: /fgkirs-admin/dashboard');
            exit;
        }

        // assignments[position_id][] = user_id
        $incoming = $_POST['assignments'] ?? [];

        // Delete all current assignments and re-insert
        $this->db->query("DELETE FROM board_assignments");

        $sort = [];
        foreach ($incoming as $positionId => $userIds) {
            $positionId = (int) $positionId;
            foreach ((array) $userIds as $userId) {
                $userId = (int) $userId;
                if ($userId <= 0) continue;
                $s = $sort[$positionId] = ($sort[$positionId] ?? 0) + 1;
                $this->db->query(
                    "INSERT INTO board_assignments (position_id, user_id, sort_order) VALUES (:pid, :uid, :s)",
                    ['pid' => $positionId, 'uid' => $userId, 's' => $s]
                );
            }
        }

        $_SESSION['success'] = 'Estrutura Administrativa atualizada com sucesso!';
        header('Location: /fgkirs-admin/board');
        exit;
    }
}
