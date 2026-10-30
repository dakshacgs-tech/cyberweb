HOTELFORGE PREMIUM DASHBOARD - SETUP

1. The supplied zip originally had only index.html, css/style.css, js/dashboard.js.
2. This redesign keeps the supplied navigation and order: Create Website -> Workspace Overview -> Recent Projects.
3. It adds a separate users.php page to list REAL rows from the cyberweb.users database.
4. For the dashboard design, copy index.html and css/style.css and js/dashboard.js to the corresponding locations in your existing hotel dashboard. BACK UP your old files first.
5. To use users.php, copy it and css/users.css into the SAME existing project that contains api/db.php and auth/require_login.php. Without those dependencies, it intentionally refuses access.
6. In api/db.php ensure MySQL database is cyberweb and a PDO instance named $pdo is created.
7. Run http://localhost/<your-project-folder>/users.php after signing in.
8. The dashboard stat cards (12, 04, 08, 06) and project names are sample design data from the uploaded HTML, NOT data fetched from the database. The users.php directory is real data.
9. Original source links for projects, templates, media-manager, revisions, exports, settings, editor, and create-project are maintained but those pages were not present in the supplied ZIP. Supply those pages or adjust navigation when integrating.
10. SECURITY: users.php requires login but does not enforce admin role; add server-side admin authorization before allowing other users to access it. Never show passwords or password hashes.

WHITE PANEL UPDATE: Left sidebar stays navy; right workspace, content backgrounds, toolbar and users table now use clean white. Removed dark theme toggle.
