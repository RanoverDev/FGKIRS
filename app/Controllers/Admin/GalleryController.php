<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Database;
use Helpers\Auth;
use Helpers\ImageProcessor;
use Helpers\Slugify;
use PDO;
use ZipArchive;

class GalleryController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        if (!Auth::authorize(['admin', 'sensei', 'aluno-colaborador'])) {
            header('Location: /fgkirs-admin/dashboard');
            exit;
        }

        $authorFilter = Auth::isAdmin() ? '' : ' AND g.author_id = :author_id';
        $params = Auth::isAdmin() ? [] : ['author_id' => Auth::id()];

        $sql = "SELECT g.*,
                    CASE
                        WHEN u.role = 'admin' THEN 'Comunicação FGKIRS'
                        ELSE CONCAT(COALESCE(d.name,'—'), IF(d.city IS NOT NULL AND d.city != '', CONCAT(' / ', d.city), ''))
                    END AS author_display,
                    (SELECT COUNT(*) FROM gallery_images WHERE gallery_id = g.id) AS image_count
                FROM galleries g
                JOIN users u ON g.author_id = u.id
                LEFT JOIN dojos d ON u.dojo_id = d.id
                WHERE 1=1 {$authorFilter}
                ORDER BY g.event_date DESC";

        $galleries = $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/galleries/index', ['galleries' => $galleries]);
    }

    public function create(): void
    {
        if (!Auth::authorize(['admin', 'sensei', 'aluno-colaborador'])) {
            header('Location: /fgkirs-admin/dashboard');
            exit;
        }

        $this->view('admin/galleries/form');
    }

    public function store(): void
    {
        if (!Auth::authorize(['admin', 'sensei', 'aluno-colaborador'])) {
            header('Location: /fgkirs-admin/dashboard');
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $eventDate = $_POST['event_date'] ?: null;
        $status = \in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'published';

        if (empty($title) || empty($eventDate)) {
            $_SESSION['error'] = 'Título e data do evento são obrigatórios.';
            header('Location: /fgkirs-admin/galleries/create');
            exit;
        }

        $slug = Slugify::unique($title, function ($candidate) {
            return $this->db->query("SELECT id FROM galleries WHERE slug = :s", ['s' => $candidate])->fetchColumn() !== false;
        });

        $this->db->query(
            "INSERT INTO galleries (title, slug, description, event_date, author_id, status, published_at)
             VALUES (:title, :slug, :description, :event_date, :author_id, :status, NOW())",
            [
                'title' => $title,
                'slug' => $slug,
                'description' => $description,
                'event_date' => $eventDate,
                'author_id' => Auth::id(),
                'status' => $status,
            ]
        );

        $galleryId = (int) $this->db->lastInsertId();

        $_SESSION['success'] = 'Galeria criada! Agora faça o upload das imagens em ZIP.';
        header("Location: /fgkirs-admin/galleries/edit/$galleryId");
        exit;
    }

    public function edit(int $id): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }

        $gallery = $this->getGalleryOrDeny($id);

        $images = $this->db->query(
            "SELECT * FROM gallery_images WHERE gallery_id = :id ORDER BY is_cover DESC, created_at ASC",
            ['id' => $id]
        )->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/galleries/form', ['gallery' => $gallery, 'images' => $images]);
    }

    public function update(int $id): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }

        $gallery = $this->getGalleryOrDeny($id);

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $eventDate = $_POST['event_date'] ?: null;
        $status = \in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'published';

        if (empty($title) || empty($eventDate)) {
            $_SESSION['error'] = 'Título e data do evento são obrigatórios.';
            header("Location: /fgkirs-admin/galleries/edit/$id");
            exit;
        }

        $slug = $gallery['slug'];
        if (empty($slug)) {
            $slug = Slugify::unique($title, fn($candidate) => $this->db->query(
                "SELECT id FROM galleries WHERE slug = :s AND id != :id",
                ['s' => $candidate, 'id' => $id]
            )->fetchColumn() !== false);
        }

        $this->db->query(
            "UPDATE galleries SET title = :title, slug = :slug, description = :description,
             event_date = :event_date, status = :status, updated_at = NOW()
             WHERE id = :id",
            [
                'title' => $title,
                'slug' => $slug,
                'description' => $description,
                'event_date' => $eventDate,
                'status' => $status,
                'id' => $id,
            ]
        );

        $_SESSION['success'] = 'Galeria atualizada com sucesso!';
        header('Location: /fgkirs-admin/galleries');
        exit;
    }

    public function uploadZip(int $id): void
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '256M');

        $gallery = $this->getGalleryOrDeny($id);

        if (!isset($_FILES['zip_file'])) {
            $_SESSION['error'] = 'Nenhum arquivo recebido. Verifique as configurações de upload do servidor.';
            header("Location: /fgkirs-admin/galleries/edit/$id");
            exit;
        }

        $uploadError = $_FILES['zip_file']['error'];
        if ($uploadError !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'O arquivo excede o limite do servidor (upload_max_filesize).',
                UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o limite do formulário.',
                UPLOAD_ERR_PARTIAL => 'O arquivo foi enviado apenas parcialmente.',
                UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi selecionado.',
                UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária ausente no servidor.',
                UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar o arquivo no servidor.',
            ];
            $_SESSION['error'] = $errorMessages[$uploadError] ?? "Erro no upload (código $uploadError).";
            header("Location: /fgkirs-admin/galleries/edit/$id");
            exit;
        }

        $zip = new ZipArchive();
        if ($zip->open($_FILES['zip_file']['tmp_name']) !== true) {
            $_SESSION['error'] = 'Não foi possível abrir o arquivo ZIP.';
            header("Location: /fgkirs-admin/galleries/edit/$id");
            exit;
        }

        $tempDir = sys_get_temp_dir() . '/gallery_' . uniqid();
        mkdir($tempDir, 0755, true);
        $zip->extractTo($tempDir);
        $zip->close();

        $dateFolder = date('Y/m/d');
        $uploadDir = __DIR__ . '/../../../public/uploads/galleries/' . $dateFolder;

        $hasCover = (int) $this->db->query(
            "SELECT COUNT(*) FROM gallery_images WHERE gallery_id = :id AND is_cover = 1",
            ['id' => $id]
        )->fetchColumn() > 0;

        $files = $this->scanImages($tempDir);
        $processedCount = 0;
        $failedCount = 0;

        if (empty($files)) {
            error_log("GalleryController: ZIP extraído em $tempDir não contém imagens suportadas");
        }

        foreach ($files as $file) {
            $filename = ImageProcessor::processFromPath($file, $uploadDir);
            if (!$filename) {
                $failedCount++;
                continue;
            }

            $relativePath = $dateFolder . '/' . $filename;
            $isCover = $hasCover ? 0 : 1;

            $this->db->query(
                "INSERT INTO gallery_images (gallery_id, filename, is_cover) VALUES (:gallery_id, :filename, :is_cover)",
                ['gallery_id' => $id, 'filename' => $relativePath, 'is_cover' => $isCover]
            );

            if (!$hasCover) {
                $this->db->query(
                    "UPDATE galleries SET cover_image = :cover WHERE id = :id",
                    ['cover' => $relativePath, 'id' => $id]
                );
                $hasCover = true;
            }

            $processedCount++;
        }

        $this->deleteDir($tempDir);

        if ($processedCount === 0) {
            $detail = empty($files)
                ? 'Nenhuma imagem encontrada no ZIP (formatos aceitos: JPG, PNG, WebP).'
                : "Todas as $failedCount imagem(ns) falharam no processamento. Verifique o log do servidor.";
            $_SESSION['error'] = "Nenhuma imagem foi adicionada. $detail";
        } else {
            $msg = "$processedCount imagem(ns) adicionada(s) com sucesso!";
            if ($failedCount > 0) {
                $msg .= " ($failedCount falhou)";
            }
            $_SESSION['success'] = $msg;
        }

        header("Location: /fgkirs-admin/galleries/edit/$id");
        exit;
    }

    /**
     * Step 1 of chunked upload: receive ZIP, extract to temp dir, store file list in session.
     * Returns JSON: { total: N, tempDir: "..." } or { error: "..." }
     */
    public function uploadZipExtract(int $id): void
    {
        @set_time_limit(120);
        @ini_set('memory_limit', '512M');

        header('Content-Type: application/json');

        if (!Auth::authorize(['admin', 'sensei', 'aluno-colaborador'])) {
            echo json_encode(['error' => 'Acesso negado.']);
            exit;
        }

        $this->getGalleryOrDeny($id);

        if (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
            $code = $_FILES['zip_file']['error'] ?? -1;
            $msgs = [
                UPLOAD_ERR_INI_SIZE  => 'O arquivo excede o limite do servidor (upload_max_filesize).',
                UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o limite do formulário.',
                UPLOAD_ERR_PARTIAL   => 'Upload incompleto. Tente novamente.',
                UPLOAD_ERR_NO_FILE   => 'Nenhum arquivo selecionado.',
                UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária ausente no servidor.',
                UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar arquivo no servidor.',
            ];
            echo json_encode(['error' => $msgs[$code] ?? "Erro no upload (código $code)."]);
            exit;
        }

        $zip = new ZipArchive();
        if ($zip->open($_FILES['zip_file']['tmp_name']) !== true) {
            echo json_encode(['error' => 'Não foi possível abrir o arquivo ZIP. Verifique se o arquivo não está corrompido.']);
            exit;
        }

        $tempDir = sys_get_temp_dir() . '/gallery_' . $id . '_' . uniqid();
        mkdir($tempDir, 0755, true);
        $zip->extractTo($tempDir);
        $zip->close();

        $files = $this->scanImages($tempDir);

        if (empty($files)) {
            $this->deleteDir($tempDir);
            echo json_encode(['error' => 'Nenhuma imagem encontrada no ZIP. Formatos aceitos: JPG, PNG, WebP.']);
            exit;
        }

        $sessionKey = 'gallery_zip_' . $id;
        $_SESSION[$sessionKey] = [
            'tempDir' => $tempDir,
            'files'   => $files,
            'offset'  => 0,
        ];

        echo json_encode(['total' => count($files)]);
        exit;
    }

    /**
     * Step 2 of chunked upload: process the next batch of N images from session.
     * Returns JSON: { processed: N, failed: N, offset: N, total: N, done: bool }
     */
    public function processBatch(int $id): void
    {
        @set_time_limit(60);
        @ini_set('memory_limit', '512M');

        header('Content-Type: application/json');

        if (!Auth::authorize(['admin', 'sensei', 'aluno-colaborador'])) {
            echo json_encode(['error' => 'Acesso negado.']);
            exit;
        }

        $this->getGalleryOrDeny($id);

        $sessionKey = 'gallery_zip_' . $id;
        if (empty($_SESSION[$sessionKey])) {
            echo json_encode(['error' => 'Sessão expirada. Faça o upload novamente.']);
            exit;
        }

        $state    = &$_SESSION[$sessionKey];
        $files    = $state['files'];
        $offset   = $state['offset'];
        $total    = count($files);
        $batchSize = 5;

        $dateFolder = date('Y/m/d');
        $uploadDir  = __DIR__ . '/../../../public/uploads/galleries/' . $dateFolder;

        $hasCover = (int) $this->db->query(
            "SELECT COUNT(*) FROM gallery_images WHERE gallery_id = :id AND is_cover = 1",
            ['id' => $id]
        )->fetchColumn() > 0;

        $processed = 0;
        $failed    = 0;
        $batch     = array_slice($files, $offset, $batchSize);

        foreach ($batch as $file) {
            if (!file_exists($file)) {
                $failed++;
                continue;
            }

            $filename = ImageProcessor::processFromPath($file, $uploadDir);
            if (!$filename) {
                $failed++;
                continue;
            }

            $relativePath = $dateFolder . '/' . $filename;
            $isCover = $hasCover ? 0 : 1;

            $this->db->query(
                "INSERT INTO gallery_images (gallery_id, filename, is_cover) VALUES (:gallery_id, :filename, :is_cover)",
                ['gallery_id' => $id, 'filename' => $relativePath, 'is_cover' => $isCover]
            );

            if (!$hasCover) {
                $this->db->query(
                    "UPDATE galleries SET cover_image = :cover WHERE id = :id",
                    ['cover' => $relativePath, 'id' => $id]
                );
                $hasCover = true;
            }

            $processed++;
        }

        $newOffset = $offset + count($batch);
        $state['offset'] = $newOffset;
        $done = $newOffset >= $total;

        if ($done) {
            $this->deleteDir($state['tempDir']);
            unset($_SESSION[$sessionKey]);
        }

        echo json_encode([
            'processed' => $processed,
            'failed'    => $failed,
            'offset'    => $newOffset,
            'total'     => $total,
            'done'      => $done,
        ]);
        exit;
    }

    public function removeImage(int $imageId): void
    {
        $image = $this->getImageOrDeny($imageId);

        $uploadBase = __DIR__ . '/../../../public/uploads/galleries/';
        ImageProcessor::delete($uploadBase . $image['filename']);

        $this->db->query("DELETE FROM gallery_images WHERE id = :id", ['id' => $imageId]);

        if ($image['is_cover']) {
            $next = $this->db->query(
                "SELECT id, filename FROM gallery_images WHERE gallery_id = :gid ORDER BY created_at ASC LIMIT 1",
                ['gid' => $image['gallery_id']]
            )->fetch(PDO::FETCH_ASSOC);

            if ($next) {
                $this->db->query("UPDATE gallery_images SET is_cover = 1 WHERE id = :id", ['id' => $next['id']]);
                $this->db->query(
                    "UPDATE galleries SET cover_image = :cover WHERE id = :id",
                    ['cover' => $next['filename'], 'id' => $image['gallery_id']]
                );
            } else {
                $this->db->query("UPDATE galleries SET cover_image = NULL WHERE id = :id", ['id' => $image['gallery_id']]);
            }
        }

        header("Location: /fgkirs-admin/galleries/edit/{$image['gallery_id']}");
        exit;
    }

    public function setCover(int $imageId): void
    {
        $image = $this->getImageOrDeny($imageId);

        $this->db->query(
            "UPDATE gallery_images SET is_cover = 0 WHERE gallery_id = :gid",
            ['gid' => $image['gallery_id']]
        );
        $this->db->query("UPDATE gallery_images SET is_cover = 1 WHERE id = :id", ['id' => $imageId]);
        $this->db->query(
            "UPDATE galleries SET cover_image = :cover WHERE id = :id",
            ['cover' => $image['filename'], 'id' => $image['gallery_id']]
        );

        header("Location: /fgkirs-admin/galleries/edit/{$image['gallery_id']}");
        exit;
    }

    public function delete(int $id): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }

        $gallery = $this->getGalleryOrDeny($id);

        $images = $this->db->query(
            "SELECT filename FROM gallery_images WHERE gallery_id = :id",
            ['id' => $id]
        )->fetchAll(PDO::FETCH_ASSOC);

        $uploadBase = __DIR__ . '/../../../public/uploads/galleries/';
        foreach ($images as $img) {
            ImageProcessor::delete($uploadBase . $img['filename']);
        }

        $this->db->query("DELETE FROM galleries WHERE id = :id", ['id' => $id]);

        $_SESSION['success'] = 'Galeria e todas as imagens foram excluídas.';
        header('Location: /fgkirs-admin/galleries');
        exit;
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function getGalleryOrDeny(int $id): array
    {
        $gallery = $this->db->query(
            "SELECT * FROM galleries WHERE id = :id",
            ['id' => $id]
        )->fetch(PDO::FETCH_ASSOC);

        if (!$gallery || ($gallery['author_id'] !== Auth::id() && !Auth::isAdmin())) {
            $_SESSION['error'] = 'Galeria não encontrada ou acesso negado.';
            header('Location: /fgkirs-admin/galleries');
            exit;
        }

        return $gallery;
    }

    private function getImageOrDeny(int $imageId): array
    {
        $image = $this->db->query(
            "SELECT gi.*, g.author_id FROM gallery_images gi
             JOIN galleries g ON gi.gallery_id = g.id
             WHERE gi.id = :id",
            ['id' => $imageId]
        )->fetch(PDO::FETCH_ASSOC);

        if (!$image || ($image['author_id'] !== Auth::id() && !Auth::isAdmin())) {
            header('Location: /fgkirs-admin/galleries');
            exit;
        }

        return $image;
    }

    private function scanImages(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $pathname = $file->getPathname();
            if (str_contains($pathname, '__MACOSX') || str_contains($pathname, '.DS_Store')) {
                continue;
            }

            if ($file->getFilename()[0] === '.') {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (\in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'heic'])) {
                $files[] = $pathname;
            }
        }

        return $files;
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
