CYBERWEB: PROJECT CREATE -> HOTEL DETAILS -> TEMPLATE SELECT -> FINAL PREVIEW

IMPORTANT: New 3-step workflow ke liye README-NEW-HOTEL-WORKFLOW.txt padhein.

WHAT CHANGED
1. My Projects > New project asks for name, property name and location.
2. Saving creates a draft in MySQL WITHOUT assigning a template yet.
3. The app immediately opens Templates and identifies the saved project.
4. Click Preview to see a concept. Click Use Template and confirm to attach it to the existing project.
5. The user is returned to My Projects. No duplicate project is created.
6. On an existing project, use Choose template / Change template to pick a different one.
7. Editing a project's basic details and status remains available separately.

TEMPLATE PAGE USER EXPERIENCE UPDATE
- After project creation, the dedicated page clearly shows Step 1 completed and Step 2 current.
- The selected project name appears above the four template cards.
- The large promotional banner is hidden during project setup, so design choices appear sooner.
- The CSS URL has a new version value to avoid the unstyled step panel caused by browser caching.
- The regular template browsing page still displays its original banner.

INSTALL / TEST WITH XAMPP
1. Back up your current C:\xampp\htdocs\cyberweb-dashboard directory.
2. Extract this ZIP and replace/merge matching files in that project directory.
3. Start Apache and MySQL from XAMPP.
4. Your MySQL database must be 'cyberweb', with an existing users table.
5. Import database/projects.sql if the projects table does not exist yet.
6. Sign in, visit My Projects, click New project and save basic details.
7. Confirm the Templates page opens for that project and that choosing Harbor/Willow/Urban/Minimal saves to the SAME row in the projects table.
8. If you still see outdated styling, press Ctrl+Shift+R once after copying both templates.php and css/templates.css.

DATABASE NOTES
- No SQL migration is required if the supplied projects table already exists.
- Until a template is chosen, projects.template is an empty string (not NULL).
- Selecting a template updates projects.template for the signed-in user.
- The separate hotel editor still saves to browser localStorage, not a project-specific MySQL record.
- The template previews do NOT generate a deployable website.

TROUBLESHOOTING
- If 'Database connection failed' is displayed, check api/db.php, MySQL in XAMPP, and the cyberweb database.
- 'Projects table not ready' means database/projects.sql has not been imported successfully.
- Test with a local admin/test account before using with real customer data.
