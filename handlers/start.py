"""Handlers related to /start and onboarding."""
from __future__ import annotations

from aiogram import Router
from aiogram.filters import CommandStart
from aiogram.types import Message

from config import REFERRAL_BONUS
from database import add_points, get_or_create_user, record_invite, update_username
from utils.localization import get_text
from utils.time import is_within_active_range

router = Router(name="start")


def _parse_referrer(args: str | None) -> int | None:
    if not args:
        return None
    try:
        ref_id = int(args)
        return ref_id if ref_id > 0 else None
    except ValueError:
        return None


@router.message(CommandStart())
async def handle_start(message: Message) -> None:
    if not is_within_active_range():
        await message.answer(get_text("outside_range"))
        return

    parts = (message.text or "").split(maxsplit=1)
    inviter_arg = parts[1] if len(parts) > 1 else None
    inviter_id = _parse_referrer(inviter_arg)
    telegram_user = message.from_user
    language = (telegram_user.language_code or "en").split("-")[0]

    user, created = get_or_create_user(
        telegram_user.id,
        telegram_user.username,
        language=language,
        invited_by=inviter_id if inviter_id and inviter_id != telegram_user.id else None,
    )

    if telegram_user.username != user.username:
        update_username(telegram_user.id, telegram_user.username)

    text = get_text("start_welcome", language=user.language)
    if inviter_id and inviter_id != telegram_user.id:
        if created and inviter_id:
            try:
                add_points(inviter_id, REFERRAL_BONUS)
                record_invite(inviter_id, telegram_user.id)
            except ValueError:
                # Inviter might not exist yet; ignore silently.
                pass
        text = (
            text
            + "\n\n"
            + get_text(
                "start_referral",
                language=user.language,
                inviter=str(inviter_id),
            )
        )

    await message.answer(text)


__all__ = ["router"]
