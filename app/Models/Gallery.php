<?php

namespace Models;

use Core\Database;
use PDO;

class Gallery
{
    public static function getById(int $id): ?array
    {
        try {
            $db = Database::getInstance();
            return $db->query(
                "SELECT * FROM galleries WHERE id = :id AND status = 'published'",
                ['id' => $id]
            )->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            error_log('Gallery::getById error: ' . $e->getMessage());
            return null;
        }
    }

    public static function getBySlug(string $slug): ?array
    {
        try {
            $db = Database::getInstance();
            return $db->query(
                "SELECT * FROM galleries WHERE slug = :slug AND status = 'published'",
                ['slug' => $slug]
            )->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            error_log('Gallery::getBySlug error: ' . $e->getMessage());
            return null;
        }
    }

    public static function getImages(int $galleryId): array
    {
        try {
            $db = Database::getInstance();
            return $db->query(
                "SELECT * FROM gallery_images WHERE gallery_id = :id ORDER BY is_cover DESC, created_at ASC",
                ['id' => $galleryId]
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Gallery::getImages error: ' . $e->getMessage());
            return [];
        }
    }
}
