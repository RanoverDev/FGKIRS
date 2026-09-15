<?php

namespace Models;

use Core\Database;
use Helpers\Auth;
use Helpers\Slugify;
use PDO;

/**
 * Campeonato: o evento de disputa de kata e kumite criado pelo Presidente.
 *
 * Nao confundir com posts.type = 'event', que e a Agenda de Eventos (conteudo
 * editorial do site publico). Os dois podem ser ligados por post_id.
 */
class Championship
{
    public const STATUSES = [
        'draft'     => 'Rascunho',
        'published' => 'Publicado',
        'closed'    => 'Encerrado',
    ];

    public const REGISTRATION_STATES = [
        'draft'     => 'Rascunho',
        'scheduled' => 'Inscrições em breve',
        'open'      => 'Inscrições abertas',
        'closed'    => 'Inscrições encerradas',
    ];

    public static function all(bool $onlyVisible = false): array
    {
        $sql = "SELECT c.*, u.name AS created_by_name
                FROM championships c
                LEFT JOIN users u ON u.id = c.created_by";

        if ($onlyVisible) {
            $sql .= " WHERE c.status <> 'draft'";
        }

        $sql .= " ORDER BY c.event_date DESC, c.id DESC";

        try {
            return Database::getInstance()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Championship::all error: ' . $e->getMessage());
            return [];
        }
    }

    public static function find(int $id): ?array
    {
        try {
            $row = Database::getInstance()->query(
                "SELECT * FROM championships WHERE id = :id LIMIT 1",
                ['id' => $id]
            )->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        } catch (\Exception $e) {
            error_log('Championship::find error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Campeonato acessivel ao usuario logado. Rascunho so existe para o
     * Presidente; para o sensei o evento simplesmente nao existe.
     */
    public static function findForCurrentUser(int $id): ?array
    {
        $championship = self::find($id);

        if (!$championship) {
            return null;
        }

        if ($championship['status'] === 'draft' && !Auth::isAdmin()) {
            return null;
        }

        return $championship;
    }

    public static function registrationState(array $championship): string
    {
        if ($championship['status'] === 'draft') {
            return 'draft';
        }

        if ($championship['status'] === 'closed') {
            return 'closed';
        }

        $now = time();

        if ($now < strtotime($championship['registration_start'])) {
            return 'scheduled';
        }

        if ($now > strtotime($championship['registration_end'])) {
            return 'closed';
        }

        return 'open';
    }

    public static function isRegistrationOpen(array $championship): bool
    {
        return self::registrationState($championship) === 'open';
    }

    /**
     * Trava de escrita do modulo. Roda em TODO POST/DELETE, nao apenas
     * escondendo o botao na tela. O Presidente passa mesmo fora do prazo para
     * poder corrigir inscricoes, e a tela avisa que ele esta nesse modo.
     */
    public static function assertRegistrationOpen(array $championship): void
    {
        if (Auth::isAdmin() || self::isRegistrationOpen($championship)) {
            return;
        }

        http_response_code(403);
        $_SESSION['error'] = 'As inscrições deste evento estão encerradas.';
        header('Location: /fgkirs-admin/championships/' . (int) $championship['id'] . '/athletes');
        exit;
    }

    public static function create(array $data, int $userId): int
    {
        $db = Database::getInstance();

        $params = self::sanitize($data);
        $params['slug'] = Slugify::unique(
            $params['title'],
            fn(string $slug) => (bool) $db->query(
                "SELECT 1 FROM championships WHERE slug = :slug LIMIT 1",
                ['slug' => $slug]
            )->fetchColumn()
        );
        $params['created_by'] = $userId;

        $db->query(
            "INSERT INTO championships
                (title, slug, description, location, event_date,
                 registration_start, registration_end, status, post_id, created_by)
             VALUES
                (:title, :slug, :description, :location, :event_date,
                 :registration_start, :registration_end, :status, :post_id, :created_by)",
            $params
        );

        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $params = self::sanitize($data);
        $params['id'] = $id;

        Database::getInstance()->query(
            "UPDATE championships SET
                title = :title, description = :description, location = :location,
                event_date = :event_date, registration_start = :registration_start,
                registration_end = :registration_end, status = :status, post_id = :post_id
             WHERE id = :id",
            $params
        );
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->query(
            "DELETE FROM championships WHERE id = :id",
            ['id' => $id]
        );
    }

    /** Totais do evento inteiro, para o painel do Presidente. */
    public static function totals(int $id): array
    {
        try {
            $db = Database::getInstance();

            return [
                'athletes' => (int) $db->query(
                    "SELECT COUNT(*) FROM championship_athletes WHERE championship_id = :id",
                    ['id' => $id]
                )->fetchColumn(),
                'entries' => (int) $db->query(
                    "SELECT COUNT(*) FROM championship_entries e
                     JOIN championship_athletes a ON a.id = e.championship_athlete_id
                     WHERE a.championship_id = :id",
                    ['id' => $id]
                )->fetchColumn(),
                'teams' => (int) $db->query(
                    "SELECT COUNT(*) FROM championship_teams WHERE championship_id = :id",
                    ['id' => $id]
                )->fetchColumn(),
                'dojos' => (int) $db->query(
                    "SELECT COUNT(DISTINCT dojo_id) FROM championship_athletes WHERE championship_id = :id",
                    ['id' => $id]
                )->fetchColumn(),
                'referees' => (int) $db->query(
                    "SELECT COUNT(*) FROM championship_referees WHERE championship_id = :id",
                    ['id' => $id]
                )->fetchColumn(),
            ];
        } catch (\Exception $e) {
            error_log('Championship::totals error: ' . $e->getMessage());
            return ['athletes' => 0, 'entries' => 0, 'teams' => 0, 'dojos' => 0, 'referees' => 0];
        }
    }

    /** Um resumo por dojo participante, para agrupar o painel do Presidente. */
    public static function totalsByDojo(int $id): array
    {
        try {
            return Database::getInstance()->query(
                "SELECT d.id, d.name, d.city, d.logo,
                        COUNT(DISTINCT a.id) AS athlete_count,
                        COUNT(DISTINCT e.id) AS entry_count,
                        (SELECT COUNT(*) FROM championship_teams t
                          WHERE t.championship_id = :id_teams AND t.dojo_id = d.id) AS team_count
                 FROM championship_athletes a
                 JOIN dojos d ON d.id = a.dojo_id
                 LEFT JOIN championship_entries e ON e.championship_athlete_id = a.id
                 WHERE a.championship_id = :id
                 GROUP BY d.id, d.name, d.city, d.logo
                 ORDER BY d.name ASC",
                ['id' => $id, 'id_teams' => $id]
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Championship::totalsByDojo error: ' . $e->getMessage());
            return [];
        }
    }

    /** Posts da Agenda de Eventos, para amarrar o campeonato a materia do site. */
    public static function agendaPosts(): array
    {
        try {
            return Database::getInstance()->query(
                "SELECT id, title, event_date FROM posts
                 WHERE type = 'event'
                 ORDER BY event_date DESC
                 LIMIT 60"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Championship::agendaPosts error: ' . $e->getMessage());
            return [];
        }
    }

    private static function sanitize(array $data): array
    {
        $start = trim((string) ($data['registration_start'] ?? ''));
        $end   = trim((string) ($data['registration_end'] ?? ''));

        return [
            'title'              => trim((string) ($data['title'] ?? '')),
            'description'        => trim((string) ($data['description'] ?? '')) ?: null,
            'location'           => trim((string) ($data['location'] ?? '')) ?: null,
            'event_date'         => trim((string) ($data['event_date'] ?? '')),
            'registration_start' => str_replace('T', ' ', $start),
            'registration_end'   => str_replace('T', ' ', $end),
            'status'             => isset(self::STATUSES[$data['status'] ?? '']) ? $data['status'] : 'draft',
            'post_id'            => ($data['post_id'] ?? '') !== '' ? (int) $data['post_id'] : null,
        ];
    }
}
