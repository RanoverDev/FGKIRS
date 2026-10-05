<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Database;
use Helpers\Auth;
use PDO;

/**
 * GraduationController - Belt Progression Management
 * Handles student promotions and graduation history
 */
class GraduationController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Display all graduation levels by style
     */
    public function index(): void
    {
        if (!Auth::authorize(['admin'])) {
            header('Location: /fgkirs-admin');
            exit;
        }

        $stmt = $this->db->query(
            "SELECT g.*, s.name as style_name
             FROM graduations g
             JOIN martial_arts_styles s ON g.style_id = s.id
             ORDER BY s.name, g.order_rank"
        );
        $graduations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view("admin/graduations/index", ["graduations" => $graduations]);
    }

    public function create(): void
    {
        if (!Auth::authorize(['admin'])) {
            header('Location: /fgkirs-admin/graduations');
            exit;
        }

        $stmt = $this->db->query("SELECT id, name FROM martial_arts_styles ORDER BY name ASC");
        $styles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view("admin/graduations/form", ["graduation" => null, "styles" => $styles]);
    }

    public function store(): void
    {
        if (!Auth::authorize(['admin'])) {
            header('Location: /fgkirs-admin/graduations');
            exit;
        }

        $styleId = (int) ($_POST['style_id'] ?? 0);
        $beltName = trim($_POST['belt_name'] ?? '');
        $beltColor = trim($_POST['belt_color'] ?? '#000000');
        $desc = trim($_POST['requirements'] ?? '');
        $level = $this->levelFromPost();

        $stmt = $this->db->query(
            "SELECT COALESCE(MAX(order_rank), -1) + 1 as next_rank
             FROM graduations WHERE style_id = :style_id",
            ['style_id' => $styleId]
        );
        $nextRank = (int) $stmt->fetchColumn();

        $this->db->query(
            "INSERT INTO graduations (style_id, belt_name, belt_color, order_rank, level, requirements, created_at, updated_at)
             VALUES (:style_id, :belt_name, :belt_color, :order_rank, :level, :requirements, NOW(), NOW())",
            [
                'level' => $level,
                'style_id' => $styleId,
                'belt_name' => $beltName,
                'belt_color' => $beltColor,
                'order_rank' => $nextRank,
                'requirements' => $desc,
            ]
        );

        header('Location: /fgkirs-admin/graduations');
        exit;
    }

    public function edit(int $id): void
    {
        if (!Auth::authorize(['admin'])) {
            header('Location: /login');
            exit;
        }

        $stmt = $this->db->query("SELECT * FROM graduations WHERE id = :id", ['id' => $id]);
        $graduation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$graduation) {
            header('Location: /fgkirs-admin/graduations');
            exit;
        }

        $stmt = $this->db->query("SELECT id, name FROM martial_arts_styles ORDER BY name ASC");
        $styles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view("admin/graduations/form", ["graduation" => $graduation, "styles" => $styles]);
    }

    public function update(int $id): void
    {
        if (!Auth::authorize(['admin'])) {
            header('Location: /fgkirs-admin/graduations');
            exit;
        }

        $this->db->query(
            "UPDATE graduations
             SET style_id = :style_id, belt_name = :belt_name, belt_color = :belt_color,
                 level = :level, requirements = :requirements, updated_at = NOW()
             WHERE id = :id",
            [
                'level' => $this->levelFromPost(),
                'style_id' => (int) ($_POST['style_id'] ?? 0),
                'belt_name' => trim($_POST['belt_name'] ?? ''),
                'belt_color' => trim($_POST['belt_color'] ?? '#000000'),
                'requirements' => trim($_POST['requirements'] ?? ''),
                'id' => $id,
            ]
        );

        header('Location: /fgkirs-admin/graduations');
        exit;
    }

    public function delete(int $id): void
    {
        if (!Auth::authorize(['admin'])) {
            header('Location: /fgkirs-admin/graduations');
            exit;
        }

        $this->db->query("DELETE FROM graduations WHERE id = :id", ['id' => $id]);

        header('Location: /fgkirs-admin/graduations');
        exit;
    }

    /**
     * Show graduation history for a specific student
     */
    public function history(int $userId): void
    {
        if (!Auth::check()) {
            header('Location: /login.php');
            exit;
        }

        // Get student info
        $sql = "SELECT u.*, sp.registration_number, sp.status as student_status,
                       mas.name as style_name, g.belt_name, g.belt_color
                FROM users u
                LEFT JOIN student_profiles sp ON u.id = sp.user_id
                LEFT JOIN martial_arts_styles mas ON sp.style_id = mas.id
                LEFT JOIN graduations g ON sp.current_graduation_id = g.id
                WHERE u.id = :user_id";

        $stmt = $this->db->query($sql, ['user_id' => $userId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            header('Location: /fgkirs-admin/users');
            exit;
        }

        //Check authorization: admin, sensei (only own dojo), or self
        if (!Auth::isAdmin()) {
            if (Auth::isSensei() && $student['dojo_id'] !== Auth::dojoId()) {
                header('Location: /fgkirs-admin/users');
                exit;
            } elseif ($userId !== Auth::id() && !Auth::isSensei()) {
                header('Location: /fgkirs-admin/users');
                exit;
            }
        }

        // Get graduation history
        $sql = "SELECT gh.*, g.belt_name, g.belt_color, g.order_rank,
                       u.name as promoted_by_name
                FROM graduation_history gh
                JOIN graduations g ON gh.graduation_id = g.id
                JOIN users u ON gh.promoted_by_sensei_id = u.id
                JOIN student_profiles sp ON gh.student_profile_id = sp.id
                WHERE sp.user_id = :user_id
                ORDER BY gh.promotion_date DESC, g.order_rank DESC";

        $stmt = $this->db->query($sql, ['user_id' => $userId]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view("admin/graduations/history", ["student" => $student, "history" => $history]);
    }

    /**
     * Show promotion form for a student
     */
    public function promoteForm(int $userId): void
    {
        // Only President or Sensei can promote
        if (!Auth::authorize(['admin', 'sensei'])) {
            header('Location: /fgkirs-admin/users');
            exit;
        }

        // Get student profile
        $sql = "SELECT u.*, sp.id as profile_id, sp.style_id, sp.current_graduation_id,
                       mas.name as style_name, g.belt_name as current_belt, g.order_rank as current_rank
                FROM users u
                JOIN student_profiles sp ON u.id = sp.user_id
                JOIN martial_arts_styles mas ON sp.style_id = mas.id
                JOIN graduations g ON sp.current_graduation_id = g.id
                WHERE u.id = :user_id";

        $stmt = $this->db->query($sql, ['user_id' => $userId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            header('Location: /fgkirs-admin/users');
            exit;
        }

        // Verify authorization: Sensei can only promote students from their dojo
        if (Auth::isSensei() && $student['dojo_id'] !== Auth::dojoId()) {
            header('Location: /fgkirs-admin/users');
            exit;
        }

        // Get available next graduations (current_rank + 1)
        $sql = "SELECT * FROM graduations 
                WHERE style_id = :style_id AND order_rank > :current_rank
                ORDER BY order_rank ASC";

        $stmt = $this->db->query($sql, [
            'style_id' => $student['style_id'],
            'current_rank' => $student['current_rank']
        ]);
        $availableGraduations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view("admin/graduations/promote", ["student" => $student, "availableGraduations" => $availableGraduations]);
    }

    public function promoteStudent(): void
    {
        // Only President or Sensei can promote
        if (!Auth::authorize(['admin', 'sensei'])) {
            header('Location: /fgkirs-admin/users');
            exit;
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        $newGraduationId = (int) ($_POST['graduation_id'] ?? 0);
        $examScore = $_POST['exam_score'] ?? null;
        $notes = $_POST['notes'] ?? '';
        $senseiId = Auth::id();

        // Get student profile
        $sql = "SELECT sp.*, u.dojo_id, g.order_rank as current_rank
                FROM student_profiles sp
                JOIN users u ON sp.user_id = u.id
                JOIN graduations g ON sp.current_graduation_id = g.id
                WHERE sp.user_id = :user_id";

        $stmt = $this->db->query($sql, ['user_id' => $userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profile) {
            $_SESSION['error'] = 'Perfil de estudante não encontrado.';
            header("Location: /fgkirs-admin/users/edit/$userId");
            exit;
        }

        // Authorization: Sensei can only promote students from their dojo
        if (Auth::isSensei() && $profile['dojo_id'] !== Auth::dojoId()) {
            $_SESSION['error'] = 'Você só pode promover alunos do seu dojo.';
            header("Location: /fgkirs-admin/users");
            exit;
        }

        // Validate new graduation
        $sql = "SELECT * FROM graduations WHERE id = :grad_id AND style_id = :style_id";
        $stmt = $this->db->query($sql, [
            'grad_id' => $newGraduationId,
            'style_id' => $profile['style_id']
        ]);
        $newGraduation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$newGraduation) {
            $_SESSION['error'] = 'Graduação inválida.';
            header("Location: /fgkirs-admin/graduations/promote/$userId");
            exit;
        }

        // Validate progression (must be next level)
        if ($newGraduation['order_rank'] <= $profile['current_rank']) {
            $_SESSION['error'] = 'A nova graduação deve ser superior à atual.';
            header("Location: /fgkirs-admin/graduations/promote/$userId");
            exit;
        }

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // Update student profile
            $sql = "UPDATE student_profiles 
                    SET current_graduation_id = :new_grad_id, updated_at = NOW()
                    WHERE id = :profile_id";

            $this->db->query($sql, [
                'new_grad_id' => $newGraduationId,
                'profile_id' => $profile['id']
            ]);

            // Record in graduation history
            $sql = "INSERT INTO graduation_history 
                    (student_profile_id, graduation_id, promoted_by_sensei_id, 
                     promotion_date, exam_score, notes)
                    VALUES (:profile_id, :grad_id, :sensei_id, CURDATE(), :score, :notes)";

            $this->db->query($sql, [
                'profile_id' => $profile['id'],
                'grad_id' => $newGraduationId,
                'sensei_id' => $senseiId,
                'score' => $examScore,
                'notes' => $notes
            ]);

            $this->db->commit();

            $_SESSION['success'] = 'Aluno promovido com sucesso!';
            header("Location: /fgkirs-admin/graduations/history/$userId");
            exit;

        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('Promotion error: ' . $e->getMessage());
            $_SESSION['error'] = 'Erro ao promover aluno. Tente novamente.';
            header("Location: /fgkirs-admin/graduations/promote/$userId");
            exit;
        }
    }

    /**
     * Get students ready for promotion (by time requirements)
     */
    public function readyForPromotion(): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            header('Location: /login.php');
            exit;
        }

        // Get students who meet time requirements for next belt
        $sql = "SELECT u.name, u.id as user_id, sp.registration_number,
                       g.belt_name as current_belt, g.belt_color,
                       mas.name as style_name,
                       gh.promotion_date as last_promotion,
                       DATEDIFF(CURDATE(), gh.promotion_date) as days_since_promotion,
                       next_g.belt_name as next_belt,
                       next_g.minimum_time_months * 30 as required_days
                FROM student_profiles sp
                JOIN users u ON sp.user_id = u.id
                JOIN martial_arts_styles mas ON sp.style_id = mas.id
                JOIN graduations g ON sp.current_graduation_id = g.id
                JOIN graduations next_g ON next_g.style_id = g.style_id 
                     AND next_g.order_rank = g.order_rank + 1
                LEFT JOIN (
                    SELECT student_profile_id, MAX(promotion_date) as promotion_date
                    FROM graduation_history
                    GROUP BY student_profile_id
                ) gh ON gh.student_profile_id = sp.id
                WHERE sp.status = 'active'
                AND DATEDIFF(CURDATE(), COALESCE(gh.promotion_date, sp.created_at)) >= 
                    (next_g.minimum_time_months * 30)";

        // Filter by dojo if sensei
        if (Auth::isSensei()) {
            $sql .= " AND u.dojo_id = :dojo_id";
            $params = ['dojo_id' => Auth::dojoId()];
        } else {
            $params = [];
        }

        $sql .= " ORDER BY days_since_promotion DESC";

        $stmt = $this->db->query($sql, $params);
        $readyStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view("admin/graduations/ready", ["readyStudents" => $readyStudents]);
    }

    /** Nível comum entre estilos (1 a 20); vazio ou fora do intervalo vira NULL. */
    private function levelFromPost(): ?int
    {
        $level = (int) ($_POST['level'] ?? 0);

        return $level >= 1 && $level <= 20 ? $level : null;
    }
}
