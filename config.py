"""Configuration settings for the WARTOLLEX Telegram bot."""
from __future__ import annotations

from dataclasses import dataclass
from datetime import date
from pathlib import Path
import os

from dotenv import load_dotenv

BASE_DIR = Path(__file__).resolve().parent
ENV_PATH = BASE_DIR / ".env"
load_dotenv(ENV_PATH)


@dataclass(frozen=True)
class DateRange:
    start: date
    end: date


BOT_NAME: str = os.getenv("BOT_NAME", "WARTOLLEX")
BOT_TOKEN: str | None = os.getenv("BOT_TOKEN")
ADMIN_IDS: set[int] = {int(admin_id) for admin_id in os.getenv("ADMIN_IDS", "").split(",") if admin_id.strip().isdigit()}

# The period during which the bot should be operational
ACTIVE_RANGE = DateRange(start=date(2026, 1, 1), end=date(2026, 12, 31))

# Database configuration
DB_PATH: Path = BASE_DIR / "wartollex.db"

# Paths for configuration data
TASKS_FILE: Path = BASE_DIR / "data" / "tasks.json"

# Points configuration
REFERRAL_BONUS: int = int(os.getenv("REFERRAL_BONUS", "25"))
DEFAULT_LANGUAGE: str = os.getenv("DEFAULT_LANGUAGE", "en")

# Leaderboard configuration
LEADERBOARD_LIMIT = 1000
LEADERBOARD_DISPLAY = int(os.getenv("LEADERBOARD_DISPLAY", "20"))

# Logging configuration
LOG_LEVEL = os.getenv("LOG_LEVEL", "INFO")


class MissingTokenError(RuntimeError):
    """Raised when the BOT_TOKEN is missing from the environment."""


def validate_config() -> None:
    """Ensure that critical configuration is present."""
    if not BOT_TOKEN:
        raise MissingTokenError(
            "BOT_TOKEN was not found. Please create a .env file with your Telegram bot token."
        )


__all__ = [
    "BASE_DIR",
    "BOT_NAME",
    "BOT_TOKEN",
    "ADMIN_IDS",
    "ACTIVE_RANGE",
    "DB_PATH",
    "TASKS_FILE",
    "REFERRAL_BONUS",
    "DEFAULT_LANGUAGE",
    "LEADERBOARD_LIMIT",
    "LEADERBOARD_DISPLAY",
    "LOG_LEVEL",
    "validate_config",
    "MissingTokenError",
]
