# WARTOLLEX Telegram Bot

WARTOLLEX is a Hamster Kombat–inspired Telegram bot that delivers daily content
quests throughout 2026. Users complete tasks, earn points, invite friends, and
climb the leaderboard. After December 2026 the bot automatically shuts down.

## Features

- ✅ Daily tasks with inline completion buttons
- ✅ Monthly point resets while preserving annual totals
- ✅ Referral bonuses with shareable invite links
- ✅ Top 1000 leaderboard (configurable display slice)
- ✅ Multi-language messaging (English + Spanish by default)
- ✅ SQLite persistence with simple schema (easy to swap for MongoDB)
- ✅ `.env`-driven configuration and structured logging

## Project Structure

```
.
├── bot.py                # Application entrypoint
├── config.py             # Environment loading and constants
├── database.py           # SQLite layer, schema & helpers
├── handlers/             # Command and callback routers
│   ├── invite.py
│   ├── leaderboard.py
│   ├── points.py
│   ├── start.py
│   └── tasks.py
├── utils/                # Helper utilities
│   ├── localization.py
│   └── time.py
├── data/
│   └── tasks.json        # Editable task catalogue
├── README.md
└── .env.example
```

## Prerequisites

- Python 3.10+
- Telegram Bot API token (create via [@BotFather](https://t.me/BotFather))

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-org/wartollex.git
   cd wartollex
   ```

2. **Create a virtual environment** (recommended)
   ```bash
   python3 -m venv .venv
   source .venv/bin/activate
   ```

3. **Install dependencies**
   ```bash
   pip install -r requirements.txt
   ```
   > The bot uses [`aiogram`](https://docs.aiogram.dev), `python-dotenv`, and
   > standard-library modules. Generate a `requirements.txt` using
   > `pip freeze` after installing the packages you need.

4. **Configure environment variables**
   - Copy `.env.example` to `.env`
   - Fill in `BOT_TOKEN` and any optional overrides

5. **Customise daily tasks**
   - Edit `data/tasks.json`
   - Add date-specific overrides (YYYY-MM-DD keys) as needed

## Running the bot

```bash
python bot.py
```

On startup the bot verifies the current date is within the 2026 window. Before
January 2026 and after December 2026 the bot logs a warning and exits cleanly.

## Commands

- `/start` — greet the user and (optionally) process referral codes
- `/tasks` — display daily quests with completion buttons
- `/points` — show monthly and lifetime totals
- `/leaderboard` — display the current rankings (top 1,000 stored, first N shown)
- `/invite` — generate the user’s referral link

## Database Schema

```sql
CREATE TABLE users (
    user_id INTEGER PRIMARY KEY,
    username TEXT,
    language TEXT DEFAULT 'en',
    monthly_points INTEGER DEFAULT 0,
    total_points INTEGER DEFAULT 0,
    monthly_period TEXT NOT NULL,
    invited_by INTEGER REFERENCES users(user_id),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE TABLE invites (
    user_id INTEGER NOT NULL,
    invited_user_id INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    PRIMARY KEY(user_id, invited_user_id),
    FOREIGN KEY(user_id) REFERENCES users(user_id),
    FOREIGN KEY(invited_user_id) REFERENCES users(user_id)
);

CREATE TABLE task_progress (
    user_id INTEGER NOT NULL,
    task_id TEXT NOT NULL,
    task_date TEXT NOT NULL,
    completed INTEGER NOT NULL DEFAULT 0,
    completed_at TEXT,
    PRIMARY KEY(user_id, task_id, task_date),
    FOREIGN KEY(user_id) REFERENCES users(user_id)
);
```

## Updating tasks & translations

- **Tasks**: update `data/tasks.json` manually or wire it into an admin tool.
  Use a `"default"` array and add daily overrides:
  ```json
  {
    "default": [ ... ],
    "2026-06-01": [ ... ]
  }
  ```
- **Languages**: extend `utils/localization.py` with additional language codes
  and translations.

## WARTOLLEX Trade Module (Optional)

A future enhancement is planned for a Flask-based trading academy. After a mock
payment (Stripe or Telegram Pay), users receive email access to learning
materials. The module should live alongside the bot (e.g. `trade/` directory)
and integrate with the database for licensing. This repository provides the
foundation for that integration.

## Logging & monitoring

- Logging level is configurable via `LOG_LEVEL` in `.env`
- Errors during callbacks reply with user-friendly text and can be extended to
  send admin notifications

## Contributing

Pull requests are welcome! Please open an issue before substantial changes and
follow conventional commit messages when possible.
