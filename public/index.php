<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/../bootstrap.php';

use Wartollex\Database;
use Wartollex\Services\LeaderboardService;
use Wartollex\Services\Localization;
use Wartollex\Services\TaskService;
use Wartollex\Services\UserService;

/** @var array $config */
/** @var Localization $localization */

$pdo = Database::connection();
$userService = new UserService($pdo);
$taskService = new TaskService($pdo, __DIR__ . '/../data/tasks.json');
$leaderboardService = new LeaderboardService($pdo);

$supportedLanguages = $config['supported_languages'];
$defaultLanguage = $config['default_language'];

$flash = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);

function redirect(?string $uri = null): void
{
    $target = $uri ?? strtok($_SERVER['REQUEST_URI'], '#');
    header('Location: ' . $target);
    exit;
}

function flash(string $message): void
{
    $_SESSION['flash'][] = $message;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if (isset($_GET['ref'])) {
    $_SESSION['referral_hint'] = trim((string) $_GET['ref']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $language = (string) ($_POST['language'] ?? $defaultLanguage);
        $language = in_array($language, $supportedLanguages, true) ? $language : $defaultLanguage;
        $referral = trim((string) ($_POST['referral'] ?? ''));
        if ($referral === '' && isset($_SESSION['referral_hint'])) {
            $referral = (string) $_SESSION['referral_hint'];
        }

        if ($username === '') {
            flash($localization->translate('flash.username_required', $language));
            redirect();
        }

        $inviter = $referral !== '' ? $userService->findByReferralCode($referral) : null;
        $inviterId = $inviter['user_id'] ?? null;

        $userId = $userService->register($username, $language, $inviterId);
        $_SESSION['user_id'] = $userId;
        $_SESSION['language'] = $language;

        flash($localization->translate('flash.registered', $language, ['username' => $username]));
        redirect();
    }

    if ($action === 'complete_task' && isset($_SESSION['user_id'])) {
        $taskId = (string) ($_POST['task_id'] ?? '');
        $points = (int) ($_POST['points'] ?? 0);
        $date = new DateTimeImmutable('now');
        $user = $userService->find((int) $_SESSION['user_id']);

        if ($user === null) {
            redirect();
        }

        $language = $user['language'] ?? $defaultLanguage;

        if ($taskId === '') {
            redirect();
        }

        $wasCompleted = $taskService->completeTask((int) $user['user_id'], $taskId, $date);

        if ($wasCompleted) {
            $userService->applyPoints((int) $user['user_id'], $points);
            flash($localization->translate('flash.completed', $language, [
                'task' => $taskId,
                'points' => $points,
            ]));
        } else {
            flash($localization->translate('flash.already_completed', $language));
        }

        redirect();
    }

    if ($action === 'change_language' && isset($_SESSION['user_id'])) {
        $language = (string) ($_POST['language'] ?? $defaultLanguage);
        if (in_array($language, $supportedLanguages, true)) {
            $userService->updateLanguage((int) $_SESSION['user_id'], $language);
            $_SESSION['language'] = $language;
            flash($localization->translate('flash.language_changed', $language, ['language' => strtoupper($language)]));
        }
        redirect();
    }

    if ($action === 'logout') {
        session_unset();
        session_destroy();
        session_start();
        flash('Session reset.');
        redirect('/');
    }
}

$user = null;
if (isset($_SESSION['user_id'])) {
    $user = $userService->find((int) $_SESSION['user_id']);
    if ($user === null) {
        unset($_SESSION['user_id']);
    }
}

$language = $user['language'] ?? $_SESSION['language'] ?? $defaultLanguage;
if (!in_array($language, $supportedLanguages, true)) {
    $language = $defaultLanguage;
}
$_SESSION['language'] = $language;

$today = new DateTimeImmutable('now');
$tasks = $taskService->tasksForDate($today);
$completion = $user ? $taskService->completionStatus((int) $user['user_id'], $today) : [];
$topPlayers = $leaderboardService->topPlayers((int) $config['leaderboard_limit']);
$referralCode = $user ? $userService->generateReferralCode((int) $user['user_id']) : null;

?><!DOCTYPE html>
<html lang="<?php echo e($language); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($localization->translate('app.title', $language)); ?></title>
    <style>
        :root {
            color-scheme: light dark;
            font-family: 'Segoe UI', Roboto, sans-serif;
        }
        body {
            margin: 0;
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            color: #f8fafc;
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 2rem 1.5rem 3rem;
        }
        header {
            text-align: center;
            margin-bottom: 2rem;
        }
        header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        header p {
            color: #cbd5f5;
            margin: 0;
        }
        .card {
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.45);
        }
        .card h2 {
            margin-top: 0;
            font-size: 1.4rem;
        }
        form.inline {
            display: inline;
        }
        button {
            background: linear-gradient(135deg, #22d3ee, #0ea5e9);
            border: none;
            border-radius: 999px;
            color: #0f172a;
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            cursor: pointer;
        }
        button.secondary {
            background: transparent;
            border: 1px solid rgba(148, 163, 184, 0.5);
            color: #e2e8f0;
        }
        ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        li + li {
            margin-top: 0.75rem;
        }
        .task {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.65);
            border: 1px solid rgba(148, 163, 184, 0.2);
        }
        .task.completed {
            border-color: rgba(34, 211, 238, 0.5);
            background: rgba(13, 148, 136, 0.2);
        }
        .flash {
            background: rgba(34, 211, 238, 0.2);
            border: 1px solid rgba(34, 211, 238, 0.5);
            color: #f0fdff;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
        }
        .stack {
            display: grid;
            gap: 1rem;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(14, 165, 233, 0.2);
            border-radius: 999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.9rem;
        }
        footer {
            text-align: center;
            color: #94a3b8;
            margin-top: 2rem;
            font-size: 0.9rem;
        }
        .muted {
            color: #94a3b8;
        }
        a {
            color: #38bdf8;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo e($localization->translate('app.title', $language)); ?></h1>
            <p><?php echo e($localization->translate('app.subtitle', $language)); ?></p>
        </header>

        <?php foreach ($flash as $message): ?>
            <div class="flash"><?php echo e($message); ?></div>
        <?php endforeach; ?>

        <?php if (!$user): ?>
            <div class="card">
                <h2><?php echo e($localization->translate('registration.title', $language)); ?></h2>
                <form method="post" class="stack">
                    <input type="hidden" name="action" value="register">
                    <label>
                        <?php echo e($localization->translate('registration.username', $language)); ?>
                        <input type="text" name="username" required style="width:100%;padding:0.75rem;border-radius:8px;border:1px solid rgba(148, 163, 184, 0.4);margin-top:0.25rem;">
                    </label>
                    <label>
                        <?php echo e($localization->translate('registration.language', $language)); ?>
                        <select name="language" style="width:100%;padding:0.75rem;border-radius:8px;border:1px solid rgba(148, 163, 184, 0.4);margin-top:0.25rem;">
                            <?php foreach ($supportedLanguages as $code): ?>
                                <option value="<?php echo e($code); ?>" <?php echo $code === $language ? 'selected' : ''; ?>><?php echo strtoupper(e($code)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <?php echo e($localization->translate('registration.referral', $language)); ?>
                        <input type="text" name="referral" value="<?php echo isset($_SESSION['referral_hint']) ? e((string) $_SESSION['referral_hint']) : ''; ?>" style="width:100%;padding:0.75rem;border-radius:8px;border:1px solid rgba(148, 163, 184, 0.4);margin-top:0.25rem;">
                    </label>
                    <button type="submit"><?php echo e($localization->translate('registration.submit', $language)); ?></button>
                </form>
            </div>
        <?php else: ?>
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
                    <h2><?php echo e($localization->translate('summary.heading', $language)); ?></h2>
                    <div>
                        <form method="post" class="inline">
                            <input type="hidden" name="action" value="change_language">
                            <select name="language" onchange="this.form.submit()" style="padding:0.4rem 0.75rem;border-radius:8px;border:1px solid rgba(148, 163, 184, 0.4);">
                                <?php foreach ($supportedLanguages as $code): ?>
                                    <option value="<?php echo e($code); ?>" <?php echo $code === $language ? 'selected' : ''; ?>><?php echo strtoupper(e($code)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <form method="post" class="inline" style="margin-left:0.75rem;">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="secondary"><?php echo e($localization->translate('logout', $language)); ?></button>
                        </form>
                    </div>
                </div>
                <div class="summary-grid">
                    <div class="card" style="background:rgba(14,165,233,0.15);border:1px solid rgba(14,165,233,0.35);">
                        <strong class="muted"><?php echo e($localization->translate('summary.monthly', $language)); ?></strong>
                        <p style="font-size:1.8rem;margin:0.5rem 0 0;"><?php echo (int) $user['monthly_points']; ?></p>
                    </div>
                    <div class="card" style="background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.35);">
                        <strong class="muted"><?php echo e($localization->translate('summary.total', $language)); ?></strong>
                        <p style="font-size:1.8rem;margin:0.5rem 0 0;"><?php echo (int) $user['total_points']; ?></p>
                    </div>
                    <div class="card" style="background:rgba(129,140,248,0.15);border:1px solid rgba(129,140,248,0.35);">
                        <strong class="muted"><?php echo e($localization->translate('summary.invite', $language)); ?></strong>
                        <p style="margin:0.5rem 0 0;"><?php echo e($localization->translate('summary.invite_code', $language, ['code' => $referralCode])); ?></p>
                        <span class="pill">/?ref=<?php echo e($referralCode); ?></span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2><?php echo e($localization->translate('tasks.heading', $language)); ?></h2>
                <?php if (empty($tasks)): ?>
                    <p class="muted"><?php echo e($localization->translate('tasks.none', $language)); ?></p>
                <?php else: ?>
                    <ul class="stack">
                        <?php foreach ($tasks as $task): ?>
                            <?php
                                $taskId = $task['id'];
                                $isCompleted = $completion[$taskId] ?? false;
                            ?>
                            <li>
                                <div class="task <?php echo $isCompleted ? 'completed' : ''; ?>">
                                    <div>
                                        <strong><?php echo e($task['description']); ?></strong>
                                        <div class="muted"><?php echo e($localization->translate('tasks.points', $language, ['points' => $task['points']])); ?></div>
                                        <?php if (!empty($task['link'])): ?>
                                            <a href="<?php echo e($task['link']); ?>" target="_blank" rel="noopener">Learn more</a>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($isCompleted): ?>
                                            <span class="pill"><?php echo e($localization->translate('tasks.completed', $language)); ?></span>
                                        <?php else: ?>
                                            <form method="post" class="inline">
                                                <input type="hidden" name="action" value="complete_task">
                                                <input type="hidden" name="task_id" value="<?php echo e($taskId); ?>">
                                                <input type="hidden" name="points" value="<?php echo (int) $task['points']; ?>">
                                                <button type="submit"><?php echo e($localization->translate('tasks.complete', $language)); ?></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2><?php echo e($localization->translate('leaderboard.heading', $language)); ?></h2>
            <?php if (empty($topPlayers)): ?>
                <p class="muted"><?php echo e($localization->translate('leaderboard.empty', $language)); ?></p>
            <?php else: ?>
                <ul class="stack">
                    <?php foreach ($topPlayers as $index => $player): ?>
                        <li>
                            <?php echo e($localization->translate('leaderboard.position', $language, [
                                'rank' => $index + 1,
                                'username' => $player['username'],
                                'points' => $player['total_points'],
                            ])); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <footer>
            Built with PHP <?php echo e(PHP_VERSION); ?> · Data resets monthly · Inspired by the original WARTOLLEX bot.
        </footer>
    </div>
</body>
</html>
