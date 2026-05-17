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
            return $db->query(
                "SELECT id, slug, title, event_date, event_location, featured_image
                 FROM posts
                 WHERE type = 'event' AND event_date >= CURDATE()
                 ORDER BY event_date ASC
                 LIMIT $limit"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Event::getUpcoming error: ' . $e->getMessage());
            return [];
        }
    }

    public static function getPaginatedEvents(int $limit = 20, int $offset = 0): array
    {
        try {
            $db = Database::getInstance();
            return $db->query(
                "SELECT id, slug, title, event_date, event_location, featured_image, content, created_at
                 FROM posts
                 WHERE type = 'event'
                 ORDER BY event_date DESC
                 LIMIT $limit OFFSET $offset"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Event::getPaginatedEvents error: ' . $e->getMessage());
            return [];
        }
    }
}
