<?php

$autoloadPath1 = __DIR__ . '/../../autoload.php';
$autoloadPath2 = __DIR__ . '/../vendor/autoload.php';

if (file_exists($autoloadPath1)) {
    require_once $autoloadPath1;
} else {
    require_once $autoloadPath2;
}

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


$app = AppFactory::createFromContainer($container);
$router = $app->getRouteCollector()->getRouteParser();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'main.phtml');
})->setName('main.index');

$app->post('/urls', function (Request $request, Response $response) use ($router) {
    $parsedBody = $request->getParsedBody();
    $url = $parsedBody['url'];

    $validator = new UrlValidator();
    $errors = $validator->validate($url);

    if (count($errors) === 0) {
        $name = $url['name'];
        $len  = strlen($name);
        $name = str_ends_with($name, '/') ? substr($name, 0, $len - 1) : $name;

        $dbh    = new UrlsHandler(Connection::get()->connect());
        $oldUrl = $dbh->getByName($name);
        if (empty($oldUrl)) {
            $dbh->add($name);
            $id = $dbh->getByName($name)['id'];
            $this->get('flash')->addMessage('success', 'Страница добавлена');
        } else {
            $id = $oldUrl['id'];
            $this->get('flash')->addMessage('success', 'Страница уже существует');
        }

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        $url         = $routeParser->urlFor('urls.show', ['id' => $id]);

        return $response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }

    $params = [
        'name' => $url['name'],
        'errors' => is_array($errors) ? $errors[0] : 'Непредвиденная ошибка'
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'main.phtml', $params);
})->setName('urls.store');

$app->get('/urls', function ($request, $response) {
    $dbh = new UrlsHandler(Connection::get()->connect());
    $params['table'] = $dbh->getList();

    return $this->get('renderer')->render($response, 'urls.index.phtml', $params);
})->setName('urls.index');

$app->get('/urls/{id}', function ($request, $response, array $args) {
    $connection = Connection::get()->connect();
    $dbUrls = new UrlsHandler($connection);
    $dbCheck = new CheckHandler($connection);

    $id = $args['id'];
    $params['url'] = $dbUrls->get($id);
    $params['checks'] = $dbCheck->getByUrl($id);
    $flash = $this->get('flash')->getMessages();

    if (!empty($flash)) {
        $params['flash'] = $flash;
    }

    return $this->get('renderer')->render($response, 'urls.show.phtml', $params);
})->setName('urls.show');

$app->post('/urls/{url_id}/checks', function ($request, $response, array $args) use ($router) {
    $dbh = new CheckHandler(Connection::get()->connect());

    $url_id = $args['url_id'];
    $dbh->add($url_id);

    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    $url         = $routeParser->urlFor('urls.show', ['id' => $url_id]);

    return $response
        ->withHeader('Location', $url)
        ->withStatus(302);
})->setName('urls.checks');

$app->run();
