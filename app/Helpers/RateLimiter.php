<?php

namespace Helpers;

use Core\Database;
use PDO;

/**
 * RateLimiter Class - Proteção contra força bruta (brute force attacks)
 */
class RateLimiter
{
    private const MAX_ATTEMPTS = 5;
    private const BAN_TIME_MINUTES = 10;

    /**
     * Obtém o endereço de IP do cliente de forma segura
     */
    private static function getIpAddress(): string
    {
        return $_SERVER['HTTP_CLIENT_IP'] 
            ?? $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? '0.0.0.0';
    }

    /**
     * Registra uma tentativa falha de login
     */
    public static function registerAttempt(string $email): void
    {
        $db = Database::getInstance();
        $ip = self::getIpAddress();
        
        $sql = "INSERT INTO login_attempts (ip_address, email) VALUES (:ip, :email)";
        $db->query($sql, [
            ':ip' => $ip,
            ':email' => $email
        ]);
    }

    /**
     * Limpa as tentativas de login para um determinado e-mail ou IP após login bem-sucedido
     */
    public static function clearAttempts(string $email): void
    {
        $db = Database::getInstance();
        $ip = self::getIpAddress();
        
        $sql = "DELETE FROM login_attempts WHERE ip_address = :ip OR email = :email";
        $db->query($sql, [
            ':ip' => $ip,
            ':email' => $email
        ]);
    }

    /**
     * Verifica se o IP ou e-mail está bloqueado por excesso de tentativas
     */
    public static function isBlocked(string $email): bool
    {
        $db = Database::getInstance();
        $ip = self::getIpAddress();
        
        $sql = "SELECT COUNT(*) as total FROM login_attempts 
                WHERE (ip_address = :ip OR email = :email) 
                AND attempted_at > DATE_SUB(NOW(), INTERVAL :minutes MINUTE)";
                
        $stmt = $db->query($sql, [
            ':ip' => $ip,
            ':email' => $email,
            ':minutes' => self::BAN_TIME_MINUTES
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($result['total'] ?? 0) >= self::MAX_ATTEMPTS;
    }
}
