<?php

namespace Models;

use Core\Database;
use PDO;

/**
 * News Model
 * Handles news/posts data from the database
 */
class News
{
    /**
     * Get the latest news posts
     *
     * @param int $limit Number of posts to retrieve
     * @return array Array of news posts
     */
    public static function getLatest(int $limit = 3): array
    {
        try {
            $db = Database::getInstance();

            $sql = "SELECT id, title, content, featured_image, author_id, published_at, created_at
                    FROM posts 
                    WHERE type = 'news' 
                    AND published_at IS NOT NULL 
                    AND published_at <= NOW()
                    ORDER BY published_at DESC 
                    LIMIT :limit";

            $stmt = $db->getConnection()->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('News::getLatest error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single news post by ID
     *
     * @param int $id Post ID
     * @return array|null Post data or null if not found
     */
    public static function getById(int $id): ?array
    {
        try {
            $db = Database::getInstance();

            $sql = "SELECT * FROM posts WHERE id = :id AND type = 'news' LIMIT 1";
            $stmt = $db->query($sql, ['id' => $id]);

            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            return $post ?: null;
        } catch (\Exception $e) {
            error_log('News::getById error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get upcoming events
     *
     * @param int $limit Number of events to retrieve
     * @return array Array of event posts
     */
    public static function getUpcomingEvents(int $limit = 2): array
    {
        try {
            $db = Database::getInstance();

            $sql = "SELECT id, title, event_date, event_location, featured_image
                    FROM posts 
                    WHERE type = 'event' 
                    AND event_date >= CURDATE()
                    ORDER BY event_date ASC 
                    LIMIT :limit";

            $stmt = $db->getConnection()->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('News::getUpcomingEvents error: ' . $e->getMessage());
            return [];
        }
    }
}
