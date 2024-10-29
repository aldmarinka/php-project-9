<?php
declare(strict_types=1);

namespace Hexlet\Code;


use Dotenv\Dotenv;

class Connection
{
    protected static ?Connection $connection = null;

    public function connect()
    {
        if (!isset($_ENV['DATABASE_URL'])) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../.');
            $dotenv->load();
        }

        $dbUrl = $_ENV['DATABASE_URL'];
        if (!$dbUrl) {
            throw new \Exception("Отсутствует параметр DATABASE_URL");
        }

        // подключение к базе данных postgresql
        $params = parse_url($dbUrl);

        $conStr = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
            $params['host'],
            $params['port'],
            ltrim($params['path'], '/'),
            $params['user'],
            $params['pass']
        );

        $pdo = new \PDO($conStr);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    public static function get(): Connection
    {
        if (self::$connection === null) {
            self::$connection = new self();
        }

        return self::$connection;
    }
}