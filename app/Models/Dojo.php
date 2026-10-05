<?php

namespace Models;

use Core\Database;
use PDO;

class Dojo
{
    public static function getAllActive(): array
    {
        try {
            $db = Database::getInstance();

            $dojos = $db->query(
                "SELECT d.id, d.name, d.city, d.state,
                        d.address, d.phone, d.phone_whatsapp, d.website, d.logo,
                        d.instagram, d.facebook,
                        u.name AS sensei_name,
                        (SELECT COUNT(*) FROM users
                         WHERE dojo_id = d.id AND role NOT IN ('admin','sensei')) AS student_count
                 FROM dojos d
                 LEFT JOIN users u ON u.id = d.sensei_id
                 ORDER BY d.city ASC, d.name ASC"
            )->fetchAll(PDO::FETCH_ASSOC);

            if (empty($dojos)) return [];

            $dojoIds = array_column($dojos, 'id');
            $in      = implode(',', array_map('intval', $dojoIds));

            // All senseis per dojo with graduation
            $senseis = $db->query(
                "SELECT u.id, u.name, u.dojo_id, g.name AS graduation_name
                 FROM users u
                 LEFT JOIN athlete_profiles ap ON ap.user_id = u.id
                 LEFT JOIN graduations g ON ap.graduation_id = g.id
                 WHERE u.role = 'sensei' AND u.dojo_id IN ($in)
                 ORDER BY u.dojo_id, u.name"
            )->fetchAll(PDO::FETCH_ASSOC);

            // Distinct styles per dojo
            $styles = $db->query(
                "SELECT u.dojo_id, ms.name AS style_name
                 FROM users u
                 JOIN athlete_profiles ap ON ap.user_id = u.id
                 JOIN martial_arts_styles ms ON ap.style_id = ms.id
                 WHERE u.dojo_id IN ($in)
                 GROUP BY u.dojo_id, ms.id
                 ORDER BY u.dojo_id, ms.name"
            )->fetchAll(PDO::FETCH_ASSOC);

            // Index by dojo_id
            $senseisByDojo = [];
            foreach ($senseis as $s) {
                $senseisByDojo[$s['dojo_id']][] = $s;
            }
            $stylesByDojo = [];
            foreach ($styles as $s) {
                $stylesByDojo[$s['dojo_id']][] = $s['style_name'];
            }

            foreach ($dojos as &$d) {
                $d['senseis'] = $senseisByDojo[$d['id']] ?? [];
                $d['styles']  = $stylesByDojo[$d['id']] ?? [];
            }

            return $dojos;
        } catch (\Exception $e) {
            error_log('Dojo::getAllActive error: ' . $e->getMessage());
            return [];
        }
    }

    /** Dojo com atleta, equipe ou árbitro inscrito em algum evento. */
    public static function hasChampionshipRegistrations(int $id): bool
    {
        return (bool) Database::getInstance()->query(
            "SELECT EXISTS(SELECT 1 FROM championship_athletes WHERE dojo_id = :a)
                 OR EXISTS(SELECT 1 FROM championship_teams WHERE dojo_id = :t)
                 OR EXISTS(SELECT 1 FROM championship_referees WHERE dojo_id = :r)",
            ['a' => $id, 't' => $id, 'r' => $id]
        )->fetchColumn();
    }
}
