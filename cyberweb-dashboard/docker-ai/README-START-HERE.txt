CYBERWEB + DOCKER CODEX: BEGINNER SETUP (WINDOWS / XAMPP)
======================================================
WHAT IS INCLUDED:
This is a Docker/Codex starter. It does NOT automatically connect AI requests to PHP/MySQL.
No API key is configured. You need to sign in to Codex using your ChatGPT account.
Docker login uses supported Codex authentication; it is not a PHP ChatGPT API.

1) Install Docker Desktop and start it.
2) In File Explorer, open C:\xampp\htdocs\cyberweb-dashboard\docker-ai
   Check that Dockerfile and compose.yaml are directly inside that folder.
3) Open PowerShell. Copy these commands ONE AT A TIME:
   cd C:\xampp\htdocs\cyberweb-dashboard\docker-ai
   docker compose up -d --build
   docker compose ps
4) If the container says Up, authenticate:
   docker compose exec codex-worker codex login --device-auth
   Follow official instructions on screen (device auth may require account permission).
   docker compose exec codex-worker codex login status
5) To test: COPY workspace\hotel-input.example.json to workspace\hotel-input.json,
   edit hotel-input.json using only your actual approved hotel info, then run:
   docker compose exec codex-worker codex exec --skip-git-repo-check --sandbox workspace-write "Read hotel-input.json and design-instructions.txt. Build all four designs as instructed."
6) Look in docker-ai\workspace\design-1 through design-4 for results.

CAUTION:
- This starter does not yet watch ai_generation_jobs or update ai_generated_designs.
- Do not upload auth.json, private database files, or the codex_auth Docker volume to GitHub.
- Existing XAMPP PHP/MySQL/project data is not mounted into the container.
- Stop worker: docker compose down (does not delete the auth volume).
- If Dockerfile is seen as Dockerfile.txt, enable file extensions and rename.
