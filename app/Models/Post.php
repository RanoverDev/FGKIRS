<?php

namespace Models;

use Core\Database;
use PDO;

class Post
{
    public static function getLatest(int $limit = 3): array
    {
        try {
            $db = Database::getInstance();
            $sql = "SELECT id, title, content, type, featured_image, published_at, created_at
                    FROM posts
                    WHERE type = 'news'
                      AND status = 'published'
                      AND published_at IS NOT NULL
                      AND published_at <= NOW()
                    ORDER BY published_at DESC
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
