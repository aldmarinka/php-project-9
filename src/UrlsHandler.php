<?php

declare(strict_types=1);

namespace Hexlet\Code;

use Carbon\Carbon;

class UrlsHandler
{
    protected \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param string $url
     *
     * @return void
     */
    public function add(string $url): void
    {
        $query = "INSERT INTO urls (name, created_at) VALUES (:name, :time)";
        $time = Carbon::now();

        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':name', $url);
        $statement->bindValue(':time', $time);
        $statement->execute();
    }

    /**
     * @return array
     */
    public function getList(): array
    {
        $query = "SELECT * FROM urls ORDER BY created_at";

        return $this->pdo->query($query, \PDO::FETCH_ASSOC)->fetchAll();
    }

    /**
     * @param int $id
     *
     * @return array
     */
    public function get(int $id): array
    {
        $query = "SELECT * FROM urls WHERE id=:id";

        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':id', $id, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetch(\PDO::FETCH_ASSOC) ?? [];
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function getByName(string $name): array
    {
        $query = "SELECT * FROM urls WHERE name=:name";

        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':name', $name);
        $statement->execute();

        return $statement->fetch(\PDO::FETCH_ASSOC) ?: [];
    }
}
