<?php

namespace Models;

use Core\Database;
use PDO;

class SitePopup
{
    /**
     * Supported poster formats. Dimensions follow the Instagram standards so the
     * same artwork can be reused on social media and in the popup.
     */
    public const FORMATS = [
        'square'   => ['label' => 'Quadrado',        'dimensions' => '1080 × 1080 px', 'ratio' => '1 / 1',  'max_width' => 440],
        'portrait' => ['label' => 'Retrato (4:5)',   'dimensions' => '1080 × 1350 px', 'ratio' => '4 / 5',  'max_width' => 400],
        'story'    => ['label' => 'Vertical (9:16)', 'dimensions' => '1080 × 1920 px', 'ratio' => '9 / 16', 'max_width' => 340],
    ];

    public const KEY_TYPES = [
        'cnpj'   => 'CNPJ',
        'cpf'    => 'CPF',
        'email'  => 'E-mail',
        'phone'  => 'Telefone',
        'random' => 'Chave Aleatória',
    ];

    public const LEGACY_IMAGE = '/assets/images/popup-PIX.jpg';

    public const UPLOAD_DIR = __DIR__ . '/../../public/uploads/popup';

    public static function get(): array
    {
        try {
            $db  = Database::getInstance();
            $sql = "SELECT * FROM site_popup WHERE id = 1 LIMIT 1";

            $row = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }

            // First run: persist the campaign that used to be hardcoded in the view
            self::save(self::defaults());

            return $db->query($sql)->fetch(PDO::FETCH_ASSOC) ?: self::defaults();
        } catch (\Exception $e) {
            error_log('SitePopup::get error: ' . $e->getMessage());
            return self::defaults();
        }
    }

    public static function save(array $data): void
    {
        Database::getInstance()->query(
            "INSERT INTO site_popup
                (id, is_active, image, image_format, image_alt,
                 pix_enabled, pix_label, pix_key_type, pix_key,
                 whatsapp_enabled, whatsapp_label, whatsapp_phone, whatsapp_message,
                 frequency_hours)
             VALUES
                (1, :is_active, :image, :image_format, :image_alt,
                 :pix_enabled, :pix_label, :pix_key_type, :pix_key,
                 :whatsapp_enabled, :whatsapp_label, :whatsapp_phone, :whatsapp_message,
                 :frequency_hours)
             ON DUPLICATE KEY UPDATE
                is_active        = VALUES(is_active),
                image            = VALUES(image),
                image_format     = VALUES(image_format),
                image_alt        = VALUES(image_alt),
                pix_enabled      = VALUES(pix_enabled),
                pix_label        = VALUES(pix_label),
                pix_key_type     = VALUES(pix_key_type),
                pix_key          = VALUES(pix_key),
                whatsapp_enabled = VALUES(whatsapp_enabled),
                whatsapp_label   = VALUES(whatsapp_label),
                whatsapp_phone   = VALUES(whatsapp_phone),
                whatsapp_message = VALUES(whatsapp_message),
                frequency_hours  = VALUES(frequency_hours)",
            [
                'is_active'        => (int) ($data['is_active'] ?? 0),
                'image'            => $data['image'] ?? null,
                'image_format'     => self::normalizeFormat($data['image_format'] ?? null),
                'image_alt'        => $data['image_alt'] ?? null,
                'pix_enabled'      => (int) ($data['pix_enabled'] ?? 0),
                'pix_label'        => $data['pix_label'] ?? null,
                'pix_key_type'     => self::normalizeKeyType($data['pix_key_type'] ?? null),
                'pix_key'          => $data['pix_key'] ?? null,
                'whatsapp_enabled' => (int) ($data['whatsapp_enabled'] ?? 0),
                'whatsapp_label'   => $data['whatsapp_label'] ?? null,
                'whatsapp_phone'   => $data['whatsapp_phone'] ?? null,
                'whatsapp_message' => $data['whatsapp_message'] ?? null,
                'frequency_hours'  => max(0, (int) ($data['frequency_hours'] ?? 24)),
            ]
        );
    }

    /**
     * Public URL of the poster. Falls back to the artwork shipped with the theme
     * while no image has been uploaded through the admin panel.
     */
    public static function imageUrl(array $popup): string
    {
        $image = $popup['image'] ?? null;

        if ($image && file_exists(self::UPLOAD_DIR . '/' . $image)) {
            return '/uploads/popup/' . $image;
        }

        return self::LEGACY_IMAGE;
    }

    public static function format(array $popup): array
    {
        return self::FORMATS[self::normalizeFormat($popup['image_format'] ?? null)];
    }

    /**
     * Digits-only national number prefixed with the Brazilian country code.
     */
    public static function whatsappUrl(array $popup): ?string
    {
        $digits = preg_replace('/\D/', '', (string) ($popup['whatsapp_phone'] ?? ''));
        if (strlen($digits) < 10) {
            return null;
        }

        $url = 'https://wa.me/55' . $digits;
        $message = trim((string) ($popup['whatsapp_message'] ?? ''));

        return $message !== '' ? $url . '?text=' . rawurlencode($message) : $url;
    }

    /**
     * Label shown under the copy button, e.g. "CNPJ 29.834.577/0001-80".
     */
    public static function pixKeyDisplay(array $popup): string
    {
        $key = trim((string) ($popup['pix_key'] ?? ''));
        if ($key === '') {
            return '';
        }

        $type = self::normalizeKeyType($popup['pix_key_type'] ?? null);

        return match ($type) {
            'cnpj', 'cpf' => self::KEY_TYPES[$type] . ' ' . $key,
            default       => $key,
        };
    }

    public static function formatPhone(?string $digits): string
    {
        return \Helpers\Format::phone($digits);
    }

    private static function normalizeFormat(?string $format): string
    {
        return isset(self::FORMATS[$format]) ? $format : 'portrait';
    }

    private static function normalizeKeyType(?string $type): string
    {
        return isset(self::KEY_TYPES[$type]) ? $type : 'cnpj';
    }

    private static function defaults(): array
    {
        return [
            'id'               => 1,
            'is_active'        => 1,
            'image'            => null,
            'image_format'     => 'portrait',
            'image_alt'        => 'PIX Solidário FGKIRS – Com sua ajuda, nossos atletas vão mais longe!',
            'pix_enabled'      => 1,
            'pix_label'        => 'Contribuir via PIX',
            'pix_key_type'     => 'cnpj',
            'pix_key'          => '29.834.577/0001-80',
            'whatsapp_enabled' => 1,
            'whatsapp_label'   => 'Posso Ajudar?',
            'whatsapp_phone'   => '5535112602',
            'whatsapp_message' => 'Olá! Quero apoiar o Karatê Gaúcho pelo PIX Solidário FGKIRS 🥋',
            'frequency_hours'  => 24,
        ];
    }
}
