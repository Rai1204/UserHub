# Developer Utilities

This folder contains development and debugging utilities that are not needed for production.

## Folders

### debug/
- **debug_password.php** - Password verification debugging tool
- **fix_github_password.php** - One-time script to fix GitHub user passwords
- **fix_github_timestamps.php** - One-time script to fix NULL timestamps
- **view_redis.php** - Redis session viewer for debugging

### migrations/
- **schema.sql** - Initial database schema (in database/)
- **add_github_support.sql** - GitHub OAuth migration
- **add_last_login.sql** - Last login timestamp migration
- **migrate.php** (2 copies) - PHP migration runner

### docs/
- **FIX_PHPMYADMIN.md** - phpMyAdmin connection troubleshooting
- **GITHUB_OAUTH_SETUP.md** - GitHub OAuth setup instructions

## Usage

These files are for development purposes only. Do not deploy to production.
