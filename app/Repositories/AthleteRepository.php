<?php

namespace Repositories;

use Core\Database;
use PDO;

/** Leitura de atletas para a inscrição. O cadastro em si (escrita) continua em Models\Athlete. */
class AthleteRepository
{
    private const SELECT = "SELECT a.*, g.belt_name, g.belt_color, g.level AS belt_level, g.is_black_belt,
                                   s.name AS style_name
                            FROM athletes a
                            LEFT JOIN graduations g ON g.id = a.graduation_id
                            LEFT JOIN martial_arts_styles s ON s.id = a.style_id";

    public function find(int $id): ?array
    {
        $row = Database::getInstance()->query(self::SELECT . " WHERE a.id = :id", ['id' => $id])->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** Atletas ativos do dojo, na ordem da grade. */
    public function activeForDojo(int $dojoId): array
    {
        return Database::getInstance()->query(
            self::SELECT . " WHERE a.dojo_id = :dojo AND a.status <> 'inactive' ORDER BY a.full_name ASC",
            ['dojo' => $dojoId]
        )->fetchAll(PDO::FETCH_ASSOC);
    }
}
