<?php

declare(strict_types=1);

namespace Hexlet\Code;

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

            return [
                'type'    => 'success',
                'message' => 'Успешно',
                'code'    => $response->getStatusCode()
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
                    'code'    => $response->getStatusCode()
                ];
            }

            return [
                'type'    => 'warning',
                'message' => 'Страница была проверена, но сервер отдавал ошибку',
                'code'    => $response->getStatusCode()
            ];
        }
    }
}
