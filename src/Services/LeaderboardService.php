<?php
declare(strict_types=1);

namespace Wartollex\Services;

use PDO;

final class LeaderboardService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function topPlayers(int $limit): array
    {
        $statement = $this->pdo->prepare(
            'SELECT username, total_points FROM users ORDER BY total_points DESC, username ASC LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
