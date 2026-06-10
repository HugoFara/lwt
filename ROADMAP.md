# LWT Roadmap

> Direction for the next iteration. Strategic, not a release checklist — see
> `CHANGELOG.md` for shipped work.

## North star

Make LWT reachable on a phone as a real app, **starting with the FOSS audience
(F-Droid)** rather than the mass-market Play Store. The long-term prize is an
app that does more in the browser and less on the server, so it can eventually
run offline and sync — but each phase below is independently useful and we only
pay for the hard parts when demand proves them worth it.

## Strategic decisions (locked)

- **F-Droid first, not Play Store first.** The FOSS audience tolerates the rough
  edges (entering a server URL, self-hosting) that mass-market users won't, so it
  needs less of the expensive zero-config work.
- **TWA is shelved.** A Trusted Web Activity needs Chrome at runtime (fails on
  de-Googled phones) and reads to F-Droid as a `NonFreeNetwork` wrapper around a
  server we control. It was a shortcut for a *different* audience.
- **The mobile artifact is a configurable client, not a hardwired wrapper.** A
  thin shell over the system WebView, pointed at a **user-chosen** server URL.
  This fits F-Droid's accepted "client for a self-hostable free service" pattern
  (Nextcloud-client style), and — unlike a TWA — it's reusable for Play Store
  later with a default instance baked in.
- **License is Unlicense (public domain)** → zero F-Droid licensing friction.

## Branding

- **`lwt` stays the umbrella** for the project and the server. The original name
  is kept for everything technical.
- **The mobile app carries its own, more evocative brand.** Working codename:
  **`language-reader`** (placeholder — a better name TBD). The consumer-facing
  client is where rebranding starts; the server stays LWT.
- **Hard deadline on the real name:** the Android `applicationId` / package name
  (e.g. `org.<brand>.app`) is **permanent once published** to F-Droid/Play —
  changing it later means a new, separate app (lost installs, ratings, history).
  So the final name must be locked **before Phase 2's first release**, not after.
  The codename is fine until then.
- Smooth the seam in client copy: a "Language Reader" app connects to "LWT
  servers" — the server-URL config screen should make that relationship clear.

## Repository layout

- **App de-coupling (Phases 0, 1, 3, 4) lives in this repo (`lwt/`).** It is the
  app evolving — not separable.
- **The mobile client (Phase 2) gets its own repo** (e.g. `lwt-mobile`),
  created *when Phase 2 starts*, not before. Rationale: F-Droid builds
  reproducibly from a focused repo+tag (a thin Android project, not an Android
  subfolder buried in a 138k-LOC PHP monorepo); separate toolchain and release
  cadence; matches the workspace's one-repo-per-concern convention and the
  standard server/client split.
- **This roadmap stays in `lwt/`** — the published, version-controlled repo. The
  parent workspace folder is not a git repo, so a roadmap there would be an
  untracked, unshareable file. When `lwt-mobile` exists it carries its own
  build-focused roadmap; this file keeps the ecosystem strategy.

## The keystone constraint

LWT renders HTML server-side (94 view templates). Until more logic moves to the
frontend against `/api/v1`, *any* mobile wrapper is just a window to a server.
So **"more frontend, less server-rendered" is the enabling work** — the thing
that makes a good client today and offline/local-first possible tomorrow.

---

## Phase 0 — Foundations (now)

- [ ] Confirm and document the strategy above.
- [x] **API base URL decoupling**: the frontend no longer assumes same-origin.
      `@shared/api/client` resolves an injectable server root (`setApiServer`,
      precedence runtime > localStorage `lwt.apiServer` > meta > same-origin),
      so the mobile client / self-host / future default instance can all point
      it at a chosen server. Unset = unchanged same-origin behavior.
- [x] **Cross-origin auth plumbing** (the seam's server + client halves):
      - [x] Opt-in **CORS** on `/api/v1` via `CORS_ALLOWED_ORIGINS`
        (`Cors` helper + preflight handling in `ApiV1::handleRequest`).
      - [x] Client sends **`Authorization: Bearer`** (`setAuthToken`/`getAuthToken`,
        persisted as `lwt.apiToken`); server already validated Bearer tokens.
      - [x] **First-run auth UX** (`GET /connect`, `clientAuth` component):
        choose-server (probes `/version`) → log in **or register** against the
        auth API, storing the bearer token. Plus session longevity — proactive
        token refresh before the 30-day expiry, and `lwt:auth-expired` →
        `/connect` on a 401. Remaining for Phase 2: the Capacitor shell that
        hosts these screens and supplies a default server.
- [ ] **Security hardening pass** — continue the XSS phases. Gate for any
      shared/public exposure; non-negotiable before a public instance.

## Phase 1 — Frontend de-coupling (the enabling work)

**Reframe (post-audit):** the mobile-critical pages are *already* client-rendered.
`read_desktop.php` renders the word grid + state 100% client-side via
`textReader.renderTextContent()` against `/api/v1`, with an offline prototype
(`offline-text-reader.ts` + IndexedDB) started. `review_desktop.php` is "all UI
rendered by Alpine.js." So Phase 1 is **cutting the server-shell umbilical, not
converting pages**. Three dependencies to sever + one real conversion:

- [ ] **(Phase 0 gate) Injectable API base URL.** `@shared/api/client` + `url.ts`
      build same-origin relative URLs from a meta tag. Make the base an
      injectable **absolute** URL so a client can target a user-chosen server.
      One centralized seam; everything client-rendered already routes through it.
- [ ] **Reading — prove the shell-free path here first.** Verify it runs
      end-to-end from `/api/v1` alone; start cutting the i18n/shell deps on this
      surface (it has the offline prototype → best proving ground).
- [ ] **i18n → client.** Templates bake translations via PHP `__e()`. Ship
      per-language **JSON string bundles** to the client — the biggest hidden
      dependency, forced the moment reading runs shell-free. Reusable everywhere.
- [ ] **Review — finish** (mostly verification; `review/next-word`, `review/status`,
      `review/config` already exist).
- [ ] **Text list / library** — verify/de-shell (`texts_grouped_app.ts` already
      client-renders; likely near-done).
- [ ] **Vocabulary mgmt — the real conversion.** Replace the legacy
      AJAX-returns-HTML `*_result.php` endpoints (set_status_result,
      hover_save_result, save_result…) with JSON + client render. Biggest effort,
      lowest mobile urgency → last.

**Out of Phase 1** (leave server-rendered, fine in a WebView online): imports
(file/web/youtube/whisper), admin/settings, language config, feeds.

**Definition of done per surface:** renders entirely from `/api/v1` JSON, no
server-rendered partial carrying data, works against a configurable API base URL.

Keep the service worker (`sw.ts`) + manifest as the offline cache layer; expand
coverage as each surface goes shell-free. Track progress as "% of mobile-critical
flows that run without a server-rendered page."

## Phase 2 — Capacitor client + own F-Droid repo

- [ ] Thin **Capacitor** (or Tauri-mobile) shell using the **system WebView**
      (no Chrome dependency).
- [ ] **Server-URL config screen** as first-run + settings.
- [ ] Reuse existing PWA assets; close the small gaps: add a **maskable icon**
      (512px, safe-zone padding) and a manifest **`id`**.
- [ ] Ship through **our own F-Droid repo first** (low bar, full control,
      derisks the toolchain) before applying to the main catalog.
- [ ] Expect a "needs a server" note — acceptable for a self-hostable-service
      client.

## Phase 3 — Toward "5 minutes to reading" (mass-market prerequisites)

> Only once the client exists and demand is real. This is where the hosting
> commitment and zero-config UX get paid for.

- [ ] **Guest / deferred-signup** first-run ("read now, sign up later to sync")
      using existing `Register` + `ClaimOrphanRows`.
- [ ] Stand up a **hardened public instance** — eyes open on the real cost:
      money is cheap (~$15/mo on the existing VPS), but backups, uptime,
      security patching, GDPR, and support are the actual burden.
- [ ] Optional: Play Store build of the same client with the public instance as
      default. Apply to the main F-Droid catalog.

## Phase 4 — Local-first + sync (conditional, far out)

> The true offline F-Droid app, and the only thing that sheds per-user hosting.
> Do **not** start until offline demand or hosting cost justifies it.

- [ ] **Spike conflict resolution FIRST** (the Anki problem) — it looks easy and
      isn't. Decide last-writer-wins+tombstones vs CRDT before committing.
- [ ] Move reading + review data to client storage (SQLite-WASM / OPFS).
- [ ] Port the corresponding logic from PHP to TypeScript.
- [ ] Caveat: server-dependent parsers (MeCab/CJK, NLP lemmatizer, Whisper) stay
      server-side or need bundled WASM equivalents — plan per-language.

---

## Watch-outs (carry forward)

- **Security before exposure** — public instance multiplies the blast radius of
  any XSS.
- **Sync is the underestimated monster** — quarantined to Phase 4 on purpose.
- **Two audiences pull opposite ways** — FOSS/self-host vs zero-config
  mass-market. The configurable-server client is what lets one codebase serve
  both; protect that seam.
