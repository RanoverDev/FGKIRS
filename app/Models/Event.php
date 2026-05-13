<?php

namespace Models;

use Core\Database;
use PDO;

class Event
{
    public static function getUpcoming(int $limit = 5): array
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
            error_log('Event::getUpcoming error: ' . $e->getMessage());
            return [];
        }
    }

    public static function getPaginatedEvents(int $limit = 20, int $offset = 0): array
    {
        try {
            $db = Database::getInstance();
            $sql = "SELECT id, title, event_date, event_location, featured_image, content, created_at
                    FROM posts
                    WHERE type = 'event'
                    ORDER BY event_date DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $db->getConnection()->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Event::getPaginatedEvents error: ' . $e->getMessage());
            return [];
        }
    }
}
