# 🔐 Development Authentication Setup Guide

## Overview
This guide will help you set up the DILG Surigao del Sur authentication system with three temporary development accounts for testing the role-based dashboards.

## Prerequisites
- XAMPP running (Apache + MySQL + PHP)
- Access to phpMyAdmin

---

## Step 1: Create the Database and Tables

### Option A: Using phpMyAdmin (Recommended)
1. Open **phpMyAdmin** at `http://localhost/phpmyadmin`
2. Click on the **SQL** tab at the top
3. Paste the entire contents of `database.sql` (from your project root)
4. Click **Go**
5. Verify that the `dilg_sds` database was created with three tables:
   - `users`
   - `reports`
   - `audit_logs`

### Option B: Using MySQL CLI
```bash
# Open MySQL command prompt
mysql -u root

# Paste the contents of database.sql
# Or run:
mysql -u root dilg_sds < C:\xampp_new\htdocs\DILG Website22\database.sql
```

---

## Step 2: Create a Database User (Optional but Recommended)

### Via phpMyAdmin:
1. Go to **User Accounts** tab
2. Click **Add user account**
3. Fill in:
   - **Username**: `dilg_dev`
   - **Host**: `localhost`
   - **Password**: choose a unique, strong password
4. In **Privileges**, select **Grant all privileges on 'dilg_sds'.\***
5. Click **Go**

### Via MySQL CLI:
```sql
CREATE USER 'dilg_dev'@'localhost' IDENTIFIED BY 'replace-with-a-unique-strong-password';
GRANT ALL PRIVILEGES ON dilg_sds.* TO 'dilg_dev'@'localhost';
FLUSH PRIVILEGES;
```

---

## Step 3: Configure Database Credentials

The file `auth/config.local.php` has been created with connection details.

**Update it with your actual database credentials:**

1. Open: `auth/config.local.php`
2. Match the values to your database setup:
   ```php
   return [
       'db_host' => '127.0.0.1',
       'db_name' => 'dilg_sds',
       'db_user' => 'dilg_dev',          // Change if you used a different username
       'db_pass' => 'replace-with-a-unique-strong-password',
       'cookie_secure' => false,
   ];
   ```
3. Save the file

---

## Step 4: Create an Administrator Account

Create the initial administrator from a local command prompt:
```powershell
"C:\xampp\php\php.exe" auth/init_admin.php
```

Choose a unique, long password. Do not run account-seeding or setup scripts through a public web server.

---

## Step 5: Verify the Accounts Were Created

### In phpMyAdmin:
1. Open `http://localhost/phpmyadmin`
2. Click on database `dilg_sds` → table `users`
3. Verify the administrator account you created and its assigned role.

---

There are no shared/default login credentials. Create accounts locally with unique passwords, then create additional accounts through the authenticated administrator interface.

---

## 🚀 Testing the Login System

1. Open: `http://localhost/DILG%20Website22/index.html`
2. Click the **Login** button in the navigation
3. Enter any of the temporary credentials above
4. You will be redirected to the role-specific dashboard
5. Try the following:
   - Log out and log in with a different role
   - Try manually accessing another role's dashboard URL (you will be redirected)
   - Check the audit logs in the ADMIN dashboard

---

## 📋 What Each Dashboard Shows

### ADMIN Dashboard (`/admin-dashboard.php`)
- Total users count
- Total reports count
- Reports by status (Pending, Submitted, Overdue)
- Recent audit logs with IP addresses and timestamps

### FO USER Dashboard (`/fo-dashboard.php`)
- User's own pending, submitted, and overdue reports
- Personal notifications count
- User-level report management features

### PO USER Dashboard (`/po-dashboard.php`)
- Organization-wide reports summary
- All reports grouped by status
- Organization-wide monitoring and oversight features

---

## 🔒 Access Control & Security

- Each role can **only access** its authorized dashboard
- Attempting to manually navigate to another role's dashboard will redirect you to the homepage
- This is enforced in `auth/middleware.php` via the `require_role()` function
- All login attempts are logged in the `audit_logs` table

---

## ⚠️ BEFORE DEPLOYING TO PRODUCTION

2. **DELETE** the entire `auth/config.local.php` file
3. **Rotate** any account passwords that have ever been shared or stored in source files
4. **Set** `APP_ENV=production` and dedicated `DB_*` environment variables
5. **Serve** the site exclusively over HTTPS

---

## 📝 Troubleshooting

### "Access denied for user" Error
- Check that the database user was created correctly
- Verify the username and password in `auth/config.local.php` match the database
- Test the connection in phpMyAdmin using the same credentials

### "Table doesn't exist" Error
- Verify that `database.sql` was executed successfully
- Check phpMyAdmin to confirm the `dilg_sds` database and tables exist

### Login Button Not Appearing
- Check browser console for JavaScript errors
- Verify that `/auth/session_check.php` is accessible
- Ensure the PHP backend is running (not just opening HTML files)

### Dashboard Shows "Unauthorized"
- Verify you are logged in (check the browser's cookies/local storage)
- Confirm you are accessing the correct dashboard URL for your role
- Check `auth/middleware.php` to see what role is required for that page

---

## 🎯 Next Steps

After verifying the login system works:

1. **Refine Dashboard UI** — Adjust layout, cards, spacing on each dashboard
2. **Test Reports Features** — Verify report creation/updating/deletion
3. **Check Responsiveness** — Test on mobile/tablet breakpoints
4. **Review Navigation** — Ensure logout and navigation work correctly
5. **Update Public Navigation** — Finalize the public site design before removing dev accounts

---

**Questions?** Check the inline comments in:
- `auth/init_admin.php` — Initial administrator creation (CLI only)
- `auth/middleware.php` — Role-based access control logic
- `auth/login.php` — Authentication flow
- `script.js` — Client-side login handling and redirects
