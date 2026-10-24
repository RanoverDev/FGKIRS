<?php

namespace Helpers;

/**
 * Sanitiza o HTML do editor do certificado. Lista branca de tags e de propriedades de estilo;
 * o resultado é remontado do zero (nunca se reaproveita o markup recebido).
 */
class RichText
{
    private const TAGS = ['p', 'div', 'br', 'span', 'b', 'strong', 'i', 'em', 'u'];

    /** Tags removidas com todo o conteúdo; as demais não permitidas são só "desembrulhadas". */
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'link', 'meta', 'template', 'textarea', 'select', 'noscript'];

    private const MAX_LENGTH = 20000;

    public static function sanitize(string $html): string
    {
        $html = substr($html, 0, self::MAX_LENGTH);
        if (trim($html) === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);
        $doc      = new \DOMDocument();
        $doc->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>',
            LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $doc->getElementsByTagName('body')->item(0);

        return $body ? trim(self::children($body)) : '';
    }

    /** Texto puro, sem tags: serve para saber se o editor ficou vazio. */
    public static function plainText(string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    private static function children(\DOMNode $node): string
    {
        $out = '';

        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMText) {
                $out .= htmlspecialchars($child->nodeValue ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                continue;
            }

            if (!$child instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP, true)) {
                continue;
            }

            if (!in_array($tag, self::TAGS, true)) {
                $out .= self::children($child);
                continue;
            }

            if ($tag === 'br') {
                $out .= '<br>';
                continue;
            }

            $style = self::style($child->getAttribute('style'));
            $attr  = $style === '' ? '' : ' style="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '"';

            $out .= "<$tag$attr>" . self::children($child) . "</$tag>";
        }

        return $out;
    }

    private static function style(string $raw): string
    {
        $kept = [];

        foreach (explode(';', $raw) as $declaration) {
            if (!str_contains($declaration, ':')) {
                continue;
            }

            [$prop, $value] = array_map('trim', explode(':', $declaration, 2));
            $prop  = strtolower($prop);
            $value = strtolower($value);

            if (self::allowed($prop, $value)) {
                $kept[$prop] = "$prop:$value";
            }
        }

        return implode(';', $kept);
    }

    private static function allowed(string $prop, string $value): bool
    {
        return match ($prop) {
            'color'           => (bool) preg_match('/^(#[0-9a-f]{3}|#[0-9a-f]{6}|rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\))$/', $value),
            'font-size'       => (bool) preg_match('/^\d{1,2}(\.\d+)?px$/', $value) && (float) $value >= 8 && (float) $value <= 80,
            'font-weight'     => (bool) preg_match('/^(normal|bold|[1-9]00)$/', $value),
            'font-style'      => $value === 'normal' || $value === 'italic',
            'text-decoration' => in_array($value, ['none', 'underline', 'line-through'], true),
            'text-align'      => in_array($value, ['left', 'right', 'center', 'justify'], true),
            'margin-top', 'margin-bottom' => (bool) preg_match('/^\d{1,3}px$/', $value) && (int) $value <= 200,
            default           => false,
        };
    }
}
