# DILG FO and PO User Portals

This folder contains only the Field Office (FO) and Provincial Office (PO) user portal code. The original `index.php`, login page, public website, and admin dashboard remain outside this package.

## Contents

- `fo/`: FO user pages: dashboard, inquiries, reports, calendar, messages, notifications, settings, help, and FAQ guidelines.
- `po/`: PO user pages: dashboard, create inquiry, inquiries, reports, documents, office, calendar, messages, notifications, settings, and help.
- `shared/auth/`: shared PHP authentication, workflow, API endpoints, database configuration, account seeders, and migration scripts.
- `shared/uploads/`: uploaded user documents and profile photos, when present.

## Add to the original website

1. Copy the entire `user-portals` folder into the original website root. Do not copy an `index.php` or login page from this package because the original website already owns those.
2. Copy `shared/auth` into the same parent directory as `fo` and `po`.
3. Copy `shared/uploads` to the shared upload location used by the original admin.
4. Update `shared/auth/config.local.php` with the original database credentials. Do not commit that file.
5. Run the database schema/migrations in `shared/auth/setup_database.php` or import the project's database schema, then run the account seeders only when appropriate.
6. Make the original login redirect FO accounts to `user-portals/fo/fo-dashboard.php` and PO accounts to `user-portals/po/po-dashboard.php`. The original login may call `user-portals/shared/auth/login.php` as its authentication endpoint.
7. Ensure the original admin uses the same database tables: `users`, `offices`, `reports`, `report_recipients`, `report_responses`, `documents`, `notifications`, `task_comments`, `audit_logs`, `calendar_events`, `google_calendar_tokens`, and `calendar_sync_log`.

## Important integration notes

- Authentication is session-based. The login page and these portals must share the same PHP session cookie path/domain.
- The API URLs in the copied pages point to `../shared/auth/`.
- The `.htaccess` files preserve the existing short navigation URLs while the real files keep their `fo-` and `po-` names.
- Public links in the original pages were intentionally not copied. Add the original site's public-page URLs where needed.
- `shared/auth/config.local.php` contains local database settings and should be edited for the destination site.
- The original root files are unchanged; this is a copy intended for transfer.

## Main endpoints

- FO: `fo_tasks_api.php`, `fo_inquiries_api.php`, `faqs_api.php`
- PO: `po_inquiries_api.php`, `po_reports_api.php`
- Shared: `reports_api.php`, `notifications_api.php`, `settings_api.php`, `calendar_api.php`, `download_document.php`, `middleware.php`, `workflow.php`, `db.php`
