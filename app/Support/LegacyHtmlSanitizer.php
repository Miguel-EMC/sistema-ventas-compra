<?php

declare(strict_types=1);

namespace VentasPos\Support;

final class LegacyHtmlSanitizer
{
    public function sanitize(string $buffer): string
    {
        if (!$this->looksLikeHtml($buffer)) {
            return $buffer;
        }

        $buffer = preg_replace_callback(
            '/\b(href|action)\s*=\s*([\"\'])(.*?)\2/i',
            static function (array $matches): string {
                $attribute = $matches[1];
                $quote = $matches[2];
                $url = legacy_strip_auth_from_url($matches[3]);

                return sprintf('%s=%s%s%s', $attribute, $quote, htmlspecialchars($url, ENT_QUOTES, 'UTF-8'), $quote);
            },
            $buffer
        ) ?? $buffer;

        $buffer = preg_replace(
            '/<input[^>]+type=["\']hidden["\'][^>]+name=["\'](?:usuario|password|usuarioLogin|passwordLogin)["\'][^>]*>/i',
            '',
            $buffer
        ) ?? $buffer;

        $buffer = preg_replace_callback(
            '/<form\b([^>]*)>/i',
            static function (array $matches): string {
                $tag = $matches[0];

                if (!preg_match('/\bmethod\s*=\s*["\']?post["\']?/i', $tag)) {
                    return $tag;
                }

                return $tag . csrf_field();
            },
            $buffer
        ) ?? $buffer;

        return $buffer;
    }

    private function looksLikeHtml(string $buffer): bool
    {
        $trimmed = ltrim($buffer);

        if ($trimmed === '' || str_starts_with($trimmed, '%PDF')) {
            return false;
        }

        return (bool) preg_match('/<(?:!DOCTYPE|html|body|head|form|aside|section|div|table)\b/i', $buffer);
    }
}
