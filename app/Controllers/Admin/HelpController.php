<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Database;
use Helpers\Auth;
use Helpers\Csrf;
use PDO;

class HelpController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }

        $videos = $this->db->query(
            "SELECT hv.*, u.name AS author_name
             FROM help_videos hv
             LEFT JOIN users u ON u.id = hv.author_id
             ORDER BY hv.created_at DESC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $this->view("admin/help/index", ["videos" => $videos]);
    }

    public function store(): void
    {
        if (!Auth::isAdmin() || !Csrf::validate($_POST['csrf_token'] ?? null)) {
            header('Location: /fgkirs-admin/help');
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $url   = trim($_POST['url'] ?? '');

        if ($title !== '' && $url !== '') {
            $this->db->query(
                "INSERT INTO help_videos (title, url, author_id, created_at) VALUES (:title, :url, :author_id, NOW())",
                [
                    'title'     => $title,
                    'url'       => $url,
                    'author_id' => Auth::id(),
                ]
            );
        }

        header('Location: /fgkirs-admin/help');
        exit;
    }

    public function delete(int $id): void
    {
        if (!Auth::isAdmin()) {
            header('Location: /fgkirs-admin/help');
            exit;
        }

        $this->db->query("DELETE FROM help_videos WHERE id = :id", ['id' => $id]);

        header('Location: /fgkirs-admin/help');
        exit;
    }
}
