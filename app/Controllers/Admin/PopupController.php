<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Helpers\Auth;
use Helpers\Csrf;
use Helpers\ImageProcessor;
use Models\SitePopup;

class PopupController extends Controller
{
    private const UPLOAD_DIR = __DIR__ . '/../../../public/uploads/popup';

    /** Posters carry text, so they keep more detail than the site-wide defaults. */
    private const IMAGE_MAX_WIDTH = 1080;
    private const IMAGE_QUALITY   = 88;

    public function edit(): void
    {
        if (!Auth::isAdmin()) {
            header('Location: /fgkirs-admin');
            exit;
        }

        $this->view('admin/popup/form', [
            'popup'     => SitePopup::get(),
            'pageTitle' => 'Popup do Site',
        ]);
    }

    public function update(): void
    {
        if (!Auth::isAdmin() || !Csrf::validate($_POST['csrf_token'] ?? null)) {
            header('Location: /fgkirs-admin/popup');
            exit;
        }

        $current = SitePopup::get();
        $image   = $current['image'] ?? null;

        if (!empty($_FILES['image']['name'])) {
            $filename = ImageProcessor::process(
                $_FILES['image'],
                self::UPLOAD_DIR,
                self::IMAGE_MAX_WIDTH,
                self::IMAGE_QUALITY
            );

            if ($filename === false) {
                $_SESSION['error'] = 'Não foi possível processar a imagem enviada. Envie um arquivo JPG, PNG ou WEBP.';
                header('Location: /fgkirs-admin/popup');
                exit;
            }

            if ($image) {
                ImageProcessor::delete(self::UPLOAD_DIR . '/' . $image);
            }

            $image = $filename;
        }

        SitePopup::save([
            'is_active'        => isset($_POST['is_active']) ? 1 : 0,
            'image'            => $image,
            'image_format'     => $_POST['image_format'] ?? 'portrait',
            'image_alt'        => trim($_POST['image_alt'] ?? ''),
            'pix_enabled'      => isset($_POST['pix_enabled']) ? 1 : 0,
            'pix_label'        => trim($_POST['pix_label'] ?? '') ?: 'Contribuir via PIX',
            'pix_key_type'     => $_POST['pix_key_type'] ?? 'cnpj',
            'pix_key'          => trim($_POST['pix_key'] ?? ''),
            'whatsapp_enabled' => isset($_POST['whatsapp_enabled']) ? 1 : 0,
            'whatsapp_label'   => trim($_POST['whatsapp_label'] ?? '') ?: 'Posso Ajudar?',
            'whatsapp_phone'   => preg_replace('/\D/', '', $_POST['whatsapp_phone'] ?? '') ?: null,
            'whatsapp_message' => trim($_POST['whatsapp_message'] ?? ''),
            'frequency_hours'  => (int) ($_POST['frequency_hours'] ?? 24),
        ]);

        $_SESSION['success'] = 'Popup atualizado com sucesso!';
        header('Location: /fgkirs-admin/popup');
        exit;
    }

    public function removeImage(): void
    {
        if (!Auth::isAdmin()) {
            header('Location: /fgkirs-admin/popup');
            exit;
        }

        $popup = SitePopup::get();

        if (!empty($popup['image'])) {
            ImageProcessor::delete(self::UPLOAD_DIR . '/' . $popup['image']);
            $popup['image'] = null;
            SitePopup::save($popup);
        }

        $_SESSION['success'] = 'Imagem removida.';
        header('Location: /fgkirs-admin/popup');
        exit;
    }
}
