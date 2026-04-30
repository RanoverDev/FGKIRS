<?php

namespace Models;

use Core\Database;
use PDO;

class Post
{
    public static function getLatest(int $limit = 3): array
    {
        try {
            $db  = Database::getInstance();
            $sql = "SELECT p.id, p.title, p.content, p.featured_image,
                           p.published_at, p.created_at,
                           c.name AS category_name
                    FROM posts p
                    LEFT JOIN categories c ON c.id = p.category_id
                    WHERE p.type = 'news'
                      AND p.published_at IS NOT NULL
                      AND p.published_at <= NOW()
                    ORDER BY p.published_at DESC
                    LIMIT :limit";

            $stmt = $db->getConnection()->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Post::getLatest error: ' . $e->getMessage());
            return [];
        }
    }
}
