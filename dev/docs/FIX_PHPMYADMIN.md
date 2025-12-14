# Fixing phpMyAdmin Connection Issues

## Quick Fix - Use Migration Script Instead

Since phpMyAdmin is not connecting, I've created a direct PHP migration script:

1. **Open your browser and go to:**
   ```
   http://localhost/user-management/database/migrate.php
   ```

2. The script will automatically:
   - Connect to your database
   - Add the `github_id` column
   - Make password nullable
   - Show you the updated table structure

3. Once you see "Migration completed successfully!", you're done!

---

## Alternative: Fix phpMyAdmin Config

If you want to fix phpMyAdmin for future use:

### Option 1: Edit phpMyAdmin Config File

1. Open `C:\xampp\phpMyAdmin\config.inc.php` in a text editor

2. Find these lines (around line 21-25):
   ```php
   $cfg['Servers'][$i]['auth_type'] = 'config';
   $cfg['Servers'][$i]['user'] = 'root';
   $cfg['Servers'][$i]['password'] = '';
   ```

3. Change the password line to:
   ```php
   $cfg['Servers'][$i]['password'] = 'root';
   ```

4. Save the file and refresh phpMyAdmin

### Option 2: Use Cookie Authentication

1. Open `C:\xampp\phpMyAdmin\config.inc.php`

2. Change the auth_type to 'cookie':
   ```php
   $cfg['Servers'][$i]['auth_type'] = 'cookie';
   ```

3. Save and refresh phpMyAdmin
4. You'll be prompted to login with username: `root` and password: `root`

---

## Recommended: Use the Migration Script

For now, just use the migration script (http://localhost/user-management/database/migrate.php) - it's faster and doesn't require fixing phpMyAdmin!
