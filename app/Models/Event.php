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

    public static function getPreviousEvent(string $eventDate, int $id): ?array
    {
        try {
            $db = Database::getInstance();
            return $db->query(
                "SELECT id, slug, title
                 FROM posts
                 WHERE type = 'event'
                   AND (event_date < :date OR (event_date = :date AND id < :id))
                 ORDER BY event_date DESC, id DESC
                 LIMIT 1",
                ['date' => $eventDate, 'id' => $id]
            )->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            error_log('Event::getPreviousEvent error: ' . $e->getMessage());
            return null;
        }
    }

    public static function getNextEvent(string $eventDate, int $id): ?array
    {
        try {
            $db = Database::getInstance();
            return $db->query(
                "SELECT id, slug, title
                 FROM posts
                 WHERE type = 'event'
                   AND (event_date > :date OR (event_date = :date AND id > :id))
                 ORDER BY event_date ASC, id ASC
                 LIMIT 1",
                ['date' => $eventDate, 'id' => $id]
            )->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            error_log('Event::getNextEvent error: ' . $e->getMessage());
            return null;
        }
    }
}
