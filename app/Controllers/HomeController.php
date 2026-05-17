<?php

namespace Controllers;

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
        } catch (\Exception $e) {
            error_log('HomeController error: ' . $e->getMessage());
            $news = $dojos = $events = [];
            $profile = [];
            $livePost = null;
        }

        $this->view('home', [
            'news' => $news,
            'dojos' => $dojos,
            'events' => $events,
            'profile' => $profile,
            'livePost' => $livePost,
        ]);
    }

    public function about(): void
    {
        $profile = FederationProfile::get();
        $this->view('about', ['profile' => $profile]);
    }

    public function showNews(int $id): void
    {
        $post = Post::getById($id);
        if (!$post || $post['type'] !== 'news') {
            header("HTTP/1.0 404 Not Found");
            echo "Notícia não encontrada.";
            exit;
        }
        $profile = FederationProfile::get();
        $this->view('news/show', ['post' => $post, 'profile' => $profile]);
    }

    public function showEvent(int $id): void
    {
        $post = Post::getById($id); // Events are in posts table
        if (!$post || $post['type'] !== 'event') {
            header("HTTP/1.0 404 Not Found");
            echo "Evento não encontrado.";
            exit;
        }
        $profile = FederationProfile::get();
        $this->view('events/show', ['post' => $post, 'profile' => $profile]);
    }

    public function newsIndex(): void
    {
        $profile = FederationProfile::get();
        $this->view('news/index', ['profile' => $profile]);
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
