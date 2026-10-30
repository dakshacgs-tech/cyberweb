CYBERWEB DASHBOARD - COMPLETE FOLDER (PHP 8.0 / XAMPP)

1. ZIP extract karo. Extracted cyberweb-dashboard folder ko C:\xampp\htdocs\ mein rakho.
   Final location: C:\xampp\htdocs\cyberweb-dashboard\index.php
   Note: nested cyberweb-dashboard\cyberweb-dashboard folder mat banana.
2. Existing folder ka backup pehle bana lo. Agar purana index.html hai to rename/delete karo.
3. XAMPP mein Apache aur MySQL start karo.
4. phpMyAdmin mein cyberweb database aur users table pehle se hone chahiye.
5. My Projects ke liye cyberweb database mein database/projects.sql import karo (sirf ek baar).
6. api/db.php ka database name cyberweb hai, default XAMPP user root aur blank password.
7. Browser Incognito window mein http://localhost/cyberweb-dashboard/ open karo.
   Logged OUT: Login Page. Login success: Dashboard. Logout: Login Page.
   Agar login session active hai to dashboard directly khulna expected hai.
8. My Projects: http://localhost/cyberweb-dashboard/projects.php
9. Existing admin password database se hi check hota hai; ZIP mein password set/reset nahi hai.

TROUBLESHOOTING:
- 404: Folder nesting aur final path check karo.
- Login redirect loop: browser cookies remove karo; auth/bootstrap.php session settings check karo.
- Projects table not ready: database/projects.sql import karo.
- Blank page: XAMPP Apache/PHP error.log check karo.
- Dashboard ka theme purane css/style.css se preserve hai.
- Note: Other menu items (Templates, etc.) are placeholders until implemented.
