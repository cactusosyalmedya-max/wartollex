<?php
declare(strict_types=1);

namespace Wartollex\Services;

final class Localization
{
    private array $translations;
    private string $defaultLanguage;

    public function __construct(array $translations, string $defaultLanguage)
    {
        $this->translations = $translations;
        $this->defaultLanguage = $defaultLanguage;
    }

    public function translate(string $key, string $language, array $replace = []): string
    {
        $language = $this->translations[$language] ?? $this->translations[$this->defaultLanguage] ?? [];
        $fallback = $this->translations[$this->defaultLanguage] ?? [];
        $value = $language[$key] ?? $fallback[$key] ?? $key;

        foreach ($replace as $search => $replacement) {
            $value = str_replace(':' . $search, (string) $replacement, $value);
        }

        return $value;
    }

    public static function defaults(): self
    {
        $translations = [
            'en' => [
                'app.title' => 'WARTOLLEX Daily Hub',
                'app.subtitle' => 'Complete quests, earn points, and climb the leaderboard—no Telegram required.',
                'registration.title' => 'Create your profile',
                'registration.username' => 'Username',
                'registration.language' => 'Language',
                'registration.submit' => 'Start earning',
                'registration.referral' => 'Referral code (optional)',
                'tasks.heading' => 'Today\'s tasks',
                'tasks.complete' => 'Mark as completed',
                'tasks.completed' => 'Completed',
                'tasks.points' => ':points pts',
                'tasks.none' => 'No tasks configured for today. Check back soon!',
                'summary.heading' => 'Your progress',
                'summary.monthly' => 'Monthly points',
                'summary.total' => 'Lifetime points',
                'summary.invite' => 'Invite friends',
                'summary.invite_code' => 'Share this code: :code',
                'leaderboard.heading' => 'Leaderboard',
                'leaderboard.empty' => 'No players yet. Complete your first task to appear here.',
                'leaderboard.position' => '#:rank :username — :points pts',
                'logout' => 'Reset session',
                'language.switch' => 'Switch language',
                'flash.registered' => 'Welcome aboard, :username! Start completing tasks below.',
                'flash.completed' => 'Task ":task" marked as completed. :points pts awarded!',
                'flash.already_completed' => 'Task already completed for today.',
                'flash.language_changed' => 'Language updated to :language.',
                'flash.username_required' => 'Username is required.',
            ],
            'es' => [
                'app.title' => 'Centro diario WARTOLLEX',
                'app.subtitle' => 'Completa misiones, gana puntos y sube en la clasificación, sin Telegram.',
                'registration.title' => 'Crea tu perfil',
                'registration.username' => 'Nombre de usuario',
                'registration.language' => 'Idioma',
                'registration.submit' => 'Comenzar',
                'registration.referral' => 'Código de referido (opcional)',
                'tasks.heading' => 'Misiones de hoy',
                'tasks.complete' => 'Marcar como completada',
                'tasks.completed' => 'Completada',
                'tasks.points' => ':points pts',
                'tasks.none' => 'No hay misiones configuradas para hoy. ¡Vuelve pronto!',
                'summary.heading' => 'Tu progreso',
                'summary.monthly' => 'Puntos mensuales',
                'summary.total' => 'Puntos acumulados',
                'summary.invite' => 'Invita a tus amigos',
                'summary.invite_code' => 'Comparte este código: :code',
                'leaderboard.heading' => 'Clasificación',
                'leaderboard.empty' => 'Aún no hay jugadores. Completa tu primera misión para aparecer aquí.',
                'leaderboard.position' => '#:rank :username — :points pts',
                'logout' => 'Reiniciar sesión',
                'language.switch' => 'Cambiar idioma',
                'flash.registered' => '¡Bienvenido, :username! Completa las misiones de abajo.',
                'flash.completed' => 'Misión ":task" completada. ¡Has ganado :points puntos!',
                'flash.already_completed' => 'La misión ya estaba completada hoy.',
                'flash.language_changed' => 'Idioma actualizado a :language.',
                'flash.username_required' => 'El nombre de usuario es obligatorio.',
            ],
        ];

        return new self($translations, 'en');
    }
}
