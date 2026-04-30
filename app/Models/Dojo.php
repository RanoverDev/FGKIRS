<?php

namespace Models;

use Core\Database;
use PDO;

class Dojo
{
    public static function getAllActive(): array
    {
        try {
            $db  = Database::getInstance();
            $sql = "SELECT d.id, d.name, d.city, d.state,
                           d.address, d.phone, d.website, d.logo,
                           u.name AS sensei_name
                    FROM dojos d
                    LEFT JOIN users u ON u.id = d.sensei_id
                    ORDER BY d.city ASC, d.name ASC";

            return $db->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Dojo::getAllActive error: ' . $e->getMessage());
            return [];
        }
    }
}
