<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Database;
use Helpers\Auth;
use Helpers\ImageProcessor;
use PDO;

/**
 * PostController - News & Events Management
 * Handles creation and management of posts
 */
class PostController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Display all posts
     */
    public function index(): void
    {
        if (!Auth::check()) {
            header('Location: /login.php');
            exit;
        }

        $sql = "SELECT p.*, u.name as author_name 
                FROM posts p 
                JOIN users u ON p.author_id = u.id 
                ORDER BY p.created_at DESC";

        $stmt = $this->db->query($sql);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view("admin/posts/index", ["posts" => $posts]);
    }

    /**
     * Show create form
     */
    public function create(): void
    {
        // Colaborador, Sensei, and Admin can create
        if (!Auth::authorize(['admin', 'sensei', 'colaborador'])) {
            header('Location: /fgkirs-admin/dashboard');
            exit;
        }

        $this->view("admin/posts/form");
    }

    /**
     * Store new post
     */
    public function store(): void
    {
        if (!Auth::authorize(['admin', 'sensei', 'colaborador'])) {
            header('Location: /fgkirs-admin/dashboard');
            exit;
        }

        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $type = $_POST['type'] ?? 'news';
        $eventDate = $_POST['event_date'] ?? null;
        $eventLocation = $_POST['event_location'] ?? null;

        // Validate
        if (empty($title) || empty($content)) {
            $_SESSION['error'] = 'Título e conteúdo são obrigatórios.';
            header('Location: /fgkirs-admin/posts/create');
            exit;
        }

        // Process featured image
        $featuredImage = null;
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../public/uploads/posts';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $featuredImage = ImageProcessor::process($_FILES['featured_image'], $uploadDir);
        }

        // Insert post
        $sql = "INSERT INTO posts (title, content, featured_image, type, author_id, event_date, event_location, published_at)
                VALUES (:title, :content, :image, :type, :author_id, :event_date, :event_location, NOW())";

        $this->db->query($sql, [
            'title' => $title,
            'content' => $content,
            'image' => $featuredImage,
            'type' => $type,
            'author_id' => Auth::id(),
            'event_date' => $eventDate,
            'event_location' => $eventLocation
        ]);

        $_SESSION['success'] = 'Post criado com sucesso!';
        header('Location: /fgkirs-admin/posts');
        exit;
    }

    /**
     * Show edit form
     */
    public function edit(int $id): void
    {
        if (!Auth::check()) {
            header('Location: /login.php');
            exit;
        }

        $sql = "SELECT * FROM posts WHERE id = :id";
        $stmt = $this->db->query($sql, ['id' => $id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            header('Location: /fgkirs-admin/posts');
            exit;
        }

        // Check permission: Only Admin/Sensei can edit others' posts
        if ($post['author_id'] !== Auth::id() && !Auth::authorize(['admin', 'sensei'])) {
            $_SESSION['error'] = 'Você não tem permissão para editar este post.';
            header('Location: /fgkirs-admin/posts');
            exit;
        }

        $this->view("admin/posts/form", ["post" => $post]);
    }

    /**
     * Update post
     */
    public function update(int $id): void
    {
        if (!Auth::check()) {
            header('Location: /login.php');
            exit;
        }

        // Get existing post
        $sql = "SELECT * FROM posts WHERE id = :id";
        $stmt = $this->db->query($sql, ['id' => $id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            header('Location: /fgkirs-admin/posts');
            exit;
        }

        // Check permission
        if ($post['author_id'] !== Auth::id() && !Auth::authorize(['admin', 'sensei'])) {
            $_SESSION['error'] = 'Você não tem permissão para editar este post.';
            header('Location: /fgkirs-admin/posts');
            exit;
        }

        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $type = $_POST['type'] ?? 'news';
        $eventDate = $_POST['event_date'] ?? null;
        $eventLocation = $_POST['event_location'] ?? null;

        // Validate
        if (empty($title) || empty($content)) {
            $_SESSION['error'] = 'Título e conteúdo são obrigatórios.';
            header("Location: /fgkirs-admin/posts/edit/$id");
            exit;
        }

        // Process new featured image if uploaded
        $featuredImage = $post['featured_image'];
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../public/uploads/posts';
            $newImage = ImageProcessor::process($_FILES['featured_image'], $uploadDir);
            if ($newImage) {
                // Delete old image
                if ($featuredImage && file_exists($uploadDir . '/' . $featuredImage)) {
                    unlink($uploadDir . '/' . $featuredImage);
                }
                $featuredImage = $newImage;
            }
        }

        // Update post
        $sql = "UPDATE posts 
                SET title = :title, content = :content, featured_image = :image, 
                    type = :type, event_date = :event_date, event_location = :event_location,
                    updated_at = NOW()
                WHERE id = :id";

        $this->db->query($sql, [
            'title' => $title,
            'content' => $content,
            'image' => $featuredImage,
            'type' => $type,
            'event_date' => $eventDate,
            'event_location' => $eventLocation,
            'id' => $id
        ]);

        $_SESSION['success'] = 'Post atualizado com sucesso!';
        header('Location: /fgkirs-admin/posts');
        exit;
    }

    /**
     * Delete post
     */
    public function delete(int $id): void
    {
        if (!Auth::check()) {
            header('Location: /login.php');
            exit;
        }

        // Get post
        $sql = "SELECT * FROM posts WHERE id = :id";
        $stmt = $this->db->query($sql, ['id' => $id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            header('Location: /fgkirs-admin/posts');
            exit;
        }

        // Only Admin and Sensei can delete any post
        if (!Auth::authorize(['admin', 'sensei'])) {
            // Others can only delete their own
            if ($post['author_id'] !== Auth::id()) {
                $_SESSION['error'] = 'Você não tem permissão para deletar este post.';
                header('Location: /fgkirs-admin/posts');
                exit;
            }
        }

        // Delete image file
        if ($post['featured_image']) {
            $imagePath = __DIR__ . '/../../../public/uploads/posts/' . $post['featured_image'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        // Delete post
        $sql = "DELETE FROM posts WHERE id = :id";
        $this->db->query($sql, ['id' => $id]);

        $_SESSION['success'] = 'Post deletado com sucesso!';
        header('Location: /fgkirs-admin/posts');
        exit;
    }
}
