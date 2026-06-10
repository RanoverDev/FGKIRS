<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Database;
use Helpers\Auth;
use Helpers\ImageProcessor;
use PDO;

class UserController extends Controller
{
    private Database $db;

    private const ATHLETE_ROLES = ['aluno', 'aluno-colaborador'];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            header('Location: /login');
            exit;
        }

        $sql = "SELECT u.*, d.name as dojo_name, d.city as dojo_city
                FROM users u
                LEFT JOIN dojos d ON u.dojo_id = d.id";

        if (Auth::isSensei()) {
            $sql .= " WHERE u.dojo_id = :dojo_id";
            $params = ['dojo_id' => Auth::dojoId()];
        } else {
            $params = [];
        }

        $sql .= " ORDER BY u.name ASC";

        $users = $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
        $this->view("admin/users/index", ["users" => $users]);
    }

    public function create(): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            header('Location: /login');
            exit;
        }

        $nextFgkirs = (int) $this->db->query(
            "SELECT COALESCE(MAX(fgkirs_registration), 0) + 1 FROM athlete_profiles"
        )->fetchColumn();

        $this->view("admin/users/form", [
            'user' => null,
            'dojos' => $this->getDojos(),
            'styles' => $this->getStyles(),
            'graduations' => $this->getGraduations(),
            'athleteProfile' => null,
            'nextFgkirs' => $nextFgkirs,
        ]);
    }

    public function store(): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            header('Location: /login');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'aluno';
        $dojoId = empty($_POST['dojo_id']) ? null : (int) $_POST['dojo_id'];
        $password = $_POST['password'] ?? '';

        if (empty($name)) {
            $_SESSION['error'] = 'O nome completo é obrigatório.';
            header('Location: /fgkirs-admin/users/create');
            exit;
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Por favor, insira um e-mail válido.';
            header('Location: /fgkirs-admin/users/create');
            exit;
        }

        $passwordError = null;
        if (!Auth::validatePasswordStrength($password, $passwordError)) {
            $_SESSION['error'] = $passwordError;
            header('Location: /fgkirs-admin/users/create');
            exit;
        }

        // Verificar e-mail duplicado
        try {
            $stmt = $this->db->query("SELECT id FROM users WHERE email = :email LIMIT 1", ['email' => $email]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = 'Este e-mail já está cadastrado no sistema.';
                header('Location: /fgkirs-admin/users/create');
                exit;
            }
        } catch (\Exception $e) {
            error_log('Error checking duplicate email: ' . $e->getMessage());
        }

        if (Auth::isSensei()) {
            $dojoId = Auth::dojoId();
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $photoFilename = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photoFilename = ImageProcessor::process(
                $_FILES['photo'],
                __DIR__ . '/../../../public/uploads/users'
            );
        }

        try {
            $this->db->query(
                "INSERT INTO users (name, email, password, role, dojo_id, photo, created_at, updated_at)
                 VALUES (:name, :email, :password, :role, :dojo_id, :photo, NOW(), NOW())",
                [
                    'name' => $name,
                    'email' => $email,
                    'password' => $hashedPassword,
                    'role' => $role,
                    'dojo_id' => $dojoId,
                    'photo' => $photoFilename
                ]
            );

            $userId = (int) $this->db->lastInsertId();

            if (in_array($role, self::ATHLETE_ROLES) || $role === 'sensei') {
                $this->saveAthleteProfile($userId);
            }

            $_SESSION['success'] = 'Usuário cadastrado com sucesso!';
            header('Location: /fgkirs-admin/users');
            exit;
        } catch (\Exception $e) {
            error_log('Error saving user: ' . $e->getMessage());
            $_SESSION['error'] = 'Erro ao salvar o usuário: ' . $e->getMessage();
            header('Location: /fgkirs-admin/users/create');
            exit;
        }
    }

    public function edit(int $id): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            header('Location: /login');
            exit;
        }

        $sql = "SELECT * FROM users WHERE id = :id";
        if (Auth::isSensei()) {
            $sql .= " AND dojo_id = :dojo_id";
            $params = ['id' => $id, 'dojo_id' => Auth::dojoId()];
        } else {
            $params = ['id' => $id];
        }

        $user = $this->db->query($sql, $params)->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            header('Location: /fgkirs-admin/users');
            exit;
        }

        $athleteProfile = null;
        if (in_array($user['role'], self::ATHLETE_ROLES) || $user['role'] === 'sensei') {
            $athleteProfile = $this->db->query(
                "SELECT * FROM athlete_profiles WHERE user_id = :id",
                ['id' => $id]
            )->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $nextFgkirs = (int) $this->db->query(
            "SELECT COALESCE(MAX(fgkirs_registration), 0) + 1 FROM athlete_profiles"
        )->fetchColumn();

        $this->view("admin/users/form", [
            'user' => $user,
            'dojos' => $this->getDojos(),
            'styles' => $this->getStyles(),
            'graduations' => $this->getGraduations(),
            'athleteProfile' => $athleteProfile,
            'nextFgkirs' => $nextFgkirs,
        ]);
    }

    public function update(int $id): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            header('Location: /login');
            exit;
        }

        $sql = "SELECT photo FROM users WHERE id = :id";
        if (Auth::isSensei()) {
            $sql .= " AND dojo_id = :dojo_id";
            $params = ['id' => $id, 'dojo_id' => Auth::dojoId()];
        } else {
            $params = ['id' => $id];
        }

        $user = $this->db->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            header('Location: /fgkirs-admin/users');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'aluno';
        $dojoId = empty($_POST['dojo_id']) ? null : (int) $_POST['dojo_id'];
        $password = $_POST['password'] ?? '';

        if (empty($name)) {
            $_SESSION['error'] = 'O nome completo é obrigatório.';
            header('Location: /fgkirs-admin/users/edit/' . $id);
            exit;
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Por favor, insira um e-mail válido.';
            header('Location: /fgkirs-admin/users/edit/' . $id);
            exit;
        }

        if (!empty($password)) {
            $passwordError = null;
            if (!Auth::validatePasswordStrength($password, $passwordError)) {
                $_SESSION['error'] = $passwordError;
                header('Location: /fgkirs-admin/users/edit/' . $id);
                exit;
            }
        }

        // Verificar e-mail duplicado em outros usuários
        try {
            $stmt = $this->db->query("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1", [
                'email' => $email,
                'id' => $id
            ]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = 'Este e-mail já está em uso por outro usuário.';
                header('Location: /fgkirs-admin/users/edit/' . $id);
                exit;
            }
        } catch (\Exception $e) {
            error_log('Error checking duplicate email on update: ' . $e->getMessage());
        }

        if (Auth::isSensei()) {
            $dojoId = Auth::dojoId();
        }

        $photoFilename = $user['photo'];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../public/uploads/users';
            $newPhoto = ImageProcessor::process($_FILES['photo'], $uploadDir);
            if ($newPhoto) {
                if ($photoFilename) {
                    ImageProcessor::delete($uploadDir . '/' . $photoFilename);
                }
                $photoFilename = $newPhoto;
            }
        }

        try {
            if (!empty($password)) {
                $this->db->query(
                    "UPDATE users SET name=:name, email=:email, password=:password,
                     role=:role, dojo_id=:dojo_id, photo=:photo, updated_at=NOW() WHERE id=:id",
                    [
                        'name' => $name,
                        'email' => $email,
                        'password' => password_hash($password, PASSWORD_BCRYPT),
                        'role' => $role,
                        'dojo_id' => $dojoId,
                        'photo' => $photoFilename,
                        'id' => $id
                    ]
                );
            } else {
                $this->db->query(
                    "UPDATE users SET name=:name, email=:email,
                     role=:role, dojo_id=:dojo_id, photo=:photo, updated_at=NOW() WHERE id=:id",
                    [
                        'name' => $name,
                        'email' => $email,
                        'role' => $role,
                        'dojo_id' => $dojoId,
                        'photo' => $photoFilename,
                        'id' => $id
                    ]
                );
            }

            if (in_array($role, self::ATHLETE_ROLES) || $role === 'sensei') {
                $this->saveAthleteProfile($id);
            }

            $_SESSION['success'] = 'Usuário atualizado com sucesso!';
            header('Location: /fgkirs-admin/users');
            exit;
        } catch (\Exception $e) {
            error_log('Error updating user: ' . $e->getMessage());
            $_SESSION['error'] = 'Erro ao atualizar o usuário: ' . $e->getMessage();
            header('Location: /fgkirs-admin/users/edit/' . $id);
            exit;
        }
    }

    public function delete(int $id): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            header('Location: /login');
            exit;
        }

        $sql = "SELECT photo FROM users WHERE id = :id";
        if (Auth::isSensei()) {
            $sql .= " AND dojo_id = :dojo_id";
            $params = ['id' => $id, 'dojo_id' => Auth::dojoId()];
        } else {
            $params = ['id' => $id];
        }

        $user = $this->db->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            if ($user['photo']) {
                ImageProcessor::delete(__DIR__ . '/../../../public/uploads/users/' . $user['photo']);
            }
            $this->db->query("DELETE FROM users WHERE id = :id", ['id' => $id]);
        }

        header('Location: /fgkirs-admin/users');
        exit;
    }

    public function toggleStatus(int $id): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $status = $data['status'] ?? 'active';

        if (!in_array($status, ['active', 'inactive', 'absent'])) {
            $this->json(['success' => false, 'message' => 'Invalid status'], 400);
            return;
        }

        if (Auth::isSensei()) {
            $this->db->query(
                "UPDATE users SET status = :status
                 WHERE id = :id AND dojo_id = :dojo_id",
                ['status' => $status, 'id' => $id, 'dojo_id' => Auth::dojoId()]
            );
        } else {
            $this->db->query(
                "UPDATE users SET status = :status WHERE id = :id",
                ['status' => $status, 'id' => $id]
            );
        }

        $this->json(['success' => true]);
    }

    private function saveAthleteProfile(int $userId): void
    {
        $styleId = !empty($_POST['athlete_style_id']) ? (int) $_POST['athlete_style_id'] : null;
        $graduationId = !empty($_POST['athlete_graduation_id']) ? (int) $_POST['athlete_graduation_id'] : null;
        $birthDate = !empty($_POST['athlete_birth_date']) ? $_POST['athlete_birth_date'] : null;
        $weight = !empty($_POST['athlete_weight']) ? (float) $_POST['athlete_weight'] : null;
        $height = !empty($_POST['athlete_height']) ? (int) $_POST['athlete_height'] : null;
        $gender = in_array($_POST['athlete_gender'] ?? '', ['M', 'F', 'O'])
            ? $_POST['athlete_gender'] : null;

        $fgkirsReg = !empty($_POST['fgkirs_registration']) ? (int) $_POST['fgkirs_registration'] : null;
        $cbkiReg = trim($_POST['cbki_registration'] ?? '') ?: null;

        $this->db->query(
            "INSERT INTO athlete_profiles
                (user_id, birth_date, email, phone_whatsapp, gender, weight, height,
                 style_id, graduation_id, fgkirs_registration, cbki_registration, notes)
             VALUES
                (:user_id,:birth_date,:email,:phone_whatsapp,:gender,:weight,:height,
                 :style_id,:graduation_id,:fgkirs_registration,:cbki_registration,:notes)
             ON DUPLICATE KEY UPDATE
                birth_date          = VALUES(birth_date),
                email               = VALUES(email),
                phone_whatsapp      = VALUES(phone_whatsapp),
                gender              = VALUES(gender),
                weight              = VALUES(weight),
                height              = VALUES(height),
                style_id            = VALUES(style_id),
                graduation_id       = VALUES(graduation_id),
                fgkirs_registration = VALUES(fgkirs_registration),
                cbki_registration   = VALUES(cbki_registration),
                notes               = VALUES(notes)",
            [
                'user_id' => $userId,
                'birth_date' => $birthDate,
                'email' => trim($_POST['athlete_email'] ?? '') ?: null,
                'phone_whatsapp' => preg_replace('/\D/', '', $_POST['athlete_phone_whatsapp'] ?? '') ?: null,
                'gender' => $gender,
                'weight' => $weight,
                'height' => $height,
                'style_id' => $styleId,
                'graduation_id' => $graduationId,
                'fgkirs_registration' => $fgkirsReg,
                'cbki_registration' => $cbkiReg,
                'notes' => trim($_POST['athlete_notes'] ?? '') ?: null,
            ]
        );
    }

    private function getDojos(): array
    {
        return $this->db->query("SELECT id, name FROM dojos ORDER BY name ASC")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getStyles(): array
    {
        return $this->db->query("SELECT id, name FROM martial_arts_styles ORDER BY name ASC")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getGraduations(): array
    {
        return $this->db->query(
            "SELECT g.id, g.belt_name, g.belt_color, g.order_rank, g.style_id, s.name as style_name
             FROM graduations g
             JOIN martial_arts_styles s ON g.style_id = s.id
             ORDER BY s.name ASC, g.order_rank ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
}
