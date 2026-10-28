<?php

namespace Repositories;

use Core\Database;
use PDO;

/** SQL do certificado de graduação. Regras e permissões ficam em Services\CertificateService. */
class CertificateRepository
{
    public function create(array $data): int
    {
        $db = Database::getInstance();

        $db->query(
            "INSERT INTO athlete_certificates
                (athlete_id, style_id, graduation_id, graduation_label, graduation_date, body_html, issued_by)
             VALUES
                (:athlete_id, :style_id, :graduation_id, :graduation_label, :graduation_date, :body_html, :issued_by)",
            $data
        );

        return (int) $db->lastInsertId();
    }

    public function delete(int $id): void
    {
        Database::getInstance()->query("DELETE FROM athlete_certificates WHERE id = :id", ['id' => $id]);
    }

    /** Certificado com o que a tela de impressão precisa: arte do estilo e atleta (para o escopo do dojo). */
    public function find(int $id): ?array
    {
        $row = Database::getInstance()->query(
            "SELECT c.*, s.name AS style_name, s.certificate_background,
                    a.full_name AS athlete_name, a.dojo_id
             FROM athlete_certificates c
             JOIN martial_arts_styles s ON s.id = c.style_id
             JOIN athletes a ON a.id = c.athlete_id
             WHERE c.id = :id",
            ['id' => $id]
        )->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function forAthlete(int $athleteId): array
    {
        return Database::getInstance()->query(
            "SELECT c.id, c.graduation_label, c.graduation_date, c.created_at,
                    s.name AS style_name, u.name AS issued_by_name
             FROM athlete_certificates c
             JOIN martial_arts_styles s ON s.id = c.style_id
             JOIN users u ON u.id = c.issued_by
             WHERE c.athlete_id = :id
             ORDER BY c.graduation_date DESC, c.id DESC",
            ['id' => $athleteId]
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Dados para montar o texto: a graduação do atleta, o estilo dela (com a arte) e a cidade do dojo. */
    public function context(int $athleteId): ?array
    {
        $row = Database::getInstance()->query(
            "SELECT a.id, a.full_name, a.gender, a.dojo_id, a.graduation_id,
                    g.belt_name, g.style_id,
                    s.name AS style_name, s.certificate_background,
                    d.name AS dojo_name, d.city AS dojo_city, d.state AS dojo_state
             FROM athletes a
             JOIN dojos d ON d.id = a.dojo_id
             LEFT JOIN graduations g ON g.id = a.graduation_id
             LEFT JOIN martial_arts_styles s ON s.id = g.style_id
             WHERE a.id = :id",
            ['id' => $athleteId]
        )->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
