<?php

declare(strict_types=1);

namespace Hexlet\Code;

use Carbon\Carbon;

class CheckHandler
{
    protected \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getByUrl(int $urlId): array
    {
        $query = "SELECT * FROM checks WHERE url_id=:url_id ORDER BY created_at";

        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':url_id', $urlId, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param int    $url_id
     * @param int    $code
     * @param string $h1
     * @param string $title
     * @param string $description
     *
     * @return void
     */
    public function add(int $url_id, int $code, string $h1, string $title, string $description): void
    {
        $query = "INSERT INTO checks (url_id, status_code, h1, title, description, created_at) 
            VALUES (:url_id, :code, :h1, :title, :description, :created_at)";
        $created_at = Carbon::now();

        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':url_id', $url_id);
        $statement->bindValue(':code', $code);
        $statement->bindValue(':h1', $h1);
        $statement->bindValue(':title', $title);
        $statement->bindValue(':description', $description);
        $statement->bindValue(':created_at', $created_at);
        $statement->execute();
    }

    /**
     * @return array
     */
    public function getList(): array
    {
        $query = "SELECT 
                u.id,
                u.name,
                c.status_code,
                c.created_at AS last_opened_at
            FROM 
                urls u
            LEFT JOIN 
                (SELECT 
                     url_id,
                     status_code,
                     created_at
                 FROM 
                     checks
                 WHERE 
                     (url_id, created_at) IN (
                         SELECT url_id, MAX(created_at)
                         FROM checks
                         GROUP BY url_id
                     )
                ) AS c ON u.id = c.url_id
            ORDER BY u.id
            ";

        $statement = $this->pdo->prepare($query);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
