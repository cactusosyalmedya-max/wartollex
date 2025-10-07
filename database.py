"""Database layer for WARTOLLEX.

This module centralises all SQLite interactions. The schema is intentionally
simple to make it easy to migrate to other databases in the future.

Schema overview (also documented in README):

- users
    user_id INTEGER PRIMARY KEY
    username TEXT
    language TEXT DEFAULT 'en'
    monthly_points INTEGER DEFAULT 0
    total_points INTEGER DEFAULT 0
    monthly_period TEXT NOT NULL -- in the format YYYY-MM
    invited_by INTEGER REFERENCES users(user_id)
    created_at TEXT NOT NULL
    updated_at TEXT NOT NULL

- invites
    user_id INTEGER REFERENCES users(user_id)
    invited_user_id INTEGER REFERENCES users(user_id)
    created_at TEXT NOT NULL
    PRIMARY KEY (user_id, invited_user_id)

- task_progress
    user_id INTEGER REFERENCES users(user_id)
    task_id TEXT NOT NULL
    task_date TEXT NOT NULL -- YYYY-MM-DD
    completed INTEGER NOT NULL DEFAULT 0
    completed_at TEXT
    PRIMARY KEY (user_id, task_id, task_date)
"""
from __future__ import annotations

from contextlib import contextmanager
from dataclasses import dataclass
from datetime import datetime
import sqlite3
from typing import Iterable, Optional

from config import DB_PATH, LEADERBOARD_LIMIT

ISO_MONTH = "%Y-%m"
ISO_DATE = "%Y-%m-%d"
ISO_TIMESTAMP = "%Y-%m-%dT%H:%M:%S"


@dataclass
class User:
    user_id: int
    username: str | None
    language: str
    monthly_points: int
    total_points: int
    monthly_period: str
    invited_by: int | None
    created_at: str
    updated_at: str


def _dict_factory(cursor: sqlite3.Cursor, row: sqlite3.Row) -> dict:
    return {col[0]: row[idx] for idx, col in enumerate(cursor.description)}


@contextmanager
def get_connection() -> Iterable[sqlite3.Connection]:
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = _dict_factory
    try:
        yield conn
    finally:
        conn.close()


def init_db() -> None:
    with get_connection() as conn:
        cur = conn.cursor()
        cur.execute(
            """
            CREATE TABLE IF NOT EXISTS users (
                user_id INTEGER PRIMARY KEY,
                username TEXT,
                language TEXT DEFAULT 'en',
                monthly_points INTEGER DEFAULT 0,
                total_points INTEGER DEFAULT 0,
                monthly_period TEXT NOT NULL,
                invited_by INTEGER,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                FOREIGN KEY(invited_by) REFERENCES users(user_id)
            )
            """
        )
        cur.execute(
            """
            CREATE TABLE IF NOT EXISTS invites (
                user_id INTEGER NOT NULL,
                invited_user_id INTEGER NOT NULL,
                created_at TEXT NOT NULL,
                PRIMARY KEY(user_id, invited_user_id),
                FOREIGN KEY(user_id) REFERENCES users(user_id),
                FOREIGN KEY(invited_user_id) REFERENCES users(user_id)
            )
            """
        )
        cur.execute(
            """
            CREATE TABLE IF NOT EXISTS task_progress (
                user_id INTEGER NOT NULL,
                task_id TEXT NOT NULL,
                task_date TEXT NOT NULL,
                completed INTEGER NOT NULL DEFAULT 0,
                completed_at TEXT,
                PRIMARY KEY(user_id, task_id, task_date),
                FOREIGN KEY(user_id) REFERENCES users(user_id)
            )
            """
        )
        conn.commit()


def _current_month() -> str:
    return datetime.utcnow().strftime(ISO_MONTH)


def _current_timestamp() -> str:
    return datetime.utcnow().strftime(ISO_TIMESTAMP)


def get_or_create_user(
    user_id: int,
    username: Optional[str],
    *,
    language: str,
    invited_by: Optional[int] = None,
) -> tuple[User, bool]:
    now = _current_timestamp()
    current_period = _current_month()
    with get_connection() as conn:
        cur = conn.cursor()
        cur.execute("SELECT * FROM users WHERE user_id = ?", (user_id,))
        row = cur.fetchone()
        if row:
            _ensure_month_reset(conn, row)
            if row["monthly_period"] != _current_month():
                cur.execute("SELECT * FROM users WHERE user_id = ?", (user_id,))
                row = cur.fetchone() or row
            return User(**row), False
        cur.execute(
            """
            INSERT INTO users (user_id, username, language, monthly_points, total_points, monthly_period, invited_by, created_at, updated_at)
            VALUES (?, ?, ?, 0, 0, ?, ?, ?, ?)
            """,
            (user_id, username, language, current_period, invited_by, now, now),
        )
        conn.commit()
        return User(
            user_id=user_id,
            username=username,
            language=language,
            monthly_points=0,
            total_points=0,
            monthly_period=current_period,
            invited_by=invited_by,
            created_at=now,
            updated_at=now,
        ), True


def _ensure_month_reset(conn: sqlite3.Connection, row: dict) -> None:
    current_period = _current_month()
    if row["monthly_period"] != current_period:
        cur = conn.cursor()
        cur.execute(
            """
            UPDATE users
            SET monthly_points = 0,
                monthly_period = ?,
                updated_at = ?
            WHERE user_id = ?
            """,
            (current_period, _current_timestamp(), row["user_id"]),
        )
        conn.commit()


def update_username(user_id: int, username: Optional[str]) -> None:
    with get_connection() as conn:
        cur = conn.cursor()
        cur.execute(
            """
            UPDATE users
            SET username = ?,
                updated_at = ?
            WHERE user_id = ?
            """,
            (username, _current_timestamp(), user_id),
        )
        conn.commit()


def update_language(user_id: int, language: str) -> None:
    with get_connection() as conn:
        cur = conn.cursor()
        cur.execute(
            """
            UPDATE users
            SET language = ?,
                updated_at = ?
            WHERE user_id = ?
            """,
            (language, _current_timestamp(), user_id),
        )
        conn.commit()


def add_points(user_id: int, amount: int) -> None:
    with get_connection() as conn:
        cur = conn.cursor()
        now = _current_timestamp()
        current_period = _current_month()
        cur.execute("SELECT * FROM users WHERE user_id = ?", (user_id,))
        row = cur.fetchone()
        if not row:
            raise ValueError(f"User {user_id} does not exist")
        if row["monthly_period"] != current_period:
            _ensure_month_reset(conn, row)
            cur.execute("SELECT * FROM users WHERE user_id = ?", (user_id,))
            row = cur.fetchone()
        cur.execute(
            """
            UPDATE users
            SET monthly_points = monthly_points + ?,
                total_points = total_points + ?,
                updated_at = ?
            WHERE user_id = ?
            """,
            (amount, amount, now, user_id),
        )
        conn.commit()


def record_invite(user_id: int, invited_user_id: int) -> None:
    with get_connection() as conn:
        cur = conn.cursor()
        cur.execute(
            """
            INSERT OR IGNORE INTO invites (user_id, invited_user_id, created_at)
            VALUES (?, ?, ?)
            """,
            (user_id, invited_user_id, _current_timestamp()),
        )
        conn.commit()


def set_task_completion(user_id: int, task_id: str, task_date: str, completed: bool) -> None:
    with get_connection() as conn:
        cur = conn.cursor()
        cur.execute(
            """
            INSERT INTO task_progress (user_id, task_id, task_date, completed, completed_at)
            VALUES (?, ?, ?, ?, ?)
            ON CONFLICT(user_id, task_id, task_date)
            DO UPDATE SET completed = excluded.completed,
                          completed_at = excluded.completed_at
            """,
            (
                user_id,
                task_id,
                task_date,
                int(completed),
                _current_timestamp() if completed else None,
            ),
        )
        conn.commit()


def get_task_status(user_id: int, task_id: str, task_date: str) -> bool:
    with get_connection() as conn:
        cur = conn.cursor()
        cur.execute(
            "SELECT completed FROM task_progress WHERE user_id = ? AND task_id = ? AND task_date = ?",
            (user_id, task_id, task_date),
        )
        row = cur.fetchone()
        return bool(row["completed"]) if row else False


def get_daily_task_summary(user_id: int, task_date: str) -> dict[str, bool]:
    with get_connection() as conn:
        cur = conn.cursor()
        cur.execute(
            "SELECT task_id, completed FROM task_progress WHERE user_id = ? AND task_date = ?",
            (user_id, task_date),
        )
        rows = cur.fetchall()
        return {row["task_id"]: bool(row["completed"]) for row in rows}


def get_points(user_id: int) -> tuple[int, int]:
    with get_connection() as conn:
        cur = conn.cursor()
        cur.execute("SELECT monthly_points, total_points FROM users WHERE user_id = ?", (user_id,))
        row = cur.fetchone()
        if not row:
            raise ValueError(f"User {user_id} does not exist")
        return row["monthly_points"], row["total_points"]


def get_leaderboard(limit: int = LEADERBOARD_LIMIT) -> list[dict]:
    with get_connection() as conn:
        cur = conn.cursor()
        cur.execute(
            """
            SELECT user_id, username, monthly_points, total_points
            FROM users
            ORDER BY monthly_points DESC, total_points DESC, updated_at ASC
            LIMIT ?
            """,
            (limit,),
        )
        return cur.fetchall()


def has_completed_task(user_id: int, task_id: str, task_date: str) -> bool:
    return get_task_status(user_id, task_id, task_date)


__all__ = [
    "User",
    "init_db",
    "get_or_create_user",
    "update_username",
    "update_language",
    "add_points",
    "record_invite",
    "set_task_completion",
    "get_task_status",
    "get_daily_task_summary",
    "get_points",
    "get_leaderboard",
    "has_completed_task",
]
