"""Simple localisation helper for WARTOLLEX."""
from __future__ import annotations

from typing import Any

from config import DEFAULT_LANGUAGE

TRANSLATIONS: dict[str, dict[str, str]] = {
    "en": {
        "start_welcome": "Welcome to WARTOLLEX! Complete daily tasks to earn points.",
        "start_referral": "You were invited by {inviter}. They just earned a referral bonus!",
        "tasks_header": "Here are your tasks for today:",
        "task_completed": "✅ Completed",
        "task_incomplete": "⬜ Pending",
        "points_summary": "Monthly points: {monthly}\nTotal points: {total}",
        "leaderboard_title": "🏆 WARTOLLEX Leaderboard",
        "leaderboard_empty": "No one has earned points yet. Be the first!",
        "leaderboard_entry": "{rank}. {name} — {points} pts",
        "invite_message": "Share this link to invite friends and earn {bonus} bonus points: {link}",
        "outside_range": "WARTOLLEX is only active during 2026. Come back later!",
        "already_completed": "You already completed this task today.",
        "completed_now": "Great! You've earned {points} points for '{task}'.",
        "reset_monthly": "Monthly points reset for the new month!",
        "unknown_error": "Something went wrong. Please try again later.",
        "language_updated": "Your language preference has been updated to {language}.",
        "not_authorised": "You are not authorised to perform this action.",
        "tasks_unavailable": "Task configuration is missing. Contact an admin.",
    },
    "es": {
        "start_welcome": "¡Bienvenido a WARTOLLEX! Completa tareas diarias para ganar puntos.",
        "start_referral": "Has sido invitado por {inviter}. ¡Acaban de recibir un bono!",
        "tasks_header": "Estas son tus tareas de hoy:",
        "task_completed": "✅ Completado",
        "task_incomplete": "⬜ Pendiente",
        "points_summary": "Puntos mensuales: {monthly}\nPuntos totales: {total}",
        "leaderboard_title": "🏆 Clasificación WARTOLLEX",
        "leaderboard_empty": "Nadie ha ganado puntos todavía. ¡Sé el primero!",
        "leaderboard_entry": "{rank}. {name} — {points} pts",
        "invite_message": "Comparte este enlace para invitar amigos y ganar {bonus} puntos extra: {link}",
        "outside_range": "WARTOLLEX solo está activo durante 2026. ¡Vuelve más tarde!",
        "already_completed": "Ya completaste esta tarea hoy.",
        "completed_now": "¡Genial! Has ganado {points} puntos por '{task}'.",
        "reset_monthly": "¡Puntos mensuales reiniciados para el nuevo mes!",
        "unknown_error": "Algo salió mal. Inténtalo de nuevo más tarde.",
        "language_updated": "Tu idioma ha sido actualizado a {language}.",
        "not_authorised": "No estás autorizado para realizar esta acción.",
        "tasks_unavailable": "Falta la configuración de tareas. Contacta con un administrador.",
    },
}


SUPPORTED_LANGUAGES = set(TRANSLATIONS)


def get_text(key: str, language: str | None = None, **kwargs: Any) -> str:
    """Fetch a translated message for the provided language."""
    language = language or DEFAULT_LANGUAGE
    if language not in TRANSLATIONS:
        language = DEFAULT_LANGUAGE
    template = TRANSLATIONS[language].get(key) or TRANSLATIONS[DEFAULT_LANGUAGE].get(key, key)
    return template.format(**kwargs)


__all__ = ["TRANSLATIONS", "SUPPORTED_LANGUAGES", "get_text"]
