<?php

namespace Controllers;

use Models\News;
use Core\Database;

/**
 * HomeController - Public homepage
 */
class HomeController extends Controller
{
    /**
     * Display the homepage with dynamic data
     */
    public function index(): void
    {
        try {
            $db = Database::getInstance();

            // Get latest 3 news posts
            $news = News::getLatest(3);

            // Get upcoming 2 events
            $events = News::getUpcomingEvents(2);

            // Get federation statistics
            $stats = $this->getFederationStats($db);

            // Get all dojos for the locator
            $dojos = $this->getDojos($db);

            // Render the landing page with data
            $this->view('home/index', [
                'news' => $news,
                'events' => $events,
                'stats' => $stats,
                'dojos' => $dojos
            ]);
        } catch (\Exception $e) {
            error_log('HomeController error: ' . $e->getMessage());

            // Fallback to static view if database fails
            $this->view('home/index', [
                'news' => [],
                'events' => [],
                'stats' => ['dojos' => 0, 'athletes' => 0],
                'dojos' => []
            ]);
        }
    }

    /**
     * Get federation statistics
     */
    private function getFederationStats(Database $db): array
    {
        try {
            // Count total dojos
            $dojoCount = $db->query("SELECT COUNT(*) as total FROM dojos")->fetch();

            // Count active athletes (users with role 'aluno' or having student_profiles)
            $athleteCount = $db->query(
                "SELECT COUNT(DISTINCT u.id) as total 
                 FROM users u 
                 LEFT JOIN student_profiles sp ON u.id = sp.user_id 
                 WHERE u.role = 'aluno' OR sp.id IS NOT NULL"
            )->fetch();

            return [
                'dojos' => $dojoCount['total'] ?? 0,
                'athletes' => $athleteCount['total'] ?? 0
            ];
        } catch (\Exception $e) {
            error_log('getFederationStats error: ' . $e->getMessage());
            return ['dojos' => 0, 'athletes' => 0];
        }
    }

    /**
     * Get all dojos with sensei information
     */
    private function getDojos(Database $db): array
    {
        try {
            $sql = "SELECT d.id, d.name, d.city, d.state, d.address, d.phone, d.website,
                           u.name as sensei_name
                    FROM dojos d
                    LEFT JOIN users u ON d.sensei_id = u.id
                    ORDER BY d.city, d.name";

            return $db->query($sql)->fetchAll();
        } catch (\Exception $e) {
            error_log('getDojos error: ' . $e->getMessage());
            return [];
        }
    }
}
