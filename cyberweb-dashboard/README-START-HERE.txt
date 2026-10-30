CYBERWEB DASHBOARD — ADMIN LOGIN (LOCAL XAMPP DEMO)

IMPORTANT: This is an older setup note. For the CURRENT project -> hotel details ->
templates -> final preview workflow, read README-NEW-HOTEL-WORKFLOW.txt first.
The setup_admin.php file mentioned below is NOT included in this ZIP. Use your
existing database user account; do not assume the demo credentials will work.

1. Back up your original cyberweb-dashboard directory.
2. Copy this folder to C:\xampp\htdocs\cyberweb-dashboard\.
3. Start Apache and MySQL in XAMPP.
4. Check api/db.php connects to your existing cyberweb database (root / empty password are XAMPP defaults).
5. ONCE open http://localhost/cyberweb-dashboard/setup_admin.php
6. When it says Admin account ready, DELETE setup_admin.php from the folder.
7. Open http://localhost/cyberweb-dashboard/ (or /index.php) -> automatic redirect to /login.php.
8. Username: admin   Password: admin1234
9. Click Access dashboard; you will reach /index.php. Use Sign Out to end session.

The admin password is securely hashed in users.password in MySQL; not stored in plaintext there.
The account is identified in the existing users table by admin@cyberweb.local.
No public signup is allowed in this fixed-admin demo.

NOTE: admin/admin1234 is a shared, weak demo credential. Do NOT deploy it to the internet. Change it, add rate limiting, and enforce HTTPS prior to production use. Other dashboard links may point to HTML pages that were not included in your uploaded ZIP.
