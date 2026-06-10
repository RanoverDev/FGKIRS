<?php

namespace Helpers;

/**
 * Csrf Class - Proteção contra ataques CSRF (Cross-Site Request Forgery)
 */
class Csrf
{
    /**
     * Garante que a sessão está ativa
     */
    private static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Gera um token CSRF e armazena na sessão
     */
    public static function token(): string
    {
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Valida o token CSRF fornecido
     */
    public static function validate(?string $token): bool
    {
        self::startSession();
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Retorna o campo hidden HTML com o token CSRF
     */
    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::token() . '">';
    }
}
