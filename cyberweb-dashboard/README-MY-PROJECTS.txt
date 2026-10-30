CYBERWEB — MY PROJECTS INSTALL (XAMPP + MySQL)

1. BACKUP your current C:\xampp\htdocs\cyberweb-dashboard folder.
2. Unzip this package and copy the contents of its cyberweb-dashboard folder to
   C:\xampp\htdocs\cyberweb-dashboard (merge/replace matching files).
   Your existing login credentials and cyberweb.users table are not modified.
3. XAMPP > Start Apache and MySQL.
4. Open http://localhost/phpmyadmin > select cyberweb > SQL tab.
5. Copy and run database/projects.sql ONE TIME. This creates a new projects table only.
6. Open http://localhost/cyberweb-dashboard/ and log in.
7. Click 'My Projects' in sidebar, or open
   http://localhost/cyberweb-dashboard/projects.php
8. Click 'New project'. Data is inserted into cyberweb.projects.
   Edit/delete also update that table. Search and status filters are live.

NOTICE: Editor/ZIP export/template selection are not yet connected to a website builder.
The main dashboard's original Recent Projects rows are sample data, not live records.
Future step: wire index.php counters and Recent Projects to the new projects table.
Do NOT run setup_admin.php or reset_admin.php; delete them if found.
Security: authenticated users can only access their own projects.
