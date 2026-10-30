# Cyberweb AI design integration

1. Import `database/ai_designs.sql` into the existing `cyberweb` database.
2. Visit `projects.php` and click **AI Designs** on a project card.
3. Submit complete approved property details; new MySQL job is pending.
4. A trusted, separately authenticated AI/MCP executor must retrieve pending jobs and call `ai_complete_job($pdo,$requestId,$result,$userId)` server-side. Never expose that function through an unauthenticated public HTTP route. An ordinary ChatGPT subscription is not a PHP API/MCP authentication credential.
5. After completion, refresh AI Designs to select one of four and download ZIP. Both actions are logged.

NOTE: This is a queue/integration foundation, not a working ChatGPT account connection; no real designs will be generated until the authorized external executor is supplied. New jobs do not automatically run. Original dashboard login and project CRUD are maintained. This project archive omits `.git` metadata; push through your local GitHub repository after reviewing changes.

Security: ZIPs are generated only from authenticated users' own completed designs. Generated HTML/CSS/JS requires human review and should be served from a sandboxed origin if previews are added. Images referenced in submitted facts aren't automatically bundled in ZIPs.
