<?php

namespace Controllers;

use Models\Gallery;
use Models\Post;
use Models\Dojo;
use Models\Event;
use Models\FederationProfile;

class HomeController extends Controller
{
    public function underConstruction(): void
    {
        $this->view('under_construction');
    }

    public function index(): void
    {
        try {
            $news = Post::getLatest(3);
            $dojos = Dojo::getAllActive();
            $events = Event::getUpcoming(5);
            $profile = FederationProfile::get();
            $livePost = Post::getLivePost();
            $galleries = Gallery::getLatest(6);
        } catch (\Exception $e) {
            error_log('HomeController error: ' . $e->getMessage());
            $news = $dojos = $events = $galleries = [];
            $profile = [];
            $livePost = null;
        }

        $this->view('home', [
            'news' => $news,
            'dojos' => $dojos,
            'events' => $events,
            'profile' => $profile,
            'livePost' => $livePost,
            'galleries' => $galleries,
        ]);
    }

    public function about(): void
    {
        $profile = FederationProfile::get();
        $board   = $this->loadBoard();
        $this->view('about', [
            'profile'   => $profile,
            'board'     => $board,
            'pageTitle' => 'Quem Somos | FGKIRS',
        ]);
    }

    public function arbitrationRules(): void
    {
        $profile = FederationProfile::get();
        $this->view('arbitration_rules', [
            'profile'   => $profile,
            'pageTitle' => 'Regras de Arbitragem | FGKIRS',
            'pageDesc'  => 'Regulamento oficial de arbitragem da Federação Gaúcha de Karatê Interestilos.',
        ]);
    }

    private function loadBoard(): array
    {
        try {
            $db = \Core\Database::getInstance();
            $rows = $db->query(
                "SELECT
                    bp.id AS position_id, bp.title AS position_title,
                    bp.tier, bp.section, bp.color, bp.sort_order AS pos_sort,
                    ba.id AS assignment_id, ba.user_id,
                    ba.custom_name, ba.custom_info, ba.sort_order AS asgn_sort,
                    u.name AS user_name, u.photo AS user_photo,
                    g.name AS graduation_name,
                    d.city AS dojo_city
                 FROM board_positions bp
                 LEFT JOIN board_assignments ba ON ba.position_id = bp.id
                 LEFT JOIN users u ON ba.user_id = u.id
                 LEFT JOIN athlete_profiles ap ON u.id = ap.user_id
                 LEFT JOIN graduations g ON ap.graduation_id = g.id
                 LEFT JOIN dojos d ON u.dojo_id = d.id
                 ORDER BY bp.sort_order ASC, ba.sort_order ASC"
            )->fetchAll(\PDO::FETCH_ASSOC);

            // Group by position
            $positions = [];
            foreach ($rows as $row) {
                $pid = $row['position_id'];
                if (!isset($positions[$pid])) {
                    $positions[$pid] = [
                        'id'      => $pid,
                        'title'   => $row['position_title'],
                        'tier'    => $row['tier'],
                        'section' => $row['section'],
                        'color'   => $row['color'],
                        'members' => [],
                    ];
                }
                if ($row['assignment_id']) {
                    $positions[$pid]['members'][] = [
                        'name'       => $row['user_name'] ?? $row['custom_name'],
                        'photo'      => $row['user_photo'],
                        'graduation' => $row['graduation_name'] ?? $row['custom_info'],
                        'city'       => $row['dojo_city'],
                    ];
                }
            }
            return array_values($positions);
        } catch (\Exception $e) {
            error_log('HomeController::loadBoard error: ' . $e->getMessage());
            return [];
        }
    }

    public function showNews(string $slug): void
    {
        if (is_numeric($slug)) {
            $post = Post::getById((int) $slug);
            if ($post && !empty($post['slug'])) {
                header("Location: /noticia/{$post['slug']}", true, 301);
                exit;
            }
        } else {
            $post = Post::getBySlug($slug);
        }

        if (!$post || $post['type'] !== 'news') {
            header("HTTP/1.0 404 Not Found");
            echo "Notícia não encontrada.";
            exit;
        }
        $profile = FederationProfile::get();
        $images = Post::getImages($post['id']);
        $this->view('news/show', ['post' => $post, 'images' => $images, 'profile' => $profile]);
    }

    public function showEvent(string $slug): void
    {
        if (is_numeric($slug)) {
            $post = Post::getById((int) $slug);
            if ($post && !empty($post['slug'])) {
                header("Location: /evento/{$post['slug']}", true, 301);
                exit;
            }
        } else {
            $post = Post::getBySlug($slug);
        }

        if (!$post || $post['type'] !== 'event') {
            header("HTTP/1.0 404 Not Found");
            echo "Evento não encontrado.";
            exit;
        }
        $profile = FederationProfile::get();
        $images = Post::getImages($post['id']);
        $prevEvent = Event::getPreviousEvent($post['event_date'], (int) $post['id']);
        $nextEvent = Event::getNextEvent($post['event_date'], (int) $post['id']);
        $this->view('events/show', [
            'post' => $post,
            'images' => $images,
            'profile' => $profile,
            'prevEvent' => $prevEvent,
            'nextEvent' => $nextEvent
        ]);
    }

    public function showGallery(string $slug): void
    {
        if (is_numeric($slug)) {
            $gallery = Gallery::getById((int) $slug);
            if ($gallery && !empty($gallery['slug'])) {
                header("Location: /galeria/{$gallery['slug']}", true, 301);
                exit;
            }
        } else {
            $gallery = Gallery::getBySlug($slug);
        }

        if (!$gallery) {
            header("HTTP/1.0 404 Not Found");
            echo "Galeria não encontrada.";
            exit;
        }

        $images = Gallery::getImages($gallery['id']);
        $profile = FederationProfile::get();
        $this->view('galleries/show', ['gallery' => $gallery, 'images' => $images, 'profile' => $profile]);
    }

    public function newsIndex(): void
    {
        $profile = FederationProfile::get();
        $this->view('news/index', ['profile' => $profile]);
    }

    public function galleriesIndex(): void
    {
        $profile = FederationProfile::get();
        $this->view('galleries/index', ['profile' => $profile]);
    }

    public function eventsIndex(): void
    {
        $profile = FederationProfile::get();
        $this->view('events/index', ['profile' => $profile]);
    }

    public function newsLoadMore(): void
    {
        header('Content-Type: application/json');
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $news = Post::getPaginatedNews($limit, $offset);
        echo json_encode(['data' => $news]);
        exit;
    }

    public function eventsLoadMore(): void
    {
        header('Content-Type: application/json');
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $events = Event::getPaginatedEvents($limit, $offset);
        echo json_encode(['data' => $events]);
        exit;
    }

    public function contact(): void
    {
        $profile = FederationProfile::get();
        $this->view('contact', ['profile' => $profile]);
    }

    public function dojos(): void
    {
        $dojos = Dojo::getAllActive();
        $profile = FederationProfile::get();
        $this->view('dojos', ['dojos' => $dojos, 'profile' => $profile]);
    }

    public function sendContact(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $message = $_POST['message'] ?? '';

        $profile = FederationProfile::get();
        $to = !empty($profile['email']) ? $profile['email'] : 'contato@fgkirs.com.br';

        if (empty($name) || empty($email) || empty($message)) {
            $_SESSION['error'] = 'Por favor, preencha todos os campos do formulário.';
            header('Location: /contato');
            exit;
        }

        $subject = "Novo Contato do Site: $name";
        $headers = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        $body = "Nome: $name\nE-mail: $email\n\nMensagem:\n$message\n";

        if (@mail($to, $subject, $body, $headers)) {
            $_SESSION['success'] = 'Mensagem enviada com sucesso! Retornaremos o mais breve possível.';
        } else {
            $_SESSION['error'] = 'Ocorreu um erro ao enviar sua mensagem. Tente novamente mais tarde.';
        }

        header('Location: /contato');
        exit;
    }
}
