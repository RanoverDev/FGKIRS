<?php

namespace Controllers\Admin;

use Core\Database;
use Helpers\Auth;
use Helpers\ImageProcessor;
use Helpers\Mailer;
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
            $sql .= " WHERE d.id = :dojo_id OR d.sensei_id = :user_id";
            $params = [
                'dojo_id' => Auth::dojoId(),
                'user_id' => Auth::id()
            ];
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
        $state = 'RS';
        $senseiId = $_POST['sensei_id'] ?? null;

        // Process logo if uploaded
        $logoFilename = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../public/uploads/dojos';
            $logoFilename = ImageProcessor::process($_FILES['logo'], $uploadDir);
        }

        $phoneWhatsapp = preg_replace('/\D/', '', $_POST['phone_whatsapp'] ?? '') ?: null;
        $instagram     = trim(ltrim($_POST['instagram'] ?? '', '@')) ?: null;
        $facebook      = trim($_POST['facebook'] ?? '') ?: null;

        // Insert dojo
        $sql = "INSERT INTO dojos (name, address, city, state, sensei_id, logo, phone_whatsapp, instagram, facebook, created_at, updated_at)
                VALUES (:name, :address, :city, :state, :sensei_id, :logo, :phone_whatsapp, :instagram, :facebook, NOW(), NOW())";

        $params = [
            'name'           => $name,
            'address'        => $address,
            'city'           => $city,
            'state'          => $state,
            'sensei_id'      => $senseiId,
            'logo'           => $logoFilename,
            'phone_whatsapp' => $phoneWhatsapp,
            'instagram'      => $instagram,
            'facebook'       => $facebook,
        ];

        $this->db->query($sql, $params);

        // Enviar e-mail de boas-vindas para o Sensei responsável
        if ($senseiId) {
            try {
                $stmt = $this->db->query("SELECT name, email FROM users WHERE id = :id", ['id' => $senseiId]);
                $sensei = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($sensei && !empty($sensei['email'])) {
                    $adminLink = BASE_URL . '/login';
                    $resetLink = BASE_URL . '/recuperar-senha';
                    
                    $subject = 'FGKIRS - Seu Dojo foi cadastrado com sucesso!';
                    $logoUrl = BASE_URL . '/assets/images/logo-fgkirs-white.png';
                    $body = "
                        <div style='background-color: #f8fafc; padding: 20px; font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #334155;'>
                            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;'>
                                
                                <!-- Header -->
                                <div style='background-color: #0f172a; padding: 25px; text-align: center; border-bottom: 4px solid #EE302F;'>
                                    <img src='{$logoUrl}' alt='FGKIRS' width='160' style='width: 160px; max-width: 100%; height: auto; display: inline-block;'>
                                </div>

                                <!-- Body Content -->
                                <div style='padding: 40px 30px; background-color: #ffffff;'>
                                    <h2 style='color: #0f172a; font-size: 20px; font-weight: bold; margin-top: 0; margin-bottom: 20px;'>Olá, " . htmlspecialchars($sensei['name']) . "!</h2>
                                    <p style='margin-bottom: 20px;'>Temos a satisfação de informar que o dojo <strong>" . htmlspecialchars($name) . "</strong> foi cadastrado com sucesso no sistema da <strong>Federação Gaúcha de Karatê Interestilos (FGKIRS)</strong> e você foi definido como o Sensei responsável.</p>
                                    
                                    <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                                    
                                    <h3 style='color: #00AB4E; font-size: 16px; font-weight: bold; margin-top: 0; margin-bottom: 10px;'>A Importância da Atualização dos Dados</h3>
                                    <p style='margin-bottom: 20px;'>Manter as informações do seu dojo atualizadas e o cadastro completo dos seus atletas (incluindo graduações e documentos) é essencial para garantir a participação ativa dos alunos nos eventos oficiais, exames de faixas e torneios organizados pela Federação.</p>
                                    
                                    <h3 style='color: #00AB4E; font-size: 16px; font-weight: bold; margin-top: 0; margin-bottom: 10px;'>Publicação de Conteúdo</h3>
                                    <p style='margin-bottom: 25px;'>Como Sensei responsável, você possui autonomia para publicar notícias, eventos ou artigos relacionados ao seu dojo diretamente em nosso portal. Quando publicar materiais externos, lembre-se de adicionar a fonte original correspondente (URL da notícia/artigo) para garantir a veracidade e autoria dos dados.</p>
                                    
                                    <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;'>
                                    
                                    <h3 style='color: #0f172a; font-size: 15px; font-weight: bold; margin-top: 0; margin-bottom: 15px; text-align: center;'>Como Acessar o Painel Administrativo</h3>
                                    <div style='text-align: center; margin-bottom: 25px;'>
                                        <a href='{$adminLink}' style='background-color: #EE302F; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; font-size: 15px;'>Acessar Área Administrativa</a>
                                    </div>
                                    
                                    <p style='margin-bottom: 10px;'><strong>Primeiro acesso ou esqueceu sua senha?</strong></p>
                                    <p style='margin-bottom: 0;'>Caso ainda não possua uma senha definida ou precise redefini-la, acesse o link abaixo e informe seu e-mail cadastrado (<em>" . htmlspecialchars($sensei['email']) . "</em>) para receber as instruções de redefinição:</p>
                                    <p style='text-align: center; margin: 20px 0;'>
                                        <a href='{$resetLink}' style='color: #EE302F; text-decoration: underline; font-weight: bold;'>Definir ou Redefinir Senha</a>
                                    </p>
                                    
                                    <hr style='border: 0; border-top: 1px solid #e2e8f0; margin-top: 25px; margin-bottom: 15px;'>
                                    <p style='font-size: 11px; color: #64748b; margin-bottom: 0; text-align: center;'>Este é um e-mail automático enviado pelo sistema de gerenciamento FGKIRS.</p>
                                </div>

                                <!-- Footer -->
                                <div style='background-color: #f1f5f9; padding: 30px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 12px; color: #475569;'>
                                    <p style='font-weight: bold; color: #1e293b; margin: 0 0 10px 0; text-transform: uppercase;'>Federação Gaúcha de Karatê Interestilos</p>
                                    <p style='margin: 0 0 5px 0;'>Rua João Macluf, 333, Santa Rosa – RS</p>
                                    <p style='margin: 0 0 5px 0;'>Telefone: (55) 3511 2602</p>
                                    <p style='margin: 0 0 5px 0;'>WhatsApp: (55) 9 9988 1447</p>
                                    <p style='margin: 0 0 15px 0;'>E-mail: <a href='mailto:falecom@fgkirs.com.br' style='color: #EE302F; text-decoration: none;'>falecom@fgkirs.com.br</a></p>
                                    <p style='margin: 0; color: #94a3b8;'>&copy; " . date('Y') . " FGKIRS. Todos os direitos reservados.</p>
                                </div>

                            </div>
                        </div>
                    ";
                    
                    Mailer::send($sensei['email'], $subject, $body);
                }
            } catch (\Exception $e) {
                error_log('Error sending dojo creation welcome email: ' . $e->getMessage());
            }
        }

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
        $stmt = $this->db->query($sql, ['id' => $id]);
        $dojo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dojo) {
            header('Location: /fgkirs-admin/dojos');
            exit;
        }

        // Business rule: Sensei can only edit their dojo (assigned via user.dojo_id or dojos.sensei_id)
        if (Auth::isSensei() && $id !== Auth::dojoId() && (int)($dojo['sensei_id'] ?? 0) !== Auth::id()) {
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

        // Get current dojo data
        $stmt = $this->db->query("SELECT logo, sensei_id FROM dojos WHERE id = :id", ['id' => $id]);
        $dojo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dojo) {
            header('Location: /fgkirs-admin/dojos');
            exit;
        }

        // Business rule: Sensei can only update their dojo (assigned via user.dojo_id or dojos.sensei_id)
        if (Auth::isSensei() && $id !== Auth::dojoId() && (int)($dojo['sensei_id'] ?? 0) !== Auth::id()) {
            header('Location: /fgkirs-admin/dojos');
            exit;
        }

        $name = $_POST['name'] ?? '';
        $address = $_POST['address'] ?? '';
        $city = $_POST['city'] ?? '';
        $state = 'RS';
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

        $phoneWhatsapp = preg_replace('/\D/', '', $_POST['phone_whatsapp'] ?? '') ?: null;
        $instagram     = trim(ltrim($_POST['instagram'] ?? '', '@')) ?: null;
        $facebook      = trim($_POST['facebook'] ?? '') ?: null;

        // Update dojo
        $sql = "UPDATE dojos
                SET name = :name, address = :address, city = :city, state = :state,
                    sensei_id = :sensei_id, logo = :logo, phone_whatsapp = :phone_whatsapp,
                    instagram = :instagram, facebook = :facebook,
                    updated_at = NOW()
                WHERE id = :id";

        $params = [
            'name'           => $name,
            'address'        => $address,
            'city'           => $city,
            'state'          => $state,
            'sensei_id'      => $senseiId,
            'logo'           => $logoFilename,
            'phone_whatsapp' => $phoneWhatsapp,
            'instagram'      => $instagram,
            'facebook'       => $facebook,
            'id'             => $id,
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
