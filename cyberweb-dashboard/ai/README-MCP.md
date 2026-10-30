# AI template generation — backend foundation

## What works now
- `ai_start_job()` validates project ownership, saves only submitted hotel facts, stores the exact system prompt, queues a unique request ID.
- `ai_complete_job()` validates and saves **exactly 4** distinct numbered AI responses atomically; call from an authenticated trusted connector only.
- `ai_select_design()` saves one selected design per project, keeping all four generated versions.
- `ai_record_download()` adds one database event for a confirmed ZIP download.
- `ai/create_job.php` and `ai/select_design.php` are POST endpoints using the existing login and CSRF scheme.

## What is NOT connected yet
PHP cannot silently use a ChatGPT account over MCP. An authorized MCP host / supported ChatGPT sign-in integration is required for live model execution. This project does **not** call OpenAI API, scrape ChatGPT, or use account cookies. No AI generations occur until a supported connector is connected. No fabricated ZIP generation is included.

## Integration sequence
1. Import `database/ai_designs.sql` into `cyberweb`.
2. Collect final complete hotel facts (including logo asset ID + verified palette) using your actual hotel details form. Do NOT trust raw paths supplied by a browser.
3. POST to `ai/create_job.php` as the logged-in user, with `csrf_token`, `project_id` and `hotel_information_json` (JSON object).
4. An authorized MCP host reads the pending job, sends `system_prompt` as trusted instructions and `input_json` as project data to the generative model; response must match JSON contract in `ai/system_prompt.txt`.
5. Verify caller identity, attachment provenance, layout safety, output size and resulting HTML/CSS/JS before calling `ai_complete_job($pdo, $requestId, $response, $userId)` from a trusted backend process. Never expose this function as an unauthenticated public webhook.
6. Show four previews of saved `ai_generated_designs`; POST selection to `ai/select_design.php` with CSRF/project/design IDs.
7. In your ZIP export service, generate/validate ZIP first, then call `ai_record_download()` and serve it. Downloads and selections are independent.

## Existing project constraints
Current ZIP only has `templates.php` with four fixed visual cards (Harbor, Willow, Urban, Minimal), and project fields name/property/location. It does not have Daksha's four HTML designs nor a complete hotel-information submission form. Those source designs and the full content form must be added separately before claiming end-to-end generation.

## Security
Do not expose system prompts as credentials (they are not). Authenticate the MCP bridge, validate HTML/CSS/JS before serving to end users, bind all SQL parameters, validate uploaded logo assets server-side, and protect downloads against cross-user access.
