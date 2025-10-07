"""Helper utilities for loading and formatting daily tasks."""
from __future__ import annotations

import json
from dataclasses import dataclass
from pathlib import Path
from typing import Iterable, Optional

from config import TASKS_FILE


@dataclass(slots=True)
class Task:
    task_id: str
    description: str
    points: int
    link: Optional[str] = None

    def display(self) -> str:
        if self.link:
            return f"{self.description} ({self.points} pts)\n{self.link}"
        return f"{self.description} ({self.points} pts)"


def load_tasks_config(path: Path = TASKS_FILE) -> dict:
    if not path.exists():
        return {}
    with path.open("r", encoding="utf-8") as file:
        return json.load(file)


def get_tasks_for_date(target_date: str, *, config: Optional[dict] = None) -> list[Task]:
    config = config or load_tasks_config()
    daily_config = config.get(target_date) or config.get("default") or []
    tasks: list[Task] = []
    for raw in daily_config:
        tasks.append(
            Task(
                task_id=raw["id"],
                description=raw["description"],
                points=int(raw["points"]),
                link=raw.get("link"),
            )
        )
    return tasks


def format_tasks(tasks: Iterable[Task]) -> str:
    return "\n\n".join(task.display() for task in tasks)


__all__ = ["Task", "load_tasks_config", "get_tasks_for_date", "format_tasks"]
