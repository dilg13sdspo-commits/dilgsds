# ⚡ Quick Start: Local Setup & Dashboard Testing

## TL;DR - Get Started in 3 Steps

### 1️⃣ Create Database & User
```
In phpMyAdmin (http://localhost/phpmyadmin):
- Create database: dilg_sds
- Paste & execute: database.sql (from project root)
- Create a dedicated database user with a unique, strong password → Grant access only to dilg_sds
```

### 2️⃣ Update Database Config
Edit: `auth/config.local.php`
```php
'db_host' => '127.0.0.1',
'db_name' => 'dilg_sds',
'db_user' => 'dilg_dev',
'db_pass' => 'your-unique-database-password',
```

### 3️⃣ Create the Administrator Account
```powershell
"C:\xampp\php\php.exe" auth/init_admin.php
```

Use the unique credentials created for your local accounts. This project has no shared/default login passwords.

---

## 🎯 What to Test

After logging in with each account:

✅ Dashboard loads and shows correct metrics
✅ Logout button works
✅ Try accessing another role's dashboard URL → Should redirect
✅ Check ADMIN dashboard audit logs
✅ Test responsive design (mobile/tablet)
✅ Review card layouts and spacing
✅ Inspect table formatting for reports

---

## 🚀 Testing Flow

1. `http://localhost/DILG%20Website22/index.html`
2. Click "Login"
3. Enter the administrator credentials you created
4. Inspect `/admin-dashboard.php`
5. Click "Logout"
6. Repeat with other accounts

---

## ⚠️ Remove Before Production

```bash
# Update auth config for production:
# - Use environment variables instead of hardcoding
# - Set APP_ENV=production, DB_* variables, and serve the site over HTTPS
# - Set cookie_secure = true (if using HTTPS)
```

---

## 🔗 Reference Files

- **Setup Guide**: `AUTH_SETUP_GUIDE.md` (comprehensive)
- **Initial Admin Setup**: `auth/init_admin.php` (CLI only)
- **Config File**: `auth/config.local.php` (database credentials)
- **Middleware**: `auth/middleware.php` (role enforcement)
- **Login API**: `auth/login.php` (authentication backend)
- **Client Logic**: `script.js` (login modal & role redirects)

---

**Never deploy shared/default account passwords.**
