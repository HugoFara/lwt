---
title: Security Followups
description: Residuals and follow-up investigation deferred from security hardening phases.
---

# Security Followups

Items deferred from the security hardening phases tracked in the
[changelog](../changelog.md). These are known residuals — not
shipped fixes — and are recorded here to keep the changelog focused
on what actually changed.

## SSRF — DNS rebinding TOCTOU (phase 5)

`UrlUtilities::safeHttpGet` validates each redirect hop's URL
before fetching it, but DNS resolution happens twice: once inside
`validateUrlForFetch` and once inside the actual `file_get_contents`
call. An attacker controlling an authoritative DNS server with
`TTL=0` can return a public address to the validator and a private
address (e.g. `127.0.0.1`) to the fetch.

Full mitigation requires resolving the hostname once, asserting the
resolved IP is public, then opening the connection by IP literal
with an explicit `Host` header. That is more invasive than the
current `file_get_contents`-based layer provides and is left for a
later pass.
