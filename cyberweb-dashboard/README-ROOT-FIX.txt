ROOT URL FIX — cyberweb-dashboard

The previous ZIP did not contain index.html. If an old index.html remains in C:\xampp\htdocs\cyberweb-dashboard\, Apache may open it before the protected index.php.

This ZIP includes an index.html that redirects the folder URL to login.php. The authenticated dashboard remains index.php.

INSTALL: Backup C:\xampp\htdocs\cyberweb-dashboard first. Extract this ZIP, copy ALL extracted contents (including index.html, index.php and .htaccess) into that exact folder, and choose Replace files. Do not create a nested folder.

Alternatively delete/rename index.html and keep .htaccess DirectoryIndex index.php.

BEHAVIOR: http://localhost/cyberweb-dashboard/ -> login.php (if logged in, login.php will redirect to index.php). To test as logged out, click Sign Out or use Incognito.

No database changes are included.
