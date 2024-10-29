<?php

declare(strict_types=1);

namespace Hexlet\Code;

class UrlValidator
{
    public function validate(array $data): array
    {
        $errors = [];
        $url = $data['name'] ?? null;
        if (!$url) {
            $errors[] = "Не введен url";
            return $errors;
        }

        if (mb_strlen($url) > 255) {
            $errors[] = "url должен быть короче 255 символов";
        }
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['scheme']) || ($parsedUrl['scheme'] != "http" && $parsedUrl['scheme'] != "https")) {
            $errors[] = "Некорректный адрес сайта";
        }
        return $errors;
    }
}
