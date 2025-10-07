"""Handlers for point summaries."""
from __future__ import annotations

from aiogram import Router
from aiogram.filters import Command
from aiogram.types import Message

from database import get_or_create_user, get_points
from utils.localization import get_text
from utils.time import is_within_active_range

router = Router(name="points")


@router.message(Command("points"))
async def handle_points(message: Message) -> None:
    if not is_within_active_range():
        await message.answer(get_text("outside_range"))
        return

    user, _ = get_or_create_user(
        message.from_user.id,
        message.from_user.username,
        language=(message.from_user.language_code or "en").split("-")[0],
    )
    monthly, total = get_points(user.user_id)
    await message.answer(get_text("points_summary", language=user.language, monthly=monthly, total=total))


__all__ = ["router"]
