<?php

namespace App\Support;

/** Plain-text alternative for branded HTML emails (improves deliverability). */
class HtmlToText
{
    public static function convert(string $html): string
    {
        $html = preg_replace('#<(head|style|script)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#<!--.*?-->#s', '', $html);
        $html = preg_replace_callback('#<a\b[^>]*href="([^"]+)"[^>]*>(.*?)</a>#is', function ($m) {
            $text = trim(strip_tags($m[2]));
            $url = html_entity_decode($m[1]);

            return $text === '' || $text === $url ? $url : "$text ( $url )";
        }, $html);
        $html = preg_replace('#<img\b[^>]*alt="([^"]*)"[^>]*>#i', '[$1]', $html);
        $html = preg_replace('#<br\s*/?>#i', "\n", $html);
        $html = preg_replace('#</(p|div|tr|h[1-6]|li|table)>#i', "\n\n", $html);
        $html = preg_replace('#<li\b[^>]*>#i', '- ', $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t\x{00A0}]+/u", ' ', $text);
        $text = preg_replace("/ *\n */", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text)."\n";
    }
}
