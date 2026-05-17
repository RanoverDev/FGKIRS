<?php

namespace Helpers;

class Slugify
{
    public static function make(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        $from = ['á', 'à', 'ã', 'â', 'ä', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ó', 'ò', 'õ', 'ô', 'ö', 'ú', 'ù', 'û', 'ü', 'ç', 'ñ', 'ý', 'ÿ'];
        $to = ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c', 'n', 'y', 'y'];
        $text = str_replace($from, $to, $text);

        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', trim($text));

        return substr($text, 0, 200);
    }

    public static function unique(string $base, callable $exists): string
    {
        $slug = self::make($base);
        $candidate = $slug;
        $i = 2;
        while ($exists($candidate)) {
            $candidate = $slug . '-' . $i++;
        }
        return $candidate;
    }
}
