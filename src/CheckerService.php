<?php

declare(strict_types=1);

namespace Hexlet\Code;

use DiDom\Document;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class CheckerService
{
    public function __construct(protected Client $client)
    {
    }

    public function checkUrl(string $url): array
    {
        $client = $this->client;

        try {
            $response = $client->request('GET', $url, ['http_errors' => true]);

            $status = [
                'type'    => 'success',
                'message' => 'Страница успешно проверена',
                'code'    => $response->getStatusCode(),
            ];
        } catch (ConnectException) {
            return [
                'type'    => 'error',
                'message' => 'Не удалось подключиться к сайту',
            ];
        } catch (RequestException $error) {
            $response = $error->getResponse();

            if (empty($response)) {
                return [
                    'type'    => 'error',
                    'message' => "При проверке возникла ошибка: {$error->getMessage()}",
                    'code'    => $response->getStatusCode(),
                ];
            }

            $status = [
                'type'    => 'warning',
                'message' => 'Страница была проверена, но сервер отдавал ошибку',
                'code'    => $response->getStatusCode(),
            ];
        }

        $status['meta'] = $this->getMeta($response->getBody()->getContents());

        return $status;
    }

    protected function getMeta(string $content): array
    {
        $dom  = new Document($content);
        $tags = [
            'h1'          => 'h1::text',
            'title'       => 'title::text',
            'description' => 'meta[name="description"]::attr(content)'
        ];

        $meta = [
            'h1'          => '',
            'title'       => '',
            'description' => ''
        ];
        foreach ($tags as $tagName => $schema) {
            if (!$dom->has($schema)) {
                continue;
            }

            $meta[$tagName] = strip_tags($dom->first($schema));
        }

        return $meta;
    }
}
