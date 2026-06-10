<?php

namespace Helpers;

use Core\Database;
use PDO;

/**
 * Auth Class - Authentication & Authorization
 * Handles user login, session management, and role-based access control
 */
class Auth
{
    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public static function check(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['user_id']) && isset($_SESSION['user_authenticated']);
    }

    /**
     * Authorize user based on allowed roles
     *
     * @param array $roles Allowed roles (e.g., ['admin', 'sensei'])
     * @return bool
     */
    public static function authorize(array $roles): bool
    {
        if (!self::check()) {
            return false;
        }

        $userRole = $_SESSION['user_role'] ?? null;

        return $userRole && in_array($userRole, $roles, true);
    }

    /**
     * Authenticate user with email and password
     *
     * @param string $email User email
     * @param string $password Plain text password
     * @return bool True on successful login, false otherwise
     */
    public static function login(string $email, string $password): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            $db = Database::getInstance();

            // Query user by email
            $sql = "SELECT id, name, email, password, role, dojo_id 
                    FROM users 
                    WHERE email = :email 
                    LIMIT 1";

            $stmt = $db->query($sql, ['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify user exists and password matches
            if (!$user || !password_verify($password, $user['password'])) {
                return false;
            }

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_dojo_id'] = $user['dojo_id'];
            $_SESSION['user_authenticated'] = true;

            // Regenerate session ID for security
            session_regenerate_id(true);

            return true;
        } catch (\Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Logout current user
     *
     * @return void
     */
    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Clear session data
        $_SESSION = [];

        // Destroy session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Destroy session
        session_destroy();
    }

    /**
     * Get current authenticated user data
     *
     * @return array|null
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'role' => $_SESSION['user_role'] ?? null,
            'dojo_id' => $_SESSION['user_dojo_id'] ?? null,
        ];
    }

    /**
     * Get user ID from session
     *
     * @return int|null
     */
    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get user role from session
     *
     * @return string|null
     */
    public static function role(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }

    /**
     * Get user dojo_id from session
     *
     * @return int|null
     */
    public static function dojoId(): ?int
    {
        return $_SESSION['user_dojo_id'] ?? null;
    }

    /**
     * Check if user is admin
     *
     * @return bool
     */
    public static function isAdmin(): bool
    {
        return self::check() && self::role() === 'admin';
    }

    /**
     * Check if user is sensei
     *
     * @return bool
     */
    public static function isSensei(): bool
    {
        return self::check() && self::role() === 'sensei';
    }

    /**
     * Valida se uma senha é forte:
     * - Mínimo de 8 caracteres
     * - Pelo menos uma letra maiúscula
     * - Pelo menos uma letra minúscula
     * - Pelo menos um número
     * - Pelo menos um caractere especial (ex: @, #, $, etc.)
     *
     * @param string $password
     * @param string|null $errorMsg Retorno da mensagem de erro por referência
     * @return bool
     */
    public static function validatePasswordStrength(string $password, ?string &$errorMsg = null): bool
    {
        if (strlen($password) < 8) {
            $errorMsg = 'A senha deve ter no mínimo 8 caracteres.';
            return false;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errorMsg = 'A senha deve conter pelo menos uma letra maiúscula.';
            return false;
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errorMsg = 'A senha deve conter pelo menos uma letra minúscula.';
            return false;
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errorMsg = 'A senha deve conter pelo menos um número.';
            return false;
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errorMsg = 'A senha deve conter pelo menos um caractere especial (ex: @, #, $, etc.).';
            return false;
        }

        return true;
    }
}
