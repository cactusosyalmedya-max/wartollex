"""Entry point for the WARTOLLEX Telegram bot."""
from __future__ import annotations

import asyncio
import logging
from contextlib import suppress

from aiogram import Bot, Dispatcher
from aiogram.client.default import DefaultBotProperties
from aiogram.enums import ParseMode

from config import BOT_TOKEN, LOG_LEVEL, validate_config
from database import init_db
from handlers import invite, leaderboard, points, start, tasks
from utils.time import is_within_active_range


async def main() -> None:
    validate_config()

    if not is_within_active_range():
        logging.warning("WARTOLLEX is inactive outside of 2026. Bot will not start.")
        return

    logging.basicConfig(level=getattr(logging, LOG_LEVEL.upper(), logging.INFO))

    init_db()

    dispatcher = Dispatcher()
    dispatcher.include_router(start.router)
    dispatcher.include_router(tasks.router)
    dispatcher.include_router(points.router)
    dispatcher.include_router(leaderboard.router)
    dispatcher.include_router(invite.router)

    bot = Bot(token=BOT_TOKEN, default=DefaultBotProperties(parse_mode=ParseMode.HTML))

    logging.info("Starting WARTOLLEX bot…")
    await dispatcher.start_polling(bot)


if __name__ == "__main__":
    with suppress(KeyboardInterrupt):
        asyncio.run(main())
