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
- **The mobile app carries its own brand: Lukaisu.** Locked 2026-06
  (`org.lukaisu.app`, `lukaisu.org` registered), replacing the old
  `language-reader` codename. The consumer-facing client is where rebranding
  starts; the server stays LWT. App repo: `../lukaisu/`.
- **Name deadline — met.** The Android `applicationId` / package name is
  **permanent once published** to F-Droid/Play, so it had to be locked before
  Phase 2's first release. Done: `org.lukaisu.app` is committed in the app's
  Capacitor config and is final.
- Smooth the seam in client copy: the **Lukaisu** app connects to "LWT
  servers" — the server-URL config screen should make that relationship clear.

## Repository layout

- **App de-coupling (Phases 0, 1, 3, 4) lives in this repo (`lwt/`).** It is the
  app evolving — not separable.
- **The mobile client (Phase 2) has its own repo: `Lukaisu` (`../lukaisu/`).**
  Rationale: F-Droid builds reproducibly from a focused repo+tag (a thin Android
  project, not an Android subfolder buried in a 138k-LOC PHP monorepo); separate
  toolchain and release cadence; matches the workspace's one-repo-per-concern
  convention and the standard server/client split.
- **This roadmap stays in `lwt/`** — the published, version-controlled repo. The
  parent workspace folder is not a git repo, so a roadmap there would be an
  untracked, unshareable file. `lukaisu/ROADMAP.md` carries the build-focused
  detail; this file keeps the ecosystem strategy.

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

- [x] **(Phase 0 gate) Injectable API base URL.** Done in Phase 0 — same seam.
      `@shared/api/client` resolves an injectable **absolute** server root and
      everything client-rendered already routes through it. (Listed here too
      because it's the gate the rest of Phase 1 depends on.)
- [x] **Reading — content + chrome now shell-free.** The word grid + state
      render 100% client-side from `/api/v1/texts/{id}/words`
      (`TextTermApiHandler` → `word_store.ts` → `text_reader.ts`); offline
      prototype at `shared/offline/offline-text-reader.ts`. The reader's chrome
      is now client-rendered too: **book-context nav** from
      `GET /texts/{id}/book-context` (`book_nav_renderer.ts`) and the **audio
      player** from `GET /texts/{id}/audio` (markup-only `audio_player.php` +
      `audioPlayer` fetch), so `read_desktop.php` carries no per-text data and
      `TextReadController` dropped the server-side book/media plumbing. Verified
      live (book nav, audio reveal, no-regression plain read). Toolbar labels use
      `__e()` but resolve via the client i18n `t()`. *Remaining (lower urgency,
      not reader-specific):* the **global navbar** is still PHP-rendered — tracked
      separately since it spans every page.
- [x] **i18n → client (delivery mechanism).** Shipped: `GET /api/v1/i18n[/{locale}]`
      (public; `Translator::getAllTranslations()`) returns the flat
      "namespace.key" => string bundle, merging English fallback — the same shape
      the page blob uses. `shared/i18n/translator.ts` gained `loadI18nFromApi()`
      (fetch + localStorage cache) and `hydrateI18nFromCache()` (sync first-paint),
      so a shell-free client gets strings without a server-rendered page. The
      server-injected blob stays as the default for SSR pages (additive, no
      breakage). **Client boot wired:** `bootI18n()` (called from `main.ts`)
      uses the blob when present, else hydrates from the localStorage cache and
      refreshes from the API, persisting the resolved locale (`lwt.locale`) for
      offline first-paint — so a bundled client now picks up strings on its own.
      Templates still calling `__e()` resolve client-side via `t()`.
- [x] **Review — verified shell-free.** The review SPA (`review_desktop.php` →
      `review_api.ts`) renders entirely from `/api/v1/review/*`
      (next-word/status/config/table-words/tomorrow-count all exist). Removed the
      orphaned `status_change_result.php` (HTML fragment, no route/no caller;
      superseded by the JSON `status_change_config.php`). The legacy non-SPA
      review-AJAX page is a separate, non-mobile entry — out of scope.
- [x] **Text list / library — shell-free.** `texts_grouped_app.ts`
      client-renders the list from `/texts/by-language/{id}` + `/texts/statistics`,
      and the destructive **bulk archive/delete** now go through
      `PUT /api/v1/texts/bulk-action` (per-user scoped) instead of a same-origin
      form POST, so they work against a configurable API base. The remaining
      bulk actions (tag / review / reparse) intentionally stay on the form path —
      they need pickers/navigation and are desktop-admin, not mobile flows.
      `__e()` labels resolve via the i18n API once a page boots from it.
- [~] **Vocabulary mgmt — mobile path already shell-free; legacy fragments
      remain.** *Re-audit (corrected):* the **modern reader's** word actions
      already go through `/api/v1/terms/*` — `word_store.ts`/`word_modal.ts` call
      `TermsApi.setStatus/createQuick/delete`, and the unknown-word popup uses the
      API button family (`createWellKnownButton`/`createIgnoreButton` with a
      `WordActionContext`). The modern reader has no `#frames-r`, so the legacy
      `target="ro"` → `*_result.php` mechanism isn't even wired there. So the
      **mobile-critical vocab flow needs no conversion — it's done.**
      What's left is *legacy/transitional* code, not a mobile blocker: the
      `*_result.php` views + the `word_popup_interface.ts` link-builders
      (`createStatusChangeLinks`, review-status links, etc.) that some
      known/learning/review popups still emit. The clean fix is **consolidating
      those popups onto the API button family / `word_modal`** and then deleting
      the fragments — a UI-consolidation pass that needs live E2E in the reader,
      not a server-vs-client data conversion. Track as cleanup; low urgency.

**Out of Phase 1** (leave server-rendered, fine in a WebView online): imports
(file/web/youtube/whisper), admin/settings, language config, feeds.

**Definition of done per surface:** renders entirely from `/api/v1` JSON, no
server-rendered partial carrying data, works against a configurable API base URL.

Keep the service worker (`sw.ts`) + manifest as the offline cache layer; expand
coverage as each surface goes shell-free. Track progress as "% of mobile-critical
flows that run without a server-rendered page."

## Phase 2 — Capacitor client + own F-Droid repo

**The client now exists: `Lukaisu` (`../lukaisu/`, separate repo).** v0.1 is a
working shell; the data/i18n de-coupling above makes a bundled (Model B) client
the next achievable target. See `lukaisu/ROADMAP.md` for the build-side detail.

- [x] Thin **Capacitor** shell using the **system WebView** (no Chrome
      dependency) — Capacitor 8, Android platform committed.
- [x] **Server-URL config screen** — native `GET /api/v1/version` probe, choice
      persisted in native Preferences, then navigates the WebView to the server.
- [ ] Reuse existing PWA assets; close the small gaps: real adaptive/maskable
      launcher icons (replace placeholders) and a manifest **`id`** (Lukaisu v0.2).
- [ ] Ship through **our own F-Droid repo first** (low bar, full control,
      derisks the toolchain) before applying to the main catalog (Lukaisu v0.3:
      release signing + reproducible-build hygiene + fastlane metadata).
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
