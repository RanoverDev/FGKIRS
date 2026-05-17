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

    public static function getPaginatedNews(int $limit = 20, int $offset = 0): array
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
                    LIMIT :limit OFFSET :offset";

            $stmt = $db->getConnection()->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Post::getPaginatedNews error: ' . $e->getMessage());
            return [];
        }
    }

    public static function getLivePost(): ?array
    {
        try {
            $db = Database::getInstance();
            $sql = "SELECT id, title, content, video_url, published_at
                    FROM posts
                    WHERE type = 'live'
                      AND status = 'published'
                      AND published_at IS NOT NULL
                      AND published_at <= NOW()
                    ORDER BY published_at DESC
                    LIMIT 1";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            error_log('Post::getLivePost error: ' . $e->getMessage());
            return null;
        }
    }

    public static function getById(int $id): ?array
    {
        try {
            $db = Database::getInstance();
            $sql = "SELECT * FROM posts WHERE id = :id";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            error_log('Post::getById error: ' . $e->getMessage());
            return null;
        }
    }
}
