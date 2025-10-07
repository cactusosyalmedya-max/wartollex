"""Leaderboard handler for top performers."""
from __future__ import annotations

from aiogram import Router
from aiogram.filters import Command
from aiogram.types import Message

from config import LEADERBOARD_DISPLAY
from database import get_leaderboard, get_or_create_user
from utils.localization import get_text
from utils.time import is_within_active_range

router = Router(name="leaderboard")


@router.message(Command("leaderboard"))
async def handle_leaderboard(message: Message) -> None:
    if not is_within_active_range():
        await message.answer(get_text("outside_range"))
        return

    user, _ = get_or_create_user(
        message.from_user.id,
        message.from_user.username,
        language=(message.from_user.language_code or "en").split("-")[0],
    )

    rows = get_leaderboard()
    if not rows:
        await message.answer(get_text("leaderboard_empty", language=user.language))
        return

    display_rows = rows[:LEADERBOARD_DISPLAY]
    lines = [get_text("leaderboard_title", language=user.language)]
    for index, row in enumerate(display_rows, start=1):
        name = f"@{row['username']}" if row.get("username") else f"User {row['user_id']}"
        lines.append(
            get_text(
                "leaderboard_entry",
                language=user.language,
                rank=index,
                name=name,
                points=row["monthly_points"],
            )
        )

    await message.answer("\n".join(lines))


__all__ = ["router"]
