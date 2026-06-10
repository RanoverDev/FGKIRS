<?php

namespace Controllers\Auth;

use Controllers\Controller;
use Helpers\Auth;
use Helpers\Csrf;
use Helpers\RateLimiter;
use Helpers\Mailer;
use Core\Database;
use PDO;

/**
 * LoginController - Handle authentication
 */
class LoginController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm(): void
    {
        // If already authenticated, redirect to dashboard
        if (Auth::check()) {
            $this->redirect('/fgkirs-admin');
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $num1 = rand(2, 9);
        $num2 = rand(2, 9);
        $_SESSION['captcha_num1'] = $num1;
        $_SESSION['captcha_num2'] = $num2;
        $_SESSION['captcha_answer'] = $num1 + $num2;

        $this->view('auth/login');
    }

    /**
     * Handle login attempt
     */
    public function login(): void
    {
        // 1. Validar Token CSRF
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($csrfToken)) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            $this->redirect('/login');
            return;
        }

        // 2. Campo Honeypot (bot check)
        if (!empty($_POST['website'])) {
            $this->redirect('/login');
            return;
        }

        // 3. Captcha Check
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $captchaInput = isset($_POST['captcha']) ? (int)$_POST['captcha'] : -1;
        $captchaExpected = isset($_SESSION['captcha_answer']) ? (int)$_SESSION['captcha_answer'] : null;

        if ($captchaExpected === null || $captchaInput !== $captchaExpected) {
            $_SESSION['error'] = 'Resposta incorreta. Tente novamente.';
            $this->redirect('/login');
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // 4. Rate Limiting Check
        if (RateLimiter::isBlocked($email)) {
            $_SESSION['error'] = 'Muitas tentativas falhas. Tente novamente em 10 minutos.';
            $this->redirect('/login');
            return;
        }

        if (Auth::login($email, $password)) {
            RateLimiter::clearAttempts($email);
            $this->redirect('/fgkirs-admin');
        } else {
            RateLimiter::registerAttempt($email);
            $_SESSION['error'] = 'Email ou senha inválidos.';
            $this->redirect('/login');
        }
    }

    /**
     * Handle logout
     */
    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }

    /**
     * Show forgot password form
     */
    public function showForgotPasswordForm(): void
    {
        if (Auth::check()) {
            $this->redirect('/fgkirs-admin');
        }
        $this->view('auth/forgot_password');
    }

    /**
     * Send password reset link to user
     */
    public function sendResetLink(): void
    {
        // 1. Validar Token CSRF
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($csrfToken)) {
            $_SESSION['error'] = 'Token de segurança inválido.';
            $this->redirect('/recuperar-senha');
            return;
        }

        // 2. Campo Honeypot (bot check)
        if (!empty($_POST['website'])) {
            $this->redirect('/recuperar-senha');
            return;
        }

        $email = trim($_POST['email'] ?? '');

        // 3. Rate limiting check
        if (RateLimiter::isBlocked($email)) {
            $_SESSION['error'] = 'Muitas tentativas. Tente novamente mais tarde.';
            $this->redirect('/recuperar-senha');
            return;
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Por favor, insira um e-mail válido.';
            $this->redirect('/recuperar-senha');
            return;
        }

        $db = Database::getInstance();
        $stmt = $db->query("SELECT id, name FROM users WHERE email = :email AND (status = 'active' OR status = 1)", [':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $db->query(
                "UPDATE users SET password_reset_token = :token, password_reset_expires = :expires WHERE id = :id",
                [
                    ':token' => $token,
                    ':expires' => $expires,
                    ':id' => $user['id']
                ]
            );

            $resetLink = BASE_URL . '/redefinir-senha?token=' . $token;
            
            $subject = 'Recuperação de Senha - FGKIRS';
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
                            <h2 style='color: #0f172a; font-size: 20px; font-weight: bold; margin-top: 0; margin-bottom: 20px;'>Olá, " . htmlspecialchars($user['name']) . "!</h2>
                            <p style='margin-bottom: 20px;'>Recebemos uma solicitação para redefinir a senha da sua conta no sistema de gestão da <strong>FGKIRS (Federação Gaúcha de Karatê Interestilos)</strong>.</p>
                            <p style='margin-bottom: 30px;'>Para redefinir sua senha, clique no botão vermelho abaixo. Este link é válido por 1 hora.</p>
                            
                            <div style='text-align: center; margin-bottom: 30px;'>
                                <a href='{$resetLink}' style='background-color: #EE302F; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; font-size: 15px;'>Redefinir Minha Senha</a>
                            </div>
                            
                            <p style='font-size: 12px; color: #64748b; margin-bottom: 10px;'>Se o botão acima não funcionar, copie e cole o seguinte link no seu navegador:</p>
                            <p style='font-size: 12px; word-break: break-all; margin-bottom: 30px;'><a href='{$resetLink}' style='color: #3b82f6; text-decoration: underline;'>{$resetLink}</a></p>
                            
                            <hr style='border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 20px;'>
                            <p style='font-size: 12px; color: #64748b; margin-bottom: 0;'>Se você não solicitou essa redefinição, nenhuma ação é necessária. Sua senha atual permanecerá segura.</p>
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

            if (Mailer::send($email, $subject, $body)) {
                $_SESSION['success'] = 'Se o e-mail estiver cadastrado, um link de redefinição de senha foi enviado.';
            } else {
                $_SESSION['error'] = 'Ocorreu um erro ao enviar o e-mail. Tente novamente mais tarde.';
                RateLimiter::registerAttempt($email);
            }
        } else {
            $_SESSION['success'] = 'Se o e-mail estiver cadastrado, um link de redefinição de senha foi enviado.';
            usleep(250000); 
        }

        $this->redirect('/login');
    }

    /**
     * Show reset password form
     */
    public function showResetPasswordForm(): void
    {
        if (Auth::check()) {
            $this->redirect('/fgkirs-admin');
        }

        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            $_SESSION['error'] = 'Token de redefinição inválido ou ausente.';
            $this->redirect('/login');
            return;
        }

        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT id FROM users WHERE password_reset_token = :token AND password_reset_expires > NOW() AND (status = 'active' OR status = 1)",
            [':token' => $token]
        );
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['error'] = 'Token de redefinição inválido ou expirado.';
            $this->redirect('/login');
            return;
        }

        $this->view('auth/reset_password', ['token' => $token]);
    }

    /**
     * Process password reset
     */
    public function resetPassword(): void
    {
        $token = $_POST['token'] ?? '';

        // 1. Validar Token CSRF
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($csrfToken)) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            $redirect = !empty($token) ? '/redefinir-senha?token=' . urlencode($token) : '/recuperar-senha';
            $this->redirect($redirect);
            return;
        }

        // 2. Campo Honeypot (bot check)
        if (!empty($_POST['website'])) {
            $this->redirect('/recuperar-senha');
            return;
        }

        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (empty($token)) {
            $_SESSION['error'] = 'Token de redefinição inválido.';
            $this->redirect('/recuperar-senha');
            return;
        }

        $passwordError = null;
        if (!Auth::validatePasswordStrength($password, $passwordError)) {
            $_SESSION['error'] = $passwordError;
            $this->redirect('/redefinir-senha?token=' . urlencode($token));
            return;
        }

        if ($password !== $passwordConfirm) {
            $_SESSION['error'] = 'As senhas não coincidem.';
            $this->redirect('/redefinir-senha?token=' . urlencode($token));
            return;
        }

        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT id FROM users WHERE password_reset_token = :token AND password_reset_expires > NOW() AND (status = 'active' OR status = 1)",
            [':token' => $token]
        );
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['error'] = 'Token de redefinição inválido ou expirado. Solicite um novo link.';
            $this->redirect('/recuperar-senha');
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $db->query(
            "UPDATE users SET password = :password, password_reset_token = NULL, password_reset_expires = NULL WHERE id = :id",
            [
                ':password' => $passwordHash,
                ':id' => $user['id']
            ]
        );

        $_SESSION['success'] = 'Senha redefinida com sucesso. Faça login com suas novas credenciais.';
        $this->redirect('/login');
    }
}
