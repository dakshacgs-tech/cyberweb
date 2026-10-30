CYBERWEB DASHBOARD - LOGIN PAGE ALWAYS FIRST

1. XAMPP Apache + MySQL start karein.
2. Existing C:\xampp\htdocs\cyberweb-dashboard ka BACKUP banaayein.
3. Purana cyberweb-dashboard folder rename karke cyberweb-dashboard-backup karein.
4. Is ZIP ka cyberweb-dashboard folder extract karke C:\xampp\htdocs\ mein rakhein.
5. Ensure path C:\xampp\htdocs\cyberweb-dashboard\login.php aur index.php dono maujood hon.
6. http://localhost/cyberweb-dashboard/ open karein — Login Page open hoga.
7. Credentials validate honge existing cyberweb DB mein, phir dashboard index.php open hoga.
8. Logout button se session end hogi aur Login Page khulega.

IMPORTANT:
- Browser tabs same PHP session share karte hain. Root URL hamesha Login Page SHOW karega, par already authenticated user direct /index.php visit kare to session valid hone tak dashboard dikhega.
- Naye login ki guarantee har tab ke liye chahiye, to per-tab authentication alag implement karna padta hai.
- Database cyberweb aur users table unchanged. Project management ke liye database/projects.sql import karein, agar projects table nahi bana.
- Signup/forgot-password config aur real backend environment separately check karein.
- Folder ko nested cyberweb-dashboard/cyberweb-dashboard na banaayein.
