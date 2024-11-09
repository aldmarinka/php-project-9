<?php

$autoloadPath1 = __DIR__ . '/../../autoload.php';
$autoloadPath2 = __DIR__ . '/../vendor/autoload.php';

if (file_exists($autoloadPath1)) {
    require_once $autoloadPath1;
} else {
    require_once $autoloadPath2;
}

use GuzzleHttp\Client;
use Hexlet\Code\CheckerService;
use Hexlet\Code\CheckHandler;
use Hexlet\Code\Connection;
use Hexlet\Code\UrlsHandler;
use Hexlet\Code\UrlValidator;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Flash\Messages;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;
use Slim\Views\PhpRenderer;

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new Messages();
});

$container->set('connection', function () {
    return Connection::get()->connect();
});

$app = AppFactory::createFromContainer($container);

/** Кастомный обработчик ошибок */
$errorHandler = function (Request $request, Throwable $exception) use ($app) {
    $response = $app->getResponseFactory()->createResponse();
    $renderer = $app->getContainer()->get('renderer');
    if ($exception->getCode() === 404) {
        return $renderer->render($response->withStatus(404), 'errors/404.phtml');
    } else {
        return  $renderer->render($response->withStatus(500), 'errors/500.phtml');
    }
};

$app->addErrorMiddleware(true, true, true)->setDefaultErrorHandler($errorHandler);


$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'main.phtml');
})->setName('main.index');

$app->post('/urls', function (Request $request, Response $response) {
    $parsedBody = $request->getParsedBody();

    // Проверка, является ли $parsedBody массивом или объектом, и наличие 'url'
    $dataUrl = null;
    if (is_array($parsedBody) && isset($parsedBody['url'])) {
        $dataUrl = $parsedBody['url'];
    } elseif (is_object($parsedBody) && isset($parsedBody->url)) {
        $dataUrl = $parsedBody->url;
    }

    $validator = new UrlValidator();
    $errors = $validator->validate($dataUrl);

    // Получение 'name' из $dataUrl
    $name = '';
    if (is_array($dataUrl) && isset($dataUrl['name'])) {
        $name = $dataUrl['name'];
    } elseif (is_object($dataUrl) && isset($dataUrl->name)) {
        $name = $dataUrl->name;
    }

    if (count($errors) === 0) {
        $parsedUrl = parse_url($name);
        $name = "{$parsedUrl['scheme']}://{$parsedUrl['host']}";
        if (key_exists('path', $parsedUrl) && $parsedUrl['path'] !== '/' ){
            $name .= $parsedUrl['path'];
        }

        $dbh    = new UrlsHandler($this->get('connection'));
        $oldUrl = $dbh->getByName($name);
        if (empty($oldUrl)) {
            $dbh->add($name);
            $id = $dbh->getByName($name)['id'];
            $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
        } else {
            $id = $oldUrl['id'];
            $this->get('flash')->addMessage('success', 'Страница уже существует');
        }

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        $url = $routeParser->urlFor('urls.show', ['id' => $id]);

        return $response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }

    $params = [
        'name'   => $name,
        'errors' => $errors[0]
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'main.phtml', $params);
})->setName('urls.store');

$app->get('/urls', function ($request, $response) {
    $dbh = new CheckHandler($this->get('connection'));
    $params['table'] = $dbh->getList();

    return $this->get('renderer')->render($response, 'urls.index.phtml', $params);
})->setName('urls.index');

$app->get('/urls/{id}', function ($request, $response, array $args) {
    $dbUrls = new UrlsHandler($this->get('connection'));
    $dbCheck = new CheckHandler($this->get('connection'));

    $id = $args['id'];
    $params['url'] = $dbUrls->get($id);
    $params['checks'] = $dbCheck->getByUrl($id);
    $flash = $this->get('flash')->getMessages();

    if (!empty($flash)) {
        $params['flash'] = $flash;
    }

    return $this->get('renderer')->render($response, 'urls.show.phtml', $params);
})->setName('urls.show');

$app->post('/urls/{url_id}/checks', function ($request, $response, array $args) {
    $dbUrls = new UrlsHandler($this->get('connection'));
    $dbCheck = new CheckHandler($this->get('connection'));
    $checker = new CheckerService(new Client());

    $url_id = $args['url_id'];
    $url = $dbUrls->get($url_id);
    $check = $checker->checkUrl($url['name']);

    $meta = $check['meta'];
    if (array_key_exists('code', $check)) {
        $dbCheck->add(
            $url_id,
            $check['code'],
            $meta['h1'],
            $meta['title'],
            $meta['description']
        );
    }

    $this->get('flash')->addMessage($check['type'], $check['message']);

    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    $url = $routeParser->urlFor('urls.show', ['id' => $url_id]);

    return $response
        ->withHeader('Location', $url)
        ->withStatus(302);
})->setName('urls.checks');

$app->run();
