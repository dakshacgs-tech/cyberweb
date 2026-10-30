CYBERWEB AI DESIGN GENERATION - WINDOWS BEGINNER SETUP
======================================================

WHAT THIS DOES
  - PHP dashboard CREATE NEW AI REQUEST stores hotel data in MySQL.
  - A separate CLI worker runs Windows Codex using YOUR ChatGPT account login.
  - Each run processes ONE pending job, generates 4 distinct site folders,
    creates 4 ZIPs, registers them in MySQL, and marks job completed.
  - Existing Select Design and Download ZIP controls continue to work.
  - This is NOT Docker auto-execution, MCP protocol integration, or a PHP API key connection.

STEP 1. Back up your cyberweb-dashboard folder and cyberweb database.
STEP 2. Copy updated project files to C:\xampp\htdocs\cyberweb-dashboard
STEP 3. Open phpMyAdmin, choose cyberweb, click SQL, paste content of
        database/ai_worker_upgrade.sql and click Go. Do NOT reimport the full DB.
STEP 4. Confirm Windows Codex CLI is signed in:
        codex.cmd login status
        Expected: Logged in using ChatGPT
STEP 5. In browser, open My Projects -> AI Designs -> Create new AI request.
STEP 6. Open normal (non-admin) Windows PowerShell and execute:
        cd C:\xampp\htdocs\cyberweb-dashboard
        C:\xampp\php\php.exe .\ai\worker.php
STEP 7. Worker handles one job and prints success/failure. If successful,
        refresh AI Designs to see four ZIP designs, Select Design and Download ZIP.
STEP 8. Run Step 6 again to handle another pending job.

If worker says 'ALTER status' errors, run the SQL upgrade first.
If worker says 'codex.cmd' unavailable: run 'where.exe codex.cmd' in PowerShell.
If Codex cannot write: run Codex standalone in workspace with workspace-write
  first; never bypass security or run XAMPP as administrator.
If Codex produces incomplete designs: job will be marked failed; inspect
  docker-ai/workspace/ai-jobs/<request-id>/codex-output.log and codex-error.log.
  Create a new AI request to retry; failed requests remain for audit.

IMPORTANT LIMITATIONS
  - Current project schema contains gallery images but no saved logo asset.
    The AI worker will NOT fabricate logo colors; add a trusted logo field later.
  - Gallery image files must exist in storage/gallery/<project_id>/ for reuse.
  - User-supplied hotel data is sent to your authenticated Codex model service.
    Check data permissions before generating websites for clients.
  - The current workflow is manual CLI orchestration (one job per run), not
    automatic browser-triggered service or continuous Docker background worker.
  - Generated sites may require review for accuracy, accessibility, assets.
  - Windows Codex CLI must be available in the same account that runs PHP CLI.
  - Keep ai-jobs outputs and Codex auth files out of GitHub and public hosting.
