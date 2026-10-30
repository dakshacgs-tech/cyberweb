CYBERWEB 404 CONNECTION.PHP FIX
================================
Cause: The uploaded project contains api/db.php but no root connection.php.

This update adds connection.php as a safe connection-status page (no passwords displayed).
Existing api/db.php, login, dashboard, SQL tables and frontend assets remain unchanged.

Installation:
1. Backup your current C:\xampp\htdocs\cyberweb-dashboard directory.
2. Copy the contents of the ZIP cyberweb-dashboard folder into C:\xampp\htdocs\cyberweb-dashboard.
   IMPORTANT: Do not nest an extra cyberweb-dashboard folder.
3. Open http://localhost/cyberweb-dashboard/connection.php
   It should say 'Database connected successfully'.
4. Open http://localhost/cyberweb-dashboard/login.php to sign in.
5. Open http://localhost/cyberweb-dashboard/projects.php and click AI Designs.
6. Your phpMyAdmin screenshot confirms ai_designs.sql was already imported: do not re-import it.

Note: connection.php is a diagnostics/compatibility page, not a replacement for api/db.php.
If the 404 persists, check the Windows folder path and Apache document root.
