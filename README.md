# Changelog Tracker

A Laravel + Filament application that monitors GitHub repositories for new releases and posts changelog notifications to Slack via Block Kit messages.

## Features

- **GitHub Release Monitoring** — Automatically checks repositories for new releases on a configurable interval
- **Slack Notifications** — Sends rich Block Kit messages with changelog body, release metadata, and a link to GitHub
- **Filament Admin Panel** — Manage projects, releases, and Slack channels through a polished UI
- **Quick Project Setup** — Add a project by pasting a GitHub URL; metadata is fetched automatically
- **Retry Logic** — Failed notifications are retried automatically (configurable max attempts and time window)
- **Pre-release Support** — Optionally include pre-releases per project

## Tech Stack

- PHP 8.5 / Laravel 12
- Filament v3
- SQLite
- DDEV
- Pest v4
- Tailwind CSS v4

## Requirements

- [DDEV](https://ddev.readthedocs.io/en/stable/)
- Docker

## Installation

```bash
git clone <repo-url> changelog-tracker
cd changelog-tracker

ddev start
ddev composer install
ddev exec bash -c "npm install && npm run build"
cp .env.example .env
ddev exec php artisan key:generate
ddev exec php artisan migrate --seed
```

## Configuration

Add your GitHub personal access token to `.env`:

```
GITHUB_TOKEN=ghp_your_token_here
```

A token is optional but recommended to avoid GitHub API rate limits (60 requests/hour unauthenticated vs 5,000 authenticated).

Additional settings are available in `config/changelog-tracker.php`:

| Setting | Default | Description |
|---------|---------|-------------|
| `defaults.check_interval_minutes` | `30` | How often to check for new releases |
| `defaults.include_prereleases` | `false` | Whether to include pre-releases by default |
| `notifications.max_attempts` | `3` | Max notification retry attempts |
| `notifications.retry_window_hours` | `24` | Time window for retrying failed notifications |

## Usage

### Admin Panel

Visit [https://changelog-tracker.ddev.site/admin](https://changelog-tracker.ddev.site/admin) and log in with the seeded credentials:

- **Email:** `admin@binary-hype.com`
- **Password:** `password`

### Adding a Project

1. Go to **Projects** → **New Project**
2. Paste a GitHub URL (e.g. `https://github.com/laravel/framework`) or shorthand (`laravel/framework`)
3. The project name and description are fetched automatically
4. Edit the project to adjust check interval, pre-release settings, and linked Slack channels

### Adding a Slack Channel

1. Create an [Incoming Webhook](https://api.slack.com/messaging/webhooks) in your Slack workspace
2. Go to **Slack Channels** → **New Slack Channel**
3. Enter a name and the webhook URL
4. Use **Send Test Message** to verify the connection

### Artisan Commands

```bash
# Check all due projects for new releases and notify Slack
ddev exec php artisan app:check-releases

# Retry failed notifications
ddev exec php artisan app:retry-notifications
```

Both commands are scheduled automatically: `check-releases` every 15 minutes, `retry-notifications` hourly. Start the scheduler with:

```bash
ddev exec php artisan schedule:work
```

## Testing

```bash
ddev exec php artisan test
```

## Code Style

```bash
ddev exec vendor/bin/pint
```
