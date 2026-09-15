<?php

namespace Helpers;

use Core\Database;
use PDO;

/**
 * Resolucao e validacao do dojo do usuario logado.
 *
 * Todo acesso do sensei ao modulo de campeonatos passa por aqui: o dojo nunca
 * pode vir do que o navegador mandou, sempre da sessao. Admin nao tem dojo e
 * enxerga todos.
 */
class DojoScope
{
    private static ?int $cachedSenseiDojoId = null;
    private static bool $resolved = false;

    /**
     * Dojo do sensei logado. Senseis antigos podem ter vinculo apenas em
     * dojos.sensei_id, sem users.dojo_id preenchido.
     */
    public static function senseiDojoId(): ?int
    {
        if (self::$resolved) {
            return self::$cachedSenseiDojoId;
        }

        self::$resolved = true;
        self::$cachedSenseiDojoId = null;

        if (!Auth::check()) {
            return null;
        }

        $dojoId = Auth::dojoId();
        if ($dojoId) {
            self::$cachedSenseiDojoId = (int) $dojoId;
            return self::$cachedSenseiDojoId;
        }

        try {
            $row = Database::getInstance()->query(
                "SELECT id FROM dojos WHERE sensei_id = :sensei_id LIMIT 1",
                ['sensei_id' => Auth::id()]
            )->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                self::$cachedSenseiDojoId = (int) $row['id'];
            }
        } catch (\Exception $e) {
            error_log('DojoScope::senseiDojoId error: ' . $e->getMessage());
        }

        return self::$cachedSenseiDojoId;
    }

    /**
     * Dojo em que a operacao acontece. O admin pode operar em nome de qualquer
     * dojo (parametro), o sensei sempre no proprio.
     */
    public static function resolveDojoId(?int $requested = null): ?int
    {
        if (Auth::isAdmin()) {
            return $requested ?: null;
        }

        return self::senseiDojoId();
    }

    /**
     * Garante que o registro pertence ao dojo do usuario. Chamar em TODA rota
     * que recebe um id na URL, antes de ler ou escrever.
     */
    public static function assertDojo(?int $dojoId): void
    {
        if (Auth::isAdmin()) {
            return;
        }

        $current = self::senseiDojoId();

        if ($current === null || $dojoId === null || (int) $dojoId !== $current) {
            self::deny();
        }
    }

    /**
     * Encerra a requisicao com 403. Nunca devolver o registro de outro dojo,
     * nem redirecionar de um jeito que confirme que ele existe.
     */
    public static function deny(string $message = 'Você não tem permissão para acessar este registro.'): void
    {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">'
            . '<title>Acesso negado – FGKIRS</title></head>'
            . '<body style="font-family:system-ui;padding:48px;text-align:center">'
            . '<h1 style="font-size:20px;margin-bottom:8px">Acesso negado</h1>'
            . '<p style="color:#475569">' . htmlspecialchars($message) . '</p>'
            . '<p><a href="/fgkirs-admin" style="color:#EE302F">Voltar ao painel</a></p>'
            . '</body></html>';
        exit;
    }
}
