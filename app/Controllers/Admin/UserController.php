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
        $role = $_POST['role'] ?? '';
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

        if (!in_array($role, ['admin', 'sensei', 'aluno-colaborador', 'aluno'])) {
            $_SESSION['error'] = 'Selecione um perfil válido para o usuário.';
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

        // Sempre busca o perfil de atleta independente do role atual,
        // pois o admin pode estar alterando o role na tela
        $athleteProfile = $this->db->query(
            "SELECT * FROM athlete_profiles WHERE user_id = :id",
            ['id' => $id]
        )->fetch(PDO::FETCH_ASSOC) ?: null;

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
        $role = $_POST['role'] ?? '';
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

        if (!in_array($role, ['admin', 'sensei', 'aluno-colaborador', 'aluno'])) {
            $_SESSION['error'] = 'Selecione um perfil válido para o usuário.';
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

    public function profile(int $id): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            $this->json(['error' => 'Não autorizado'], 403);
            return;
        }

        $sql = "SELECT u.id, u.name, u.email, u.role, u.photo, u.status,
                       d.name AS dojo_name, d.city AS dojo_city,
                       ap.birth_date, ap.email AS athlete_email, ap.phone_whatsapp,
                       ap.gender, ap.is_para_karate, ap.weight, ap.height,
                       ap.fgkirs_registration, ap.cbki_registration, ap.notes,
                       g.belt_name, g.belt_color,
                       s.name AS style_name
                FROM users u
                LEFT JOIN dojos d ON u.dojo_id = d.id
                LEFT JOIN athlete_profiles ap ON ap.user_id = u.id
                LEFT JOIN graduations g ON g.id = ap.graduation_id
                LEFT JOIN martial_arts_styles s ON s.id = ap.style_id
                WHERE u.id = :id";

        $params = ['id' => $id];
        if (Auth::isSensei()) {
            $sql .= " AND u.dojo_id = :dojo_id";
            $params['dojo_id'] = Auth::dojoId();
        }

        $data = $this->db->query($sql, $params)->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            $this->json(['error' => 'Usuário não encontrado'], 404);
            return;
        }

        $this->json($data);
    }

    private function saveAthleteProfile(int $userId): void
    {
        $fields = [
            'birth_date'          => !empty($_POST['athlete_birth_date']) ? $_POST['athlete_birth_date'] : null,
            'email'               => trim($_POST['athlete_email'] ?? '') ?: null,
            'phone_whatsapp'      => preg_replace('/\D/', '', $_POST['athlete_phone_whatsapp'] ?? '') ?: null,
            'gender'              => in_array($_POST['athlete_gender'] ?? '', ['M', 'F', 'O']) ? $_POST['athlete_gender'] : null,
            'is_para_karate'      => ($_POST['athlete_para_karate'] ?? '0') === '1' ? 1 : 0,
            'weight'              => !empty($_POST['athlete_weight']) ? (float) $_POST['athlete_weight'] : null,
            'height'              => !empty($_POST['athlete_height']) ? (int) $_POST['athlete_height'] : null,
            'style_id'            => !empty($_POST['athlete_style_id']) ? (int) $_POST['athlete_style_id'] : null,
            'graduation_id'       => !empty($_POST['athlete_graduation_id']) ? (int) $_POST['athlete_graduation_id'] : null,
            'fgkirs_registration' => !empty($_POST['fgkirs_registration']) ? (int) $_POST['fgkirs_registration'] : null,
            'cbki_registration'   => trim($_POST['cbki_registration'] ?? '') ?: null,
            'notes'               => trim($_POST['athlete_notes'] ?? '') ?: null,
        ];

        // Verifica se já existe um registro para não sobrescrever campos preenchidos com null
        $existing = $this->db->query(
            "SELECT * FROM athlete_profiles WHERE user_id = :uid",
            ['uid' => $userId]
        )->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Mantém valores existentes quando o campo não foi enviado preenchido
            foreach ($fields as $col => $val) {
                if ($val === null && $existing[$col] !== null) {
                    $fields[$col] = $existing[$col];
                }
            }
            $sets = implode(', ', array_map(fn($c) => "$c = :$c", array_keys($fields)));
            $params = array_merge($fields, ['uid' => $userId]);
            $this->db->query("UPDATE athlete_profiles SET $sets WHERE user_id = :uid", $params);
        } else {
            $cols   = implode(', ', array_keys($fields));
            $vals   = implode(', ', array_map(fn($c) => ":$c", array_keys($fields)));
            $params = array_merge(['user_id' => $userId], $fields);
            $this->db->query(
                "INSERT INTO athlete_profiles (user_id, $cols) VALUES (:user_id, $vals)",
                $params
            );
        }
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
