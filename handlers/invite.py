"""Referral system handlers."""
from __future__ import annotations

from aiogram import Router
from aiogram.filters import Command
from aiogram.types import Message

from config import REFERRAL_BONUS
from database import get_or_create_user
from utils.localization import get_text
from utils.time import is_within_active_range

router = Router(name="invite")


@router.message(Command("invite"))
async def handle_invite(message: Message) -> None:
    if not is_within_active_range():
        await message.answer(get_text("outside_range"))
        return

    user, _ = get_or_create_user(
        message.from_user.id,
        message.from_user.username,
        language=(message.from_user.language_code or "en").split("-")[0],
    )

    bot = message.bot
    me = await bot.get_me()
    link = f"https://t.me/{me.username}?start={user.user_id}"
    await message.answer(
        get_text(
            "invite_message",
            language=user.language,
            bonus=REFERRAL_BONUS,
            link=link,
        )
    )


__all__ = ["router"]
