"""Utility helpers for dealing with time windows."""
from __future__ import annotations

from datetime import date, datetime, timezone

from config import ACTIVE_RANGE

ISO_DATE = "%Y-%m-%d"


def today_utc() -> date:
    return datetime.now(timezone.utc).date()


def current_date_str() -> str:
    return today_utc().strftime(ISO_DATE)


def is_within_active_range(target: date | None = None) -> bool:
    target = target or today_utc()
    return ACTIVE_RANGE.start <= target <= ACTIVE_RANGE.end


__all__ = ["today_utc", "current_date_str", "is_within_active_range", "ISO_DATE"]
