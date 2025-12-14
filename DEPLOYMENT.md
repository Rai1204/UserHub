# 🚀 Deployment Guide - Render + External Free Services

## Complete FREE Deployment Stack

This guide will help you deploy UserHub using 100% free services:
- **Render.com**: PHP Backend (Free tier)
- **MongoDB Atlas**: Database (Free 512MB)
- **Redis Cloud**: Cache/Sessions (Free 30MB)
- **Aiven MySQL**: Database (Free 5GB)

---

## Step 1: Setup External Databases (15 minutes)

### 🍃 MongoDB Atlas Setup

1. Go to [mongodb.com/cloud/atlas/register](https://www.mongodb.com/cloud/atlas/register)
2. Sign up (no credit card needed)
3. Create a cluster:
   - Choose **M0 Free** tier
   - Region: **Mumbai** (closest to India)
   - Cluster Name: `userhub`
4. Create database user:
   - Click **Database Access** → Add New User
   - Username: `userhub_user`
   - Password: Generate secure password (save it!)
5. Configure network access:
   - Click **Network Access** → Add IP Address
   - Choose: **Allow Access from Anywhere** (0.0.0.0/0)
6. Get connection string:
   - Click **Connect** → Connect your application
   - Copy connection string:
   ```
   mongodb+srv://userhub_user:<password>@cluster.mongodb.net/user_management
   ```
   - Replace `<password>` with your actual password

### 🔴 Redis Cloud Setup

1. Go to [redis.com/try-free](https://redis.com/try-free/)
2. Sign up (no credit card needed)
3. Create database:
   - Choose **Essentials** plan (Free)
   - Cloud: **AWS**
   - Region: **ap-south-1** (Mumbai)
   - Database name: `userhub-sessions`
4. Get credentials from **Configuration** tab:
   ```
   Host: redis-xxxxx.c1.asia-south1-1.gce.cloud.redislabs.com
   Port: 12345
   Password: your_password_here
   ```

**Alternative:** Upstash Redis
- [console.upstash.com](https://console.upstash.com)
- Create database → Get connection details

### 🗄️ MySQL Setup (Aiven)

1. Go to [console.aiven.io/signup](https://console.aiven.io/signup)
2. Sign up with email (no credit card)
3. Create MySQL service:
   - Service: **MySQL**
   - Cloud: **AWS**
   - Region: **Mumbai** (asia-south1)
   - Plan: **Hobbyist** (Free)
   - Service name: `userhub-mysql`
4. Wait for service to start (~5 minutes)
5. Get connection details from **Overview** tab:
   ```
   Host: mysql-xxxxx-userhub-mysql.aivencloud.com
   Port: 13999
   User: avnadmin
   Password: shown in overview
   Database: defaultdb
   ```
6. Import schema:
   - Download MySQL CA certificate from overview
   - Use MySQL Workbench or phpMyAdmin to connect
   - Import `database/schema.sql`

**Alternative:** FreeSQLDatabase.com (5MB free)
- [freesqldatabase.com](https://www.freesqldatabase.com)
- Fill form → Get instant credentials
- PhpMyAdmin access included

---

## Step 2: Deploy on Render (10 minutes)

### 🌐 Render Setup

1. Go to [render.com](https://render.com)
2. Sign up with **GitHub** account
3. Click **New +** → **Web Service**
4. Connect your repository:
   - Click **Connect account** → Authorize GitHub
   - Select repository: `rai1204/UserHub`
5. Configure service:
   ```
   Name: userhub
   Region: Singapore (closest)
   Branch: main
   Runtime: Docker
   ```
6. Render will auto-detect `Dockerfile` ✅
7. Plan: **Free** (select free tier)

### 🔐 Environment Variables

Click **Advanced** → Add environment variables:

```bash
# MySQL (from Aiven)
DB_HOST=mysql-xxxxx-userhub-mysql.aivencloud.com
DB_PORT=13999
DB_NAME=defaultdb
DB_USER=avnadmin
DB_PASS=your_aiven_password

# MongoDB (from Atlas)
MONGODB_URI=mongodb+srv://userhub_user:password@cluster.mongodb.net/
MONGODB_DB=user_management

# Redis (from Redis Cloud)
REDIS_HOST=redis-xxxxx.cloud.redislabs.com
REDIS_PORT=12345
REDIS_PASSWORD=your_redis_password

# Gmail SMTP
GMAIL_USERNAME=your_email@gmail.com
GMAIL_APP_PASSWORD=your_gmail_app_password
```

8. Click **Create Web Service**
9. Wait for deployment (~5-10 minutes)
10. Get your URL: `https://userhub.onrender.com` 🎉

---

## Step 3: Test Your Deployment

1. Visit your Render URL
2. Test registration with Gmail:
   - Enter @gmail.com email
   - Receive 6-digit verification code
   - Complete signup
3. Test login with credentials
4. Test profile page:
   - Update profile info
   - Upload profile picture
   - Generate QR code
5. Test forgot password:
   - Request OTP
   - Verify and login

---

## ⚠️ Important Notes

### Free Tier Limitations:

**Render Free Tier:**
- ⏱️ App sleeps after **15 minutes** of inactivity
- 🐢 First request takes **30-60 seconds** to wake up
- 📊 **750 hours/month** free (enough for demos)
- 💾 No persistent storage for uploads (files deleted on restart)

**Workarounds:**
- Use [cron-job.org](https://cron-job.org) to ping your app every 14 minutes
- Mention "hosted on free tier" when sharing
- For production, upgrade to paid tier ($7/month)

**Database Limits:**
- MongoDB Atlas: 512MB storage
- Redis Cloud: 30MB, 30 connections
- Aiven MySQL: 5GB storage

### File Uploads Issue:

Render free tier uses **ephemeral storage** - uploaded profile pictures will be deleted on app restart!

**Solutions:**
1. Upgrade to Render paid tier ($7/month)
2. Use external storage:
   - Cloudinary (free 25GB/month)
   - ImgBB (free image hosting)
   - AWS S3 (with free tier)

---

## 🔄 Updates & Redeployment

Every time you push to GitHub `main` branch, Render will auto-deploy!

Manual redeploy:
1. Go to Render dashboard
2. Click your service
3. Click **Manual Deploy** → Deploy latest commit

---

## 🐛 Troubleshooting

### "Connection Error" in logs:
- Check environment variables are correct
- Verify database services are running
- Check network access rules (IP whitelist)

### App not waking up:
- Render free tier takes 30-60 seconds on first request
- Use cron job to keep alive

### Emails not sending:
- Verify Gmail app password is correct
- Check Gmail "Less secure apps" setting
- Ensure 2FA is enabled and app password generated

### Database connection failed:
- Test connection strings locally first
- Check firewall rules on database providers
- Verify SSL/TLS settings if required

---

## 💰 Cost Summary

| Service | Plan | Cost | Storage |
|---------|------|------|---------|
| Render | Free | $0 | Ephemeral |
| MongoDB Atlas | M0 | $0 | 512MB |
| Redis Cloud | Essentials | $0 | 30MB |
| Aiven MySQL | Hobbyist | $0 | 5GB |
| **Total** | | **$0/month** | |

---

## 🚀 Production Upgrade Path

When ready for production:

1. **Render Paid Tier** ($7/month):
   - Persistent storage
   - No sleep
   - Custom domain support

2. **DigitalOcean** (with student pack):
   - $200 credit = 33 months free
   - Full control
   - All databases on one server

3. **Custom Domain**:
   - Get free `.me` domain (GitHub Student Pack)
   - Configure DNS in Render
   - Add SSL certificate (auto-managed)

---

## 📞 Support

Need help? Check:
- Render Docs: [render.com/docs](https://render.com/docs)
- MongoDB Atlas Docs: [docs.atlas.mongodb.com](https://docs.atlas.mongodb.com)
- GitHub Issues: Create issue in your repo

---

**Your shareable link:** `https://userhub.onrender.com`

Share this link anywhere - it's publicly accessible! 🌍
