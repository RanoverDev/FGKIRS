<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Database;
use Helpers\Auth;
use Helpers\ImageProcessor;
use PDO;

class StyleController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        if (!Auth::authorize(['admin'])) {
            header('Location: /login');
            exit;
        }

        $stmt = $this->db->query(
            "SELECT s.*, COUNT(g.id) as graduation_count
             FROM martial_arts_styles s
             LEFT JOIN graduations g ON s.id = g.style_id
             GROUP BY s.id
             ORDER BY s.name ASC"
        );
        $styles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view("admin/styles/index", ["styles" => $styles]);
    }

    public function create(): void
    {
        if (!Auth::authorize(['admin'])) {
            header('Location: /fgkirs-admin/styles');
            exit;
        }

        $this->view("admin/styles/form", ["style" => null]);
    }

    public function store(): void
    {
        if (!Auth::authorize(['admin'])) {
            header('Location: /fgkirs-admin/styles');
            exit;
        }

        $symbol = null;
        if (isset($_FILES['symbol']) && $_FILES['symbol']['error'] === UPLOAD_ERR_OK) {
            $symbol = ImageProcessor::process($_FILES['symbol'], __DIR__ . '/../../../public/uploads/styles', 500, 65);
        }

        $this->db->query(
            "INSERT INTO martial_arts_styles (name, description, symbol, created_at, updated_at)
             VALUES (:name, :description, :symbol, NOW(), NOW())",
            [
                'name'        => trim($_POST['name'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'symbol'      => $symbol,
            ]
        );

        header('Location: /fgkirs-admin/styles');
        exit;
    }

    public function edit(int $id): void
    {
        if (!Auth::authorize(['admin'])) {
            header('Location: /login');
            exit;
        }

        $stmt = $this->db->query(
            "SELECT * FROM martial_arts_styles WHERE id = :id",
            ['id' => $id]
        );
        $style = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$style) {
            header('Location: /fgkirs-admin/styles');
            exit;
        }

        $this->view("admin/styles/form", ["style" => $style]);
    }

    public function update(int $id): void
    {
        if (!Auth::authorize(['admin'])) {
            header('Location: /fgkirs-admin/styles');
            exit;
        }

        $stmt = $this->db->query("SELECT symbol FROM martial_arts_styles WHERE id = :id", ['id' => $id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        $symbol = $current['symbol'] ?? null;

        if (isset($_FILES['symbol']) && $_FILES['symbol']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../public/uploads/styles';
            $newSymbol = ImageProcessor::process($_FILES['symbol'], $uploadDir, 500, 65);
            if ($newSymbol) {
                if ($symbol) {
                    ImageProcessor::delete("$uploadDir/$symbol");
                }
                $symbol = $newSymbol;
            }
        }

        $this->db->query(
            "UPDATE martial_arts_styles
             SET name = :name, description = :description, symbol = :symbol, updated_at = NOW()
             WHERE id = :id",
            [
                'name'        => trim($_POST['name'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'symbol'      => $symbol,
                'id'          => $id,
            ]
        );

        header('Location: /fgkirs-admin/styles');
        exit;
    }

    public function delete(int $id): void
    {
        if (!Auth::authorize(['admin'])) {
            header('Location: /fgkirs-admin/styles');
            exit;
        }

        $stmt = $this->db->query("SELECT symbol FROM martial_arts_styles WHERE id = :id", ['id' => $id]);
        $style = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($style && $style['symbol']) {
            ImageProcessor::delete(__DIR__ . '/../../../public/uploads/styles/' . $style['symbol']);
        }

        $this->db->query(
            "DELETE FROM martial_arts_styles WHERE id = :id",
            ['id' => $id]
        );

        header('Location: /fgkirs-admin/styles');
        exit;
    }
}
