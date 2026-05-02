<?php

namespace Controllers\Admin;

use Core\Database;
use Helpers\Auth;
use Helpers\ImageProcessor;
use PDO;

/**
 * DojoController - Dojo Management
 * Handles CRUD operations for dojos with admin/sensei access control
 */
class DojoController extends \Controllers\Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Display list of dojos
     * Admin sees all, Sensei sees only their own
     */
    public function index(): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            header('Location: /login.php');
            exit;
        }

        $sql = "SELECT d.*, u.name as sensei_name 
                FROM dojos d 
                LEFT JOIN users u ON d.sensei_id = u.id";

        // Business rule: Sensei can only see their own dojo
        if (Auth::isSensei()) {
            $sql .= " WHERE d.id = :dojo_id";
            $params = ['dojo_id' => Auth::dojoId()];
        } else {
            $params = [];
        }

        $sql .= " ORDER BY d.name ASC";

        $stmt = $this->db->query($sql, $params);
        $dojos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Load view
        $this->view("admin/dojos/index", ["dojos" => $dojos]);
    }

    /**
     * Show create dojo form (Admin only)
     */
    public function create(): void
    {
        // Only admin can create dojos
        if (!Auth::authorize(['admin'])) {
            header('Location: /fgkirs-admin/dojos');
            exit;
        }

        // Get senseis for dropdown
        $senseis = $this->getSenseis();

        // Load view
        $dojo = null; // New dojo
        require_once __DIR__ . '/../../Views/admin/dojos/form.php';
    }

    /**
     * Store new dojo with logo processing (Admin only)
     */
    public function store(): void
    {
        if (!Auth::authorize(['admin'])) {
            header('Location: /fgkirs-admin/dojos');
            exit;
        }

        $name = $_POST['name'] ?? '';
        $address = $_POST['address'] ?? '';
        $city = $_POST['city'] ?? '';
        $state = $_POST['state'] ?? '';
        $senseiId = $_POST['sensei_id'] ?? null;

        // Process logo if uploaded
        $logoFilename = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../public/uploads/dojos';
            $logoFilename = ImageProcessor::process($_FILES['logo'], $uploadDir);
        }

        // Insert dojo
        $sql = "INSERT INTO dojos (name, address, city, state, sensei_id, logo, created_at, updated_at) 
                VALUES (:name, :address, :city, :state, :sensei_id, :logo, NOW(), NOW())";

        $params = [
            'name' => $name,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'sensei_id' => $senseiId,
            'logo' => $logoFilename,
        ];

        $this->db->query($sql, $params);

        header('Location: /fgkirs-admin/dojos');
        exit;
    }

    /**
     * Show edit dojo form
     * Admin can edit any, Sensei can only edit their own
     */
    public function edit(int $id): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            header('Location: /login.php');
            exit;
        }

        // Get dojo
        $sql = "SELECT * FROM dojos WHERE id = :id";

        // Business rule: Sensei can only edit their dojo
        if (Auth::isSensei() && $id !== Auth::dojoId()) {
            header('Location: /fgkirs-admin/dojos');
            exit;
        }

        $stmt = $this->db->query($sql, ['id' => $id]);
        $dojo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dojo) {
            header('Location: /fgkirs-admin/dojos');
            exit;
        }

        // Get senseis for dropdown (admin only)
        $senseis = Auth::isAdmin() ? $this->getSenseis() : [];

        // Load view
        require_once __DIR__ . '/../../Views/admin/dojos/form.php';
    }

    /**
     * Update dojo with optional logo replacement
     */
    public function update(int $id): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            header('Location: /login.php');
            exit;
        }

        // Business rule: Sensei can only update their dojo
        if (Auth::isSensei() && $id !== Auth::dojoId()) {
            header('Location: /fgkirs-admin/dojos');
            exit;
        }

        // Get current dojo data
        $stmt = $this->db->query("SELECT logo FROM dojos WHERE id = :id", ['id' => $id]);
        $dojo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dojo) {
            header('Location: /fgkirs-admin/dojos');
            exit;
        }

        $name = $_POST['name'] ?? '';
        $address = $_POST['address'] ?? '';
        $city = $_POST['city'] ?? '';
        $state = $_POST['state'] ?? '';
        $senseiId = $_POST['sensei_id'] ?? null;

        // Sensei cannot change sensei assignment
        if (Auth::isSensei()) {
            $senseiId = $dojo['sensei_id'] ?? null;
        }

        // Process new logo if uploaded
        $logoFilename = $dojo['logo'];
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../public/uploads/dojos';
            $newLogo = ImageProcessor::process($_FILES['logo'], $uploadDir);

            if ($newLogo) {
                // Delete old logo
                if ($logoFilename) {
                    ImageProcessor::delete($uploadDir . '/' . $logoFilename);
                }
                $logoFilename = $newLogo;
            }
        }

        // Update dojo
        $sql = "UPDATE dojos 
                SET name = :name, address = :address, city = :city, state = :state, 
                    sensei_id = :sensei_id, logo = :logo, updated_at = NOW() 
                WHERE id = :id";

        $params = [
            'name' => $name,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'sensei_id' => $senseiId,
            'logo' => $logoFilename,
            'id' => $id,
        ];

        $this->db->query($sql, $params);

        header('Location: /fgkirs-admin/dojos');
        exit;
    }

    /**
     * Delete dojo (Admin only)
     */
    public function delete(int $id): void
    {
        // Only admin can delete dojos
        if (!Auth::authorize(['admin'])) {
            header('Location: /fgkirs-admin/dojos');
            exit;
        }

        // Get dojo
        $stmt = $this->db->query("SELECT logo FROM dojos WHERE id = :id", ['id' => $id]);
        $dojo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dojo) {
            // Delete logo file
            if ($dojo['logo']) {
                $uploadDir = __DIR__ . '/../../../public/uploads/dojos';
                ImageProcessor::delete($uploadDir . '/' . $dojo['logo']);
            }

            // Delete dojo record
            $this->db->query("DELETE FROM dojos WHERE id = :id", ['id' => $id]);
        }

        header('Location: /fgkirs-admin/dojos');
        exit;
    }

    /**
     * Get senseis list for dropdown
     */
    private function getSenseis(): array
    {
        $sql = "SELECT id, name FROM users WHERE role = 'sensei' ORDER BY name ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
