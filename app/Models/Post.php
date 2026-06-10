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
            return $db->query(
                "SELECT id, slug, title, content, type, featured_image, published_at, created_at
                 FROM posts
                 WHERE type = 'news' AND status = 'published'
                   AND published_at IS NOT NULL AND published_at <= NOW()
                 ORDER BY published_at DESC
                 LIMIT $limit"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Post::getLatest error: ' . $e->getMessage());
            return [];
        }
    }

    public static function getPaginatedNews(int $limit = 20, int $offset = 0): array
    {
        try {
            $db = Database::getInstance();
            return $db->query(
                "SELECT id, slug, title, content, type, featured_image, published_at, created_at
                 FROM posts
                 WHERE type = 'news' AND status = 'published'
                   AND published_at IS NOT NULL AND published_at <= NOW()
                 ORDER BY published_at DESC
                 LIMIT $limit OFFSET $offset"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Post::getPaginatedNews error: ' . $e->getMessage());
            return [];
        }
    }

    public static function getLivePost(): ?array
    {
        try {
            $db = Database::getInstance();
            return $db->query(
                "SELECT id, title, content, video_url, published_at
                 FROM posts
                 WHERE type = 'live' AND status = 'published'
                   AND published_at IS NOT NULL AND published_at <= NOW()
                 ORDER BY published_at DESC
                 LIMIT 1"
            )->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            error_log('Post::getLivePost error: ' . $e->getMessage());
            return null;
        }
    }

    private static function withAuthorSql(): string
    {
        return "SELECT p.*,
                    CASE
                        WHEN u.role = 'admin' THEN 'Comunicação FGKIRS'
                        ELSE CONCAT(COALESCE(d.name,''), IF(d.city IS NOT NULL AND d.city != '', CONCAT(' / ', d.city), ''))
                    END AS author_display
                FROM posts p
                JOIN users u ON p.author_id = u.id
                LEFT JOIN dojos d ON u.dojo_id = d.id";
    }

    public static function getById(int $id): ?array
    {
        try {
            $db = Database::getInstance();
            return $db->query(
                self::withAuthorSql() . " WHERE p.id = :id",
                ['id' => $id]
            )->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            error_log('Post::getById error: ' . $e->getMessage());
            return null;
        }
    }

    public static function getBySlug(string $slug): ?array
    {
        try {
            $db = Database::getInstance();
            return $db->query(
                self::withAuthorSql() . " WHERE p.slug = :slug",
                ['slug' => $slug]
            )->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            error_log('Post::getBySlug error: ' . $e->getMessage());
            return null;
        }
    }

    public static function getImages(int $postId): array
    {
        try {
            $db = Database::getInstance();
            return $db->query(
                "SELECT * FROM post_images WHERE post_id = :id ORDER BY is_featured DESC, created_at ASC",
                ['id' => $postId]
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Post::getImages error: ' . $e->getMessage());
            return [];
        }
    }
}
