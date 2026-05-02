<?php

namespace Models;

use Core\Database;
use PDO;

class FederationProfile
{
    public static function get(): array
    {
        try {
            $stmt = Database::getInstance()->query(
                "SELECT * FROM federation_profile WHERE id = 1 LIMIT 1"
            );
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            error_log('FederationProfile::get error: ' . $e->getMessage());
            return [];
        }
    }

    public static function save(array $data): void
    {
        $db = Database::getInstance();
        $db->query(
            "INSERT INTO federation_profile
                (id, whatsapp, phone, email, address, city, state, zip_code, facebook, instagram)
             VALUES (1, :whatsapp, :phone, :email, :address, :city, :state, :zip_code, :facebook, :instagram)
             ON DUPLICATE KEY UPDATE
                whatsapp   = VALUES(whatsapp),
                phone      = VALUES(phone),
                email      = VALUES(email),
                address    = VALUES(address),
                city       = VALUES(city),
                state      = VALUES(state),
                zip_code   = VALUES(zip_code),
                facebook   = VALUES(facebook),
                instagram  = VALUES(instagram)",
            [
                'whatsapp' => $data['whatsapp'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'zip_code' => $data['zip_code'] ?? null,
                'facebook' => $data['facebook'] ?? null,
                'instagram' => $data['instagram'] ?? null,
            ]
        );
    }
}
