# WARTOLLEX Daily Hub (PHP Edition)

WARTOLLEX Daily Hub is a lightweight PHP web experience inspired by the original
Telegram bot. Players create a profile, complete daily quests, earn points, and
climb the leaderboard — all from a single-page PHP app.

## Features

- ✅ Daily quests loaded from `data/tasks.json`
- ✅ Monthly point resets (automatically triggered on login)
- ✅ Referral bonuses via shareable invite codes
- ✅ Multi-language interface (English + Spanish by default)
- ✅ SQLite persistence with automatic migrations
- ✅ Zero Telegram dependencies — everything runs in PHP

## Project structure

```
.
├── bootstrap.php          # Bootstraps the application
├── config.php             # Global configuration
├── data/
│   ├── tasks.json         # Daily quest catalogue
│   └── wartollex.sqlite   # SQLite database (created on first run)
├── public/
│   └── index.php          # Single page application entrypoint
├── src/
│   ├── Database.php
│   └── Services/
│       ├── LeaderboardService.php
│       ├── Localization.php
│       ├── TaskService.php
│       └── UserService.php
└── README.md
```

## Requirements

- PHP 8.1+
- SQLite 3 (bundled with PHP on most systems)

## Quick start

1. **Install dependencies**: none required beyond PHP itself.
2. **Start the built-in PHP server**:
   ```bash
   php -S localhost:8000 -t public
   ```
3. **Open the app**: visit http://localhost:8000 in your browser.

On first launch the application will create `data/wartollex.sqlite` and run the
necessary migrations.

## Customising quests & languages

- **Quests**: edit `data/tasks.json`. Use the `"default"` array for
  evergreen tasks, and add date-specific overrides using `YYYY-MM-DD` keys.
- **Translations**: extend `src/Services/Localization.php` with additional
  language arrays and register the codes in `config.php`.

## Data model

```sql
CREATE TABLE users (
    user_id INTEGER PRIMARY KEY,
    username TEXT NOT NULL,
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

## Referral system

Each user receives an invite code derived from their user ID. Sharing the URL
`/?ref=CODE` pre-fills the registration referral field; completed referrals grant
the inviter a 15 point bonus.

## Contributing

Pull requests are welcome! Please open an issue before major changes, keep the
PHP code strict-typed, and follow conventional commit guidelines if possible.
