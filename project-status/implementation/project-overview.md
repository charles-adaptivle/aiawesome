# local\_aiawesome – React Slide‑Out AI Chat for Moodle (Spec + Agent Prompt)

**Target Moodle**: 4.5+ (Boost‑family themes; theme‑agnostic DOM injection)

**Plugin type**: `local`

**High‑level**: Add a user‑menu icon that toggles a right‑hand slide‑out drawer containing a React chat UI. Messages stream via SSE from an upstream AI service. A secure local proxy handles OAuth2 client‑credentials, context encryption, and streaming. Admins can configure endpoints, keys, guardrails, and logging.

---

## Goals

* One‑click AI assistance anywhere in Moodle without leaving context.
* Secure: secrets server‑side only; per‑user, per‑course context encrypted.
* Performant: SSE streaming with low latency; minimal theme coupling.
* Governed: capability‑based access; configurable logging and guardrails.

## Non‑Goals (v1)

* Multi‑provider switching inside the UI (can be added later).
* Fine‑grained per‑course prompt templates (Phase 2+).
* Cross‑tenant billing/cost controls (Phase 2+).

---

## Architecture

**Client (React + AMD/ESM)**

* User‑menu icon → toggles `<aside id="aiawesome-drawer">` (fixed, right, translateX).
* React app mounts on first open; maintains in‑drawer chat state.
* Connects to local proxy endpoint (`/local/aiawesome/stream.php` or WS function) via SSE.

**Server (Moodle local plugin)**

* Settings UI (`settings.php`) with endpoints, OAuth2 client creds, appId, toggles.
* Capabilities to gate: view toggle, use chat, view logs.
* OAuth2 (client‑credentials) → Bearer JWT (cached).
* Context build (user/course) → encrypt with key derived from token claim (`sub`).
* Open upstream SSE, relay chunks to browser; optional logging to DB.

---

## Phase 1 – MVP Scope

1. **UI/UX**

   * Inject icon in user menu (theme‑agnostic JS boot).
   * Slide‑out drawer (CSS transforms) with:

     * Transcript area (streaming text append)
     * Prompt input + send
     * Stop generation button
     * Minimal status/errors
   * **Accessibility**: focus trap, ESC to close, `aria-expanded` on toggle, `aria-live="polite"` for streaming, respects `prefers-reduced-motion`, RTL support.
2. **Streaming**

   * Local SSE proxy endpoint that relays upstream `text_chunk`, `final_response`, `error` events.
   * Transport default: **EventSource**; fallback to **fetch + ReadableStream** behind a setting.
3. **Security**

   * OAuth2 client‑credentials (Cognito or similar); cache token server‑side via **MUC**.
   * Build minimal context `{courseId, enrolledCourseIds, userId}`; encrypt w/ AES‑GCM using key derived from JWT `sub` (HKDF‑SHA256 with plugin salt).
   * Secrets stored only in `config_plugins` (never sent to client).
   * **CSP‑friendly**: no inline scripts/styles; bundled assets only.
4. **Config**

   * Endpoint/base URL, token URL, client id/secret, appId.
   * Toggles: enable feature, enable logging, show references panel, default‑open drawer.
   * **Transport toggle** (EventSource vs fetch stream), **max concurrent generations per user**, and simple **rate limiting**.
5. **Access control**

   * `local/aiawesome:view`, `local/aiawesome:use`, `local/aiawesome:viewlogs`.
6. **Logging (minimal)**

   * Record: userid, courseid, timestamps, status, byte counts, error code, TTFF, duration. (No content by default.)
7. **Privacy provider**

   * GDPR export/delete; explain metadata stored.
8. **Build**

   * Vite → `amd/build/aiawesome.js` (AMD) + CSS; Moodle shimming.
   * **No jQuery policy** enforced by ESLint rule to fail on any jQuery import/reference.
9. **Caching**

   * Define `db/caches.php` for named MUC caches: `token_cache` (application), `config_cache` (request‑scoped).

### Phase 1 Acceptance Criteria

* Icon appears for users with `local/aiawesome:view`.
* Drawer opens; prompts stream token‑by‑token; stop works.
* **No jQuery in client bundle** (build fails if detected).
* Drawer passes a11y checks: focus is trapped on open; ESC closes; `aria-live` communicates streamed text; respects `prefers-reduced-motion`.
* Lazy‑loaded app; initial boot script < 8KB gz; chunk coalescing avoids layout thrash.
* No client secrets exposed (network trace checked).
* Token cached in MUC; upstream calls succeed; basic errors rendered with retries.
* Settings saved and applied; capabilities respected; logs written if enabled.

---

## Phase 2 – Enhancements

1. **Admin Dashboard**

   * Usage charts (requests, tokens, errors) by day/role/course with TTFF/duration metrics.
2. **References Panel**

   * Render upstream references (course/module/resource links) beneath assistant replies.
3. **Chat History (optional)**

   * Persist last N messages per user/session; server‑side storage & privacy controls.
4. **Guardrails & Presets**

   * Max tokens, style presets, restricted topics; per‑role override.
5. **Observability**

   * SSE timing metrics (TTFF, chunk count, total ms); simple health probe.
6. **A/B Settings**

   * Flag to test UI variants or prompts across cohorts.
7. **Transport Options**

   * Admin setting to switch between EventSource and fetch stream; detect proxy incompatibilities.
8. **Rate Limiting & Quotas**

   * Per‑user sliding window; per‑role quotas; deny with friendly error.

### Phase 2 Acceptance Criteria

* Admin report loads under `Site admin → Plugins → local_aiawesome → Reports` showing usage, TTFF, and error rates.
* References resolve to Moodle URLs (course, cmid, file where applicable).
* History toggle works when enabled; export/delete respects privacy API.
* Rate limiting and per‑user concurrent cap enforced.
* Transport can be switched and verified on `/local/aiawesome/health.php`.

---

## Plugin Skeleton

```
local/aiawesome/
  version.php
  settings.php
  db/
    access.php
    install.xml
  classes/
    external/stream.php
    output/boot.php (optional helper)
    privacy/provider.php
  lang/en/local_aiawesome.php
  amd/src/
    boot.js (inject toggle + mount)
    app.jsx (React root)
    sse.js (EventSource wrapper)
  amd/build/* (generated)
  templates/
    drawer.mustache (fallback markup)
  vendor/ (Symfony HttpClient or similar for SSE)
  index.php (health/debug)
  stream.php (if not using WS)
```

### Capabilities (`db/access.php`)

* `local/aiawesome:view` – see menu icon & open drawer (default allow for authenticated users)
* `local/aiawesome:use` – call proxy (default allow for authenticated users)
* `local/aiawesome:viewlogs` – admin/manager only

### Database (`db/install.xml`)

Table `local_aiawesome_logs` (v1 minimal):

* `id` (PK), `userid`, `courseid`, `sessionid` (varchar), `bytes_up`, `bytes_down`, `status` (varchar), `error` (text), `createdat` (int), `duration_ms` (int)

### Settings (`settings.php`)

* **General**: enable (checkbox), default‑open (checkbox)
* **Provider**:

  * Base URL (text)
  * App ID (text)
  * Token URL (text)
  * Client ID (passwordunmask)
  * Client Secret (passwordunmask)
* **Logging**: enable logs (checkbox), redact content (checkbox)
* **Guardrails**: max tokens, temperature, allowed roles

---

## Server Proxy Flow (pseudocode)

```php
require_login();
require_capability('local/aiawesome:use', context_system::instance());
$token = oauth_cache_get_or_fetch();
$context = [
  'courseId' => $courseid,
  'enrolledCourseIds' => enrol_get_users_courses($USER->id),
  'userInfo' => ['userId' => $USER->id]
];
$key = hkdf('sha256', $token->sub . PLUGIN_SALT);
$enc = aes_gcm_encrypt(json_encode($context), $key);

$response = sse_stream(
  url: BASE_URL . '/v3/ai/chat/stream/completion',
  headers: [ 'Authorization' => 'Bearer ' . $token->access, 'Accept' => 'text/event-stream' ],
  body: [ 'appId' => APP_ID, 'query' => $query, 'chat_session' => $sessionid, 'metadata' => ['context' => $enc] ]
);

header('Content-Type: text/event-stream');
session_write_close();
relay_chunks($response, on_final: save_log());
```

## Client Boot & Drawer (AMD)

```js
// amd/src/boot.js
export const init = () => {
  // 1) Insert icon into user menu
  // 2) Create/append <aside id="aiawesome-drawer"> with hidden state
  // 3) On click, load app: require(['local_aiawesome/app'], (App) => App.mount())
};
```

```jsx
// amd/src/app.jsx
import { openSSE } from './sse';
export function mount() {
  // Render React app to #aiawesome-drawer
  // Handle input, send query → EventSource to local proxy
  // Append chunks; handle final/error; stop button aborts fetch
}
```

---

## Error Handling (client)

* Network 0/timeout → retry with backoff (max 2).
* 401 → show message; server auto‑refreshes token on next call.
* 429/5xx → friendly error + suggestion.

## Security Notes

* Never expose client id/secret to browser.
* Use Moodle MUC for token cache (store expiry); per‑request validate.
* CSRF: use Moodle sesskey for POST that opens stream.
* CORS: same origin (local proxy); no direct calls to upstream from browser.

## Theming

* Drawer uses minimal, theme‑neutral CSS vars; prefers system fonts.
* Icon injected adjacent to existing user‑menu items; degrades gracefully if DOM differs.

## Observability (Phase 2)

* Measure TTFF (time to first token), total duration, chunk count.
* Simple `/local/aiawesome/health.php` to test config.

---

## No‑jQuery Policy (4.5+)

* Use modern Web APIs only: `fetch`, `AbortController`, `EventTarget`, `ResizeObserver`, `IntersectionObserver`, `Intl`.
* Moodle modules: `core/str` (promise), `core/notification` (errors), `core/ajax` only if necessary.
* ESLint rule blocks `jquery` import or `$`/`jQuery` global usage.

## Packaging for Moodle 4.5+

* Author code as ESM React (Vite). Output AMD modules named `local_aiawesome/boot` and `local_aiawesome/app`.
* Code‑split: tiny boot loader; lazy‑load app on first open.
* No globals; unique AMD names; CSS scoped to `#aiawesome-drawer`.

## Accessibility (WCAG 2.1 AA)

* Focus moves into drawer on open; returns to toggle on close.
* Trap focus within drawer; support **Esc** to close.
* `aria-expanded` on toggle; `role="complementary"` or `dialog` for drawer; `aria-live="polite"` on streamed text container.
* Respect `prefers-reduced-motion`; CSS logical properties for RTL.

## Security & CSP

* Same‑origin proxy only; never expose OAuth client secrets.
* CSRF: include `sesskey` on stream‑init POST.
* CSP: no inline scripts/styles; all assets bundled; use Moodle-supplied nonce if needed.
* Capabilities checked on both client (gate) and server.

## Caching (MUC)

* Define caches in `db/caches.php`:

  * `local_aiawesome/token` (application): OAuth token and expiry.
  * `local_aiawesome/config` (request): derived config bits.

## Testing & QA

* **PHPUnit**: token cache, crypto helpers, proxy edge cases.
* **Behat**: icon visibility per role; drawer a11y flows.
* **Vitest**: SSE stream parser, retry/backoff logic, focus‑trap utility.
* **Privacy**: export/delete unit tests.

## Fallbacks & Health

* If SSE blocked/unsupported, show friendly fallback message with help link.
* `/local/aiawesome/health.php`: verifies settings, outbound connectivity, and chosen transport.

---

## API Contracts

**Local proxy request (POST)**

```json
{
  "query": "string",
  "courseid": 123,
  "session": "uuid"
}
```

**Local proxy SSE events**

```text
event: text_chunk
data: {"text":"..."}

event: final_response
data: {"text":"...","references":[{"type":"course","id":42}]}

event: error
data: {"code":"OUT_OF_SCOPE","message":"..."}
```

---

## Deliverables (Phase 1)

* Working plugin folder with installable version.php.
* Vite build pipeline and npm scripts.
* Minimal UI and SSE streaming.
* Settings & capability checks.
* Basic logging table + privacy provider.

## Deliverables (Phase 2)

* Admin dashboard (report page) with charts.
* References rendering.
* Optional history persistence.
* Guardrails presets.

---

# Base Prompt for AI Agent (Build & Iterate)

**System/Agent Role**: You are a senior Moodle plugin engineer and frontend dev. You produce production‑ready Moodle code for 4.5+, with React frontends compiled to AMD. Follow Moodle coding guidelines, privacy/API conventions, and keep secrets server‑side.

**Project**: Build `local_aiawesome` – a local plugin adding a React slide‑out AI chat drawer, with a secure SSE proxy and admin settings.

**Stack & Conventions**

* Moodle 4.5+, PHP 8.2/8.3.
* Frontend: React 18 + Vite → AMD build in `amd/build`.
* JS style: ESLint (Moodle rules), no external UI frameworks; minimal CSS.
* Server: OAuth2 client‑credentials, Symfony HttpClient (vendor) for SSE; caching via MUC.
* Privacy: implement `
  \local_aiawesome\privacy\provider`.

**Tasks**

1. **Scaffold plugin** structure and files listed above.
2. **Implement settings.php** fields and language strings.
3. **Capabilities** in `db/access.php` (view/use/viewlogs).
4. **DB schema** `local_aiawesome_logs` and install.xml.
5. **OAuth service**: helper to fetch/cache token.
6. **Context crypto**: HKDF‑SHA256 + AES‑GCM with plugin salt; utilities for encrypt/decrypt.
7. **SSE proxy**: `classes/external/stream.php` or `stream.php`; relay events and flush.
8. **Boot & UI**: `amd/src/boot.js` to inject icon + mount drawer; `app.jsx` for chat.
9. **SSE client**: `amd/src/sse.js` with abort support; backoff on network errors.
10. **Logging**: write minimal metadata; guard with setting.
11. **Privacy provider**: declare/export/delete stored data.
12. **Report page (Phase 2)**: simple charts (tokens, errors) with Moodle templates.

**Acceptance Tests**

* Install plugin; configure settings; icon appears for privileged users.
* Send a prompt; see streamed tokens; stop works; errors handled.
* Secrets never appear in client code/network.
* Logs written when enabled; GDPR export works.

**Deliver Outputs**

* All plugin files with correct namespaces and headers.
* `package.json` with Vite build to AMD, `npm run build`.
* Brief README.md with setup steps.

**Important Notes**

* Keep DOM injection resilient; do not assume exact theme markup.
* Use `session_write_close()` before long‑running SSE.
* Cache token until expiry; refresh on 401.
* No PII in logs by default; make content logging opt‑in.

**Next Iteration Hooks**

* Add references panel; admin report; chat history.

---

## Quick Setup Steps (for humans)

1. Install plugin into `/local/aiawesome` and visit admin to upgrade DB.
2. Set endpoints and OAuth client creds in settings.
3. `npm i && npm run build` to generate `amd/build` assets.
4. Give role(s) the `local/aiawesome:view` and `use` capabilities.
5. Reload any page → click the new chat icon → ask a question.
