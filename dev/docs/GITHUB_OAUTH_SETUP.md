# GitHub OAuth Setup Guide

## Overview
GitHub OAuth allows users to sign in using their GitHub account. It's free and simpler to set up than Google OAuth!

## Step 1: Create GitHub OAuth App

1. Go to [GitHub Developer Settings](https://github.com/settings/developers)
2. Click **"OAuth Apps"** in the left sidebar
3. Click **"New OAuth App"** button
4. Fill in the application details:
   - **Application name**: User Management System
   - **Homepage URL**: `http://localhost/user-management`
   - **Authorization callback URL**: `http://localhost/user-management/api/github_callback.php`
   - **Application description**: (optional) User management system with GitHub login
5. Click **"Register application"**
6. You'll see your **Client ID** and can generate a **Client Secret**
7. Click **"Generate a new client secret"** and copy it (you'll only see it once!)

## Step 2: Update Configuration Files

### Update `api/github_auth.php`
Replace these values (around lines 9-11):
```php
$clientId = 'YOUR_GITHUB_CLIENT_ID';          // Replace with your Client ID
$clientSecret = 'YOUR_GITHUB_CLIENT_SECRET';  // Replace with your Client Secret
$redirectUri = 'http://localhost/user-management/api/github_callback.php';
```

### Update `assets/js/login.js`
Replace the Client ID (around line 85):
```javascript
const clientId = 'YOUR_GITHUB_CLIENT_ID';  // Replace with your Client ID
```

### Update `assets/js/register.js`
Replace the Client ID (around line 113):
```javascript
const clientId = 'YOUR_GITHUB_CLIENT_ID';  // Replace with your Client ID
```

## Step 3: Update Database

Run the SQL migration in phpMyAdmin:

1. Open [phpMyAdmin](http://localhost/phpmyadmin)
2. Select **user_management** database
3. Click **SQL** tab
4. Copy and paste this SQL:

```sql
USE user_management;

-- Add github_id column
ALTER TABLE users 
ADD COLUMN github_id VARCHAR(50) NULL UNIQUE AFTER password,
ADD INDEX idx_github_id (github_id);

-- Make password nullable (for GitHub OAuth users)
ALTER TABLE users 
MODIFY COLUMN password VARCHAR(255) NULL;
```

5. Click **Go** to execute

**OR** run the migration file:
```bash
# In phpMyAdmin, import the file:
database/add_github_support.sql
```

## Step 4: Test GitHub Login

1. Open `http://localhost/user-management/pages/login.html`
2. Click **"Continue with GitHub"** button
3. Authorize the application on GitHub
4. You should be redirected back and logged in automatically!

## How It Works

### For New Users:
1. User clicks "Continue with GitHub"
2. Redirected to GitHub authorization page
3. User authorizes the app
4. GitHub redirects back with authorization code
5. System exchanges code for access token
6. System fetches user info (username, email) from GitHub API
7. New account is created with GitHub ID
8. User is logged in with session token

### For Existing Users:
1. Same flow as above
2. System finds existing account by GitHub ID
3. User is logged in immediately

### Linking Existing Email:
1. If user registered with email/password
2. Then tries to login with GitHub using same email
3. System automatically links GitHub ID to existing account
4. User can now use both methods to login

## Security Features

✅ **OAuth 2.0 Protocol** - Industry standard authentication
✅ **No Password Storage** - GitHub handles authentication
✅ **Verified Email Required** - Only verified GitHub emails accepted
✅ **Account Linking** - Automatically links to existing accounts by email
✅ **Unique GitHub ID** - Prevents duplicate GitHub accounts

## Files Created

- `api/github_auth.php` - Exchanges code for token and creates user session
- `api/github_callback.php` - Receives authorization code from GitHub
- `pages/github-success.html` - Processing page after GitHub redirect
- `database/add_github_support.sql` - Database migration script

## Files Modified

- `pages/login.html` - Added "Continue with GitHub" button
- `pages/register.html` - Added "Sign up with GitHub" button
- `assets/js/login.js` - Added GitHub login handler
- `assets/js/register.js` - Added GitHub register handler
- `database/schema.sql` - Added github_id column and index

## Troubleshooting

### "No authorization code provided"
- Check that callback URL in GitHub app matches exactly: `http://localhost/user-management/api/github_callback.php`
- Make sure you replaced YOUR_GITHUB_CLIENT_ID in all files

### "Failed to obtain access token"
- Verify Client ID and Client Secret are correct in `api/github_auth.php`
- Check that you copied the full Client Secret (it's long)

### "Could not retrieve email from GitHub"
- Make sure your GitHub email is verified
- Check your [GitHub email settings](https://github.com/settings/emails)
- You may need to make your email public in GitHub settings

### "Database connection failed"
- Run the migration SQL to add github_id column
- Check MySQL is running in XAMPP

## Production Deployment

When deploying to production:

1. **Update OAuth App URLs** in GitHub:
   - Homepage URL: `https://yourdomain.com`
   - Callback URL: `https://yourdomain.com/api/github_callback.php`

2. **Update Code URLs**:
   - `api/github_auth.php` - Change `$redirectUri`
   - `assets/js/login.js` - Change `redirectUri`
   - `assets/js/register.js` - Change `redirectUri`

3. **Secure Credentials**:
   - Store Client ID and Secret in environment variables
   - Never commit secrets to version control
   - Use `.env` file or server environment variables

## Advantages Over Google OAuth

✅ **Simpler Setup** - Only 2 fields needed (Client ID & Secret)
✅ **No Verification** - App can be used immediately
✅ **Developer Friendly** - Most developers have GitHub accounts
✅ **Free Forever** - No quotas or limits
✅ **Better Privacy** - Users can review app access anytime

## Testing Checklist

- [ ] Created GitHub OAuth App
- [ ] Updated Client ID in all 3 files
- [ ] Updated Client Secret in API file
- [ ] Ran database migration
- [ ] Tested new user registration via GitHub
- [ ] Tested existing user login via GitHub
- [ ] Tested email linking (register with email, then login with GitHub)
- [ ] Verified session persistence after GitHub login
