<?php
declare(strict_types=1);

namespace Wartollex\Services;

use DateTimeImmutable;
use PDO;
use Wartollex\Database;

final class UserService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function register(string $username, string $language, ?int $invitedBy = null): int
    {
        $now = Database::now();
        $monthlyPeriod = (new DateTimeImmutable('now'))->format('Y-m');

        $statement = $this->pdo->prepare(
            'INSERT INTO users (username, language, monthly_points, total_points, monthly_period, invited_by, created_at, updated_at)
             VALUES (:username, :language, 0, 0, :period, :invited_by, :created_at, :updated_at)'
        );
        $statement->execute([
            ':username' => $username,
            ':language' => $language,
            ':period' => $monthlyPeriod,
            ':invited_by' => $invitedBy,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $userId = (int) $this->pdo->lastInsertId();

        if ($invitedBy !== null) {
            $this->recordInvite($invitedBy, $userId);
        }

        return $userId;
    }

    private function recordInvite(int $userId, int $invitedUserId): void
    {
        $statement = $this->pdo->prepare(
            'INSERT OR IGNORE INTO invites (user_id, invited_user_id, created_at) VALUES (:user_id, :invited_user_id, :created_at)'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':invited_user_id' => $invitedUserId,
            ':created_at' => Database::now(),
        ]);

        $this->pdo->prepare(
            'UPDATE users SET total_points = total_points + 15, updated_at = :updated_at WHERE user_id = :user_id'
        )->execute([
            ':user_id' => $userId,
            ':updated_at' => Database::now(),
        ]);
    }

    public function find(int $userId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE user_id = :user_id');
        $statement->execute([':user_id' => $userId]);
        $user = $statement->fetch();

        if ($user === false) {
            return null;
        }

        return $this->refreshMonthlyPoints($user);
    }

    public function refreshMonthlyPoints(array $user): array
    {
        $currentPeriod = (new DateTimeImmutable('now'))->format('Y-m');
        if ($user['monthly_period'] !== $currentPeriod) {
            $statement = $this->pdo->prepare(
                'UPDATE users SET monthly_points = 0, monthly_period = :period, updated_at = :updated_at WHERE user_id = :user_id'
            );
            $statement->execute([
                ':user_id' => $user['user_id'],
                ':period' => $currentPeriod,
                ':updated_at' => Database::now(),
            ]);

            $user['monthly_points'] = 0;
            $user['monthly_period'] = $currentPeriod;
        }

        return $user;
    }

    public function updateLanguage(int $userId, string $language): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET language = :language, updated_at = :updated_at WHERE user_id = :user_id');
        $statement->execute([
            ':user_id' => $userId,
            ':language' => $language,
            ':updated_at' => Database::now(),
        ]);
    }

    public function applyPoints(int $userId, int $points): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET monthly_points = monthly_points + :points, total_points = total_points + :points, updated_at = :updated_at WHERE user_id = :user_id'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':points' => $points,
            ':updated_at' => Database::now(),
        ]);
    }

    public function findByReferralCode(string $code): ?array
    {
        $userId = $this->decodeReferralCode($code);
        if ($userId === null) {
            return null;
        }

        return $this->find($userId);
    }

    public function generateReferralCode(int $userId): string
    {
        return strtoupper(base_convert((string) $userId, 10, 36));
    }

    private function decodeReferralCode(string $code): ?int
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $decoded = base_convert(strtolower($code), 36, 10);
        if (!ctype_digit($decoded)) {
            return null;
        }

        return (int) $decoded;
    }
}
