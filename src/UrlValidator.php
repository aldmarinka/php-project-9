<?php

declare(strict_types=1);

namespace Hexlet\Code;

class UrlValidator
{
    public function validate(?array $dataUrl): array
    {
        $errors = [];
        $url = $dataUrl['name'] ?? null;
        if (!$url) {
            $errors[] = "URL не должен быть пустым";
            return $errors;
        }

        if (mb_strlen($url) > 255) {
            $errors[] = "url должен быть короче 255 символов";
        }
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['scheme']) || ($parsedUrl['scheme'] != "http" && $parsedUrl['scheme'] != "https")) {
            $errors[] = "Некорректный URL";
        }
        return $errors;
    }
}
