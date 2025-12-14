# User Management System

A complete user management system with registration, login, and profile management using MySQL, MongoDB, Redis, and jQuery AJAX.

## Features

- User Registration
- User Login with session management
- Profile management with additional details
- MySQL for user authentication data
- MongoDB for user profile data
- Redis for session storage
- Bootstrap responsive design
- jQuery AJAX for all API calls

## Folder Structure

```
user-management/
├── index.html              # Landing page
├── pages/                  # HTML pages
│   ├── register.html
│   ├── login.html
│   └── profile.html
├── assets/                 # Static assets
│   ├── css/
│   │   └── style.css
│   └── js/
│       ├── register.js
│       ├── login.js
│       └── profile.js
├── api/                    # Backend PHP files
│   ├── config/
│   │   ├── database.php    # MySQL configuration
│   │   ├── mongodb.php     # MongoDB configuration
│   │   └── redis.php       # Redis configuration
│   ├── register.php
│   ├── login.php
│   ├── get_profile.php
│   ├── update_profile.php
│   └── logout.php
├── database/
│   └── schema.sql          # MySQL schema
├── composer.json
└── README.md
```

## Setup Instructions

### 1. PHP Extensions Required

Enable the following extensions in `php.ini` (located in `C:\xampp\php\php.ini`):

```ini
extension=pdo_mysql
extension=mysqli
extension=mongodb
extension=openssl
extension=curl
```

### 2. Install Dependencies

Open terminal in the project folder and run:

```bash
cd C:\xampp\htdocs\user-management
composer install
```

### 3. Configure Gmail SMTP (For Email Verification)

The application uses Gmail SMTP to send verification codes during registration. Credentials are stored securely in a `.env` file.

#### **Step 1: Create `.env` file**

1. Copy the example file:
   ```bash
   copy .env.example .env
   ```
   Or manually create a `.env` file in the project root

2. Open `.env` and add your credentials:
   ```env
   GMAIL_USERNAME=your-email@gmail.com
   GMAIL_APP_PASSWORD=your-16-char-app-password
   ```

#### **Step 2: Generate Gmail App Password**

1. Go to your Google Account: https://myaccount.google.com/security
2. Enable **2-Step Verification** (if not already enabled)
3. Go to **App passwords**: https://myaccount.google.com/apppasswords
4. In the "App name" field, type: `UserHub Email` (or any name)
5. Click **Create**
6. Copy the **16-character password** shown (format: `xxxx xxxx xxxx xxxx`)
7. Paste it in your `.env` file as `GMAIL_APP_PASSWORD`

#### **Step 3: Important Security Notes**

- ✅ The `.env` file is in `.gitignore` and will NOT be committed to Git
- ✅ Never share your `.env` file with anyone
- ✅ Each developer should use their own Gmail credentials
- ✅ The `.env.example` file is safe to share (no real credentials)
- ⚠️ App passwords are tied to your Google account - revoke them from Google Account settings if compromised

**Example `.env` file:**
```env
GMAIL_USERNAME=john@gmail.com
GMAIL_APP_PASSWORD=abcd efgh ijkl mnop
```

### 4. Setup MySQL Database

1. Start MySQL from XAMPP Control Panel
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Import or execute the SQL file: `database/schema.sql`
4. Import the verification codes table: `database/verification_codes.sql`

### 5. Setup MongoDB

1. Start MongoDB service
2. No additional setup needed - collections will be created automatically

### 6. Setup Redis

1. Open WSL (Windows Subsystem for Linux) terminal with Ubuntu
2. Start Redis server: `sudo service redis-server start`
3. Stop Apache running in WSL (to avoid port conflicts with XAMPP): `sudo systemctl stop apache2`
4. Default configuration (127.0.0.1:6379) is already set

### 7. Configure Apache

Make sure Apache is running on XAMPP Control Panel.

### 8. Access the Application

Open your browser and navigate to:
```
http://localhost/user-management
```

## Technologies Used

- **Frontend**: HTML5, CSS3, Bootstrap 5, jQuery
- **Backend**: PHP 8.0+
- **Databases**: 
  - MySQL (user authentication)
  - MongoDB (user profiles)
  - Redis (session management)
- **Libraries**: Predis, MongoDB PHP Library

## API Endpoints

### Authentication
- `POST /api/register.php` - Register new user
- `POST /api/login.php` - Login user
- `POST /api/logout.php` - Logout user

### Profile Management
- `GET /api/get_profile.php` - Get user profile (requires Authorization header)
- `POST /api/update_profile.php` - Update user profile information
- `POST /api/upload_profile_picture.php` - Upload profile picture
- `POST /api/delete_profile_picture.php` - Delete profile picture

### Account Management
- `POST /api/change_password.php` - Change user password
- `POST /api/delete_account.php` - Delete user account
- `GET /api/get_account_stats.php` - Get account statistics

### Email Verification
- `POST /api/send_verification_code.php` - Send 6-digit verification code to Gmail

### Password Recovery
- `POST /api/check_email.php` - Check if email exists for password recovery

### GitHub OAuth
- `GET /api/github_auth.php` - Authenticate with GitHub OAuth
- `GET /api/github_callback.php` - GitHub OAuth callback handler

## Security Features

- Password hashing using bcrypt
- Prepared statements for SQL queries (prevents SQL injection)
- Session token validation
- Redis session expiration (24 hours)
- Input validation and sanitization
- **Gmail-only registration** with email verification
- **6-digit verification codes** (15-minute expiry)
- **Environment variables** for sensitive credentials (.env file)
- **.gitignore** prevents committing credentials to version control

## Registration Flow

1. User enters Gmail address (@gmail.com only)
2. System generates and sends 6-digit verification code via email
3. Code expires in 15 minutes
4. User enters code + username + password
5. System validates code and creates account
6. User can login immediately after verification

## Notes

- No PHP sessions used - all session management via localStorage and Redis
- All API calls use jQuery AJAX (no form submissions)
- Bootstrap used for responsive design
- Separate files for HTML, CSS, JS, and PHP
- **Only Gmail addresses** are allowed for registration
- **Email verification required** before account creation
- Credentials stored securely in `.env` file (not in code)
