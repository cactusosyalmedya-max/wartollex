"""Handlers for presenting and updating daily tasks."""
from __future__ import annotations

from aiogram import Router
from aiogram.filters import Command
from aiogram.types import CallbackQuery, InlineKeyboardButton, InlineKeyboardMarkup, Message

from database import add_points, get_daily_task_summary, get_or_create_user, set_task_completion
from utils.localization import get_text
from utils.tasks import Task, get_tasks_for_date
from utils.time import current_date_str, is_within_active_range

router = Router(name="tasks")


def _build_keyboard(tasks: list[Task], completed: dict[str, bool]) -> InlineKeyboardMarkup:
    buttons: list[list[InlineKeyboardButton]] = []
    for task in tasks:
        status = "✅" if completed.get(task.task_id) else "➕"
        buttons.append(
            [
                InlineKeyboardButton(
                    text=f"{status} {task.description[:30]}…" if len(task.description) > 30 else f"{status} {task.description}",
                    callback_data=f"task:{task.task_id}",
                )
            ]
        )
    return InlineKeyboardMarkup(inline_keyboard=buttons)


@router.message(Command("tasks"))
async def handle_tasks(message: Message) -> None:
    if not is_within_active_range():
        await message.answer(get_text("outside_range"))
        return

    today = current_date_str()
    tasks = get_tasks_for_date(today)
    if not tasks:
        await message.answer(get_text("tasks_unavailable"))
        return

    user, _ = get_or_create_user(message.from_user.id, message.from_user.username, language=(message.from_user.language_code or "en").split("-")[0])
    completed = get_daily_task_summary(user.user_id, today)

    lines = [get_text("tasks_header", language=user.language)]
    for task in tasks:
        status_key = "task_completed" if completed.get(task.task_id) else "task_incomplete"
        lines.append(f"{get_text(status_key, language=user.language)} {task.display()}")

    keyboard = _build_keyboard(tasks, completed)
    await message.answer("\n\n".join(lines), reply_markup=keyboard)


@router.callback_query(lambda c: c.data and c.data.startswith("task:"))
async def handle_task_completion(callback: CallbackQuery) -> None:
    if not callback.message:
        return
    if not is_within_active_range():
        await callback.answer(get_text("outside_range"), show_alert=True)
        return

    _, task_id = callback.data.split(":", 1)
    today = current_date_str()
    tasks = get_tasks_for_date(today)
    task_lookup = {task.task_id: task for task in tasks}
    if task_id not in task_lookup:
        await callback.answer(get_text("tasks_unavailable"), show_alert=True)
        return

    user, _ = get_or_create_user(
        callback.from_user.id,
        callback.from_user.username,
        language=(callback.from_user.language_code or "en").split("-")[0],
    )

    if get_daily_task_summary(user.user_id, today).get(task_id):
        await callback.answer(get_text("already_completed", language=user.language), show_alert=False)
        return

    task = task_lookup[task_id]
    set_task_completion(user.user_id, task_id, today, True)
    add_points(user.user_id, task.points)

    await callback.answer(
        get_text("completed_now", language=user.language, task=task.description, points=task.points),
        show_alert=False,
    )

    # Refresh the message with updated status
    completed = get_daily_task_summary(user.user_id, today)
    lines = [get_text("tasks_header", language=user.language)]
    for task in tasks:
        status_key = "task_completed" if completed.get(task.task_id) else "task_incomplete"
        lines.append(f"{get_text(status_key, language=user.language)} {task.display()}")

    await callback.message.edit_text("\n\n".join(lines), reply_markup=_build_keyboard(tasks, completed))


__all__ = ["router"]
