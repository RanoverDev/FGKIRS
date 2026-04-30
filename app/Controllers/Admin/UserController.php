<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Database;
use Helpers\Auth;
use Helpers\ImageProcessor;
use PDO;

/**
 * UserController - Admin User Management
 * Handles CRUD operations for users with role-based access control
 */
class UserController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Display list of users
     * Sensei sees only students from their dojo, Admin sees all
     */
    public function index(): void
    {
        // Check authentication
        if (!Auth::authorize(['admin', 'sensei'])) {
            header('Location: /login.php');
            exit;
        }

        $sql = "SELECT u.*, d.name as dojo_name 
                FROM users u 
                LEFT JOIN dojos d ON u.dojo_id = d.id";

        // Apply role-based filtering
        if (Auth::isSensei()) {
            $sql .= " WHERE u.dojo_id = :dojo_id";
            $params = ['dojo_id' => Auth::dojoId()];
        } else {
            $params = [];
        }

        $sql .= " ORDER BY u.name ASC";

        $stmt = $this->db->query($sql, $params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Load view
        require_once __DIR__ . '/../../Views/fgkirs-admin/users/index.php';
    }

    /**
     * Show create user form
     */
    public function create(): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            header('Location: /login.php');
            exit;
        }

        // Get dojos for dropdown
        $dojos = $this->getDojos();

        // Load view
        $user = null; // New user
        require_once __DIR__ . '/../../Views/fgkirs-admin/users/form.php';
    }

    /**
     * Store new user with image processing
     */
    public function store(): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            header('Location: /login.php');
            exit;
        }

        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'aluno';
        $dojoId = $_POST['dojo_id'] ?? null;

        // Business rule: Sensei can only assign their own dojo
        if (Auth::isSensei()) {
            $dojoId = Auth::dojoId();
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Process image if uploaded
        $photoFilename = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../public/uploads/users';
            $photoFilename = ImageProcessor::process($_FILES['photo'], $uploadDir);
        }

        // Insert user
        $sql = "INSERT INTO users (name, email, password, role, dojo_id, photo, created_at, updated_at) 
                VALUES (:name, :email, :password, :role, :dojo_id, :photo, NOW(), NOW())";

        $params = [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role,
            'dojo_id' => $dojoId,
            'photo' => $photoFilename,
        ];

        $this->db->query($sql, $params);

        // Redirect to index
        header('Location: /fgkirs-admin/users');
        exit;
    }

    /**
     * Show edit user form
     */
    public function edit(int $id): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            header('Location: /login.php');
            exit;
        }

        // Get user
        $sql = "SELECT * FROM users WHERE id = :id";

        // Business rule: Sensei can only edit users from their dojo
        if (Auth::isSensei()) {
            $sql .= " AND dojo_id = :dojo_id";
            $params = ['id' => $id, 'dojo_id' => Auth::dojoId()];
        } else {
            $params = ['id' => $id];
        }

        $stmt = $this->db->query($sql, $params);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            header('Location: /fgkirs-admin/users');
            exit;
        }

        // Get dojos for dropdown
        $dojos = $this->getDojos();

        // Load view
        require_once __DIR__ . '/../../Views/fgkirs-admin/users/form.php';
    }

    /**
     * Update user with optional image replacement
     */
    public function update(int $id): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            header('Location: /login.php');
            exit;
        }

        // Verify user access
        $sql = "SELECT photo FROM users WHERE id = :id";

        if (Auth::isSensei()) {
            $sql .= " AND dojo_id = :dojo_id";
            $params = ['id' => $id, 'dojo_id' => Auth::dojoId()];
        } else {
            $params = ['id' => $id];
        }

        $stmt = $this->db->query($sql, $params);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            header('Location: /fgkirs-admin/users');
            exit;
        }

        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? 'aluno';
        $dojoId = $_POST['dojo_id'] ?? null;

        // Business rule: Sensei can only assign their own dojo
        if (Auth::isSensei()) {
            $dojoId = Auth::dojoId();
        }

        // Process new image if uploaded
        $photoFilename = $user['photo'];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../public/uploads/users';
            $newPhoto = ImageProcessor::process($_FILES['photo'], $uploadDir);

            if ($newPhoto) {
                // Delete old photo
                if ($photoFilename) {
                    ImageProcessor::delete($uploadDir . '/' . $photoFilename);
                }
                $photoFilename = $newPhoto;
            }
        }

        // Update user (exclude password if not provided)
        if (!empty($_POST['password'])) {
            $hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $sql = "UPDATE users 
                    SET name = :name, email = :email, password = :password, 
                        role = :role, dojo_id = :dojo_id, photo = :photo, updated_at = NOW() 
                    WHERE id = :id";
            $params = [
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
                'role' => $role,
                'dojo_id' => $dojoId,
                'photo' => $photoFilename,
                'id' => $id,
            ];
        } else {
            $sql = "UPDATE users 
                    SET name = :name, email = :email, role = :role, 
                        dojo_id = :dojo_id, photo = :photo, updated_at = NOW() 
                    WHERE id = :id";
            $params = [
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'dojo_id' => $dojoId,
                'photo' => $photoFilename,
                'id' => $id,
            ];
        }

        $this->db->query($sql, $params);

        header('Location: /fgkirs-admin/users');
        exit;
    }

    /**
     * Delete user
     */
    public function delete(int $id): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            header('Location: /login.php');
            exit;
        }

        // Get user and verify access
        $sql = "SELECT photo FROM users WHERE id = :id";

        if (Auth::isSensei()) {
            $sql .= " AND dojo_id = :dojo_id";
            $params = ['id' => $id, 'dojo_id' => Auth::dojoId()];
        } else {
            $params = ['id' => $id];
        }

        $stmt = $this->db->query($sql, $params);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Delete photo file
            if ($user['photo']) {
                $uploadDir = __DIR__ . '/../../../public/uploads/users';
                ImageProcessor::delete($uploadDir . '/' . $user['photo']);
            }

            // Delete user record
            $this->db->query("DELETE FROM users WHERE id = :id", ['id' => $id]);
        }

        header('Location: /fgkirs-admin/users');
        exit;
    }

    /**
     * Toggle student status (AJAX endpoint)
     */
    public function toggleStatus(int $id): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
            return;
        }

        // Get JSON input
        $data = json_decode(file_get_contents('php://input'), true);
        $status = $data['status'] ?? 'active';

        // Validate status
        if (!in_array($status, ['active', 'inactive', 'absent'])) {
            $this->json(['success' => false, 'message' => 'Invalid status'], 400);
            return;
        }

        // Update student_profiles table
        $sql = "UPDATE student_profiles SET status = :status WHERE user_id = :id";

        // For Sensei, verify the student is from their dojo
        if (Auth::isSensei()) {
            $sql = "UPDATE student_profiles sp 
                    JOIN users u ON sp.user_id = u.id 
                    SET sp.status = :status 
                    WHERE sp.user_id = :id AND u.dojo_id = :dojo_id";
            $params = ['status' => $status, 'id' => $id, 'dojo_id' => Auth::dojoId()];
        } else {
            $params = ['status' => $status, 'id' => $id];
        }

        $this->db->query($sql, $params);

        $this->json(['success' => true]);
    }

    /**
     * Get dojos list for dropdown
     */
    private function getDojos(): array
    {
        $sql = "SELECT id, name FROM dojos ORDER BY name ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
