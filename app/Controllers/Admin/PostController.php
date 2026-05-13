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
        if (!Auth::authorize(['admin', 'sensei', 'aluno-colaborador'])) {
            header('Location: /fgkirs-admin/dashboard');
            exit;
        }

        $authorFilter = Auth::isAdmin() ? '' : ' AND p.author_id = :author_id';
        $params = Auth::isAdmin() ? [] : ['author_id' => Auth::id()];

        $sql = "SELECT p.*,
                    CASE
                        WHEN u.role = 'admin' THEN 'Comunicação FGKIRS'
                        ELSE CONCAT(COALESCE(d.name,'—'), IF(d.city IS NOT NULL AND d.city != '', CONCAT(' / ', d.city), ''))
                    END AS author_display
                FROM posts p
                JOIN users u  ON p.author_id = u.id
                LEFT JOIN dojos d ON u.dojo_id = d.id
                WHERE p.type IN ('news', 'event') {$authorFilter}
                ORDER BY p.created_at DESC";

        $stmt = $this->db->query($sql, $params);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view("admin/posts/index", ["posts" => $posts]);
    }

    /**
     * Show create form
     */
    public function create(): void
    {
        // Colaborador, Sensei, and Admin can create
        if (!Auth::authorize(['admin', 'sensei', 'aluno-colaborador'])) {
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
        if (!Auth::authorize(['admin', 'sensei', 'aluno-colaborador'])) {
            header('Location: /fgkirs-admin/dashboard');
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $type = \in_array($_POST['type'] ?? '', ['news', 'event']) ? $_POST['type'] : 'news';
        $eventDate = $_POST['event_date'] ?: null;
        $eventLocation = trim($_POST['event_location'] ?? '') ?: null;
        $status = \in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'published';
        $publishedAt = !empty($_POST['published_at']) ? $_POST['published_at'] : date('Y-m-d H:i:s');

        if (empty($title) || empty($content)) {
            $_SESSION['error'] = 'Título e conteúdo são obrigatórios.';
            header('Location: /fgkirs-admin/posts/create');
            exit;
        }

        $sql = "INSERT INTO posts (title, content, type, author_id, event_date, event_location, status, published_at)
                VALUES (:title, :content, :type, :author_id, :event_date, :event_location, :status, :published_at)";

        $this->db->query($sql, [
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'author_id' => Auth::id(),
            'event_date' => $eventDate,
            'event_location' => $eventLocation,
            'status' => $status,
            'published_at' => $publishedAt,
        ]);

        $postId = (int) $this->db->lastInsertId();

        // Upload initial image if provided
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../public/uploads/posts';
            $filename = ImageProcessor::process($_FILES['featured_image'], $uploadDir);
            if ($filename) {
                $this->db->query(
                    "INSERT INTO post_images (post_id, filename, is_featured) VALUES (:post_id, :filename, 1)",
                    ['post_id' => $postId, 'filename' => $filename]
                );
                $this->db->query(
                    "UPDATE posts SET featured_image = :filename WHERE id = :id",
                    ['filename' => $filename, 'id' => $postId]
                );
            }
        }

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
        if ($post['author_id'] !== Auth::id() && !Auth::isAdmin()) {
            $_SESSION['error'] = 'Você não tem permissão para editar este post.';
            header('Location: /fgkirs-admin/posts');
            exit;
        }

        $stmt2 = $this->db->query(
            "SELECT * FROM post_images WHERE post_id = :id ORDER BY is_featured DESC, created_at ASC",
            ['id' => $id]
        );
        $images = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $this->view("admin/posts/form", ["post" => $post, "images" => $images]);
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
        if ($post['author_id'] !== Auth::id() && !Auth::isAdmin()) {
            $_SESSION['error'] = 'Você não tem permissão para editar este post.';
            header('Location: /fgkirs-admin/posts');
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $type = \in_array($_POST['type'] ?? '', ['news', 'event']) ? $_POST['type'] : 'news';
        $eventDate = $_POST['event_date'] ?: null;
        $eventLocation = trim($_POST['event_location'] ?? '') ?: null;
        $status = \in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'published';
        $publishedAt = !empty($_POST['published_at']) ? $_POST['published_at'] : $post['published_at'];

        if (empty($title) || empty($content)) {
            $_SESSION['error'] = 'Título e conteúdo são obrigatórios.';
            header("Location: /fgkirs-admin/posts/edit/$id");
            exit;
        }

        $sql = "UPDATE posts
                SET title = :title, content = :content,
                    type = :type, event_date = :event_date, event_location = :event_location,
                    status = :status, published_at = :published_at, updated_at = NOW()
                WHERE id = :id";

        $this->db->query($sql, [
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'event_date' => $eventDate,
            'event_location' => $eventLocation,
            'status' => $status,
            'published_at' => $publishedAt,
            'id' => $id,
        ]);

        $_SESSION['success'] = 'Post atualizado com sucesso!';
        header('Location: /fgkirs-admin/posts');
        exit;
    }

    public function addImage(int $postId): void
    {
        $post = $this->getPostOrDeny($postId);
        if (!$post)
            return;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../public/uploads/posts';
            $filename = ImageProcessor::process($_FILES['image'], $uploadDir);
            if ($filename) {
                $countStmt = $this->db->query(
                    "SELECT COUNT(*) FROM post_images WHERE post_id = :id",
                    ['id' => $postId]
                );
                $isFeatured = (int) $countStmt->fetchColumn() === 0 ? 1 : 0;

                $this->db->query(
                    "INSERT INTO post_images (post_id, filename, is_featured) VALUES (:post_id, :filename, :is_featured)",
                    ['post_id' => $postId, 'filename' => $filename, 'is_featured' => $isFeatured]
                );
                if ($isFeatured) {
                    $this->db->query(
                        "UPDATE posts SET featured_image = :filename WHERE id = :id",
                        ['filename' => $filename, 'id' => $postId]
                    );
                }
            }
        }

        header("Location: /fgkirs-admin/posts/edit/$postId");
        exit;
    }

    public function removeImage(int $imageId): void
    {
        $image = $this->getImageOrDeny($imageId);
        if (!$image)
            return;

        $uploadDir = __DIR__ . '/../../../public/uploads/posts';
        ImageProcessor::delete("$uploadDir/{$image['filename']}");
        $this->db->query("DELETE FROM post_images WHERE id = :id", ['id' => $imageId]);

        if ($image['is_featured']) {
            $next = $this->db->query(
                "SELECT id, filename FROM post_images WHERE post_id = :post_id ORDER BY created_at ASC LIMIT 1",
                ['post_id' => $image['post_id']]
            )->fetch(PDO::FETCH_ASSOC);

            if ($next) {
                $this->db->query("UPDATE post_images SET is_featured = 1 WHERE id = :id", ['id' => $next['id']]);
                $this->db->query(
                    "UPDATE posts SET featured_image = :f WHERE id = :id",
                    ['f' => $next['filename'], 'id' => $image['post_id']]
                );
            } else {
                $this->db->query("UPDATE posts SET featured_image = NULL WHERE id = :id", ['id' => $image['post_id']]);
            }
        }

        header("Location: /fgkirs-admin/posts/edit/{$image['post_id']}");
        exit;
    }

    public function setFeatured(int $imageId): void
    {
        $image = $this->getImageOrDeny($imageId);
        if (!$image)
            return;

        $this->db->query(
            "UPDATE post_images SET is_featured = 0 WHERE post_id = :post_id",
            ['post_id' => $image['post_id']]
        );
        $this->db->query("UPDATE post_images SET is_featured = 1 WHERE id = :id", ['id' => $imageId]);
        $this->db->query(
            "UPDATE posts SET featured_image = :f WHERE id = :id",
            ['f' => $image['filename'], 'id' => $image['post_id']]
        );

        header("Location: /fgkirs-admin/posts/edit/{$image['post_id']}");
        exit;
    }

    private function getPostOrDeny(int $postId): array|false
    {
        $stmt = $this->db->query("SELECT * FROM posts WHERE id = :id", ['id' => $postId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$post || ($post['author_id'] !== Auth::id() && !Auth::isAdmin())) {
            header("Location: /fgkirs-admin/posts");
            exit;
        }
        return $post;
    }

    private function getImageOrDeny(int $imageId): array|false
    {
        $stmt = $this->db->query(
            "SELECT pi.*, p.author_id FROM post_images pi JOIN posts p ON pi.post_id = p.id WHERE pi.id = :id",
            ['id' => $imageId]
        );
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$image || ($image['author_id'] !== Auth::id() && !Auth::isAdmin())) {
            header("Location: /fgkirs-admin/posts");
            exit;
        }
        return $image;
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

        $sql = "SELECT * FROM posts WHERE id = :id AND type IN ('news', 'event')";
        $stmt = $this->db->query($sql, ['id' => $id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            header('Location: /fgkirs-admin/posts');
            exit;
        }

        if ($post['author_id'] !== Auth::id() && !Auth::isAdmin()) {
            $_SESSION['error'] = 'Você não tem permissão para excluir este post.';
            header('Location: /fgkirs-admin/posts');
            exit;
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
