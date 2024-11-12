<?php

declare(strict_types=1);

namespace Hexlet\Code;

use DiDom\Document;
use DiDom\Exceptions\InvalidSelectorException;
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
                throw $error;
            }

            $status = [
                'type'    => 'warning',
                'message' => 'Страница была проверена, но сервер отдал ошибку',
                'code'    => $response->getStatusCode(),
            ];
        }

        $status['meta'] = $this->getMeta($response->getBody()->getContents());

        return $status;
    }

    /**
     * @throws InvalidSelectorException
     */
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

            $meta[$tagName] = $dom->first($schema);
        }

        return $meta;
    }
}
