# ModShield for Flarum

Anti-spam protection for Flarum forums powered by ModShield Core.

## Installation

```bash
composer require modshield/flarum
```

## Configuration

1. Enable the extension in Flarum admin panel
2. Go to the extension settings page
3. Enter your **ModShield Core URL** and **API Key**
4. Select the **Mode**:
   - **Disabled** — Extension does nothing (no events sent)
   - **Active** — Events are sent to ModShield Core and its decisions are
     enforced (spam posts are hidden)

   > Observe-only is controlled by ModShield Core, not the connector: keep the
   > connector **Active** and set your ModShield Core site to shadow mode. Core
   > then returns `allow` and the connector hides nothing.
5. Select the **Fail Strategy** (when ModShield Core is unavailable):
   - **Fail open** — Posts are allowed through
   - **Fail closed** — Posts are held for review
6. (Optional) Set the **Moderator group ID** — members of this group are
   reported to ModShield as moderators (Flarum's default "Mods" group is `4`).

## Performance

Calls to ModShield Core run through Flarum's queue, so they do not block the
web request. With Flarum's default synchronous queue they execute inline (as
before); configure a real queue driver (e.g. Redis) to process them in the
background.

## How It Works

When a user creates or edits a post, the extension:
1. Builds a standardized payload with post content, user info, and links
2. Sends it to your ModShield Core instance for analysis
3. Applies the decision (allow, hide for review, or block)

When a moderator hides or deletes a post, feedback is sent to ModShield to improve future detection. When a moderator restores a hidden post, it's reported as a false positive.

## Capture Signals

The forum JS (`js/src/forum/index.ts`) attaches lightweight, best-effort
instrumentation to the composer, never blocking submission:

- When the composer opens, it requests a one-time token from
  `GET /modshield/capture-token` and records the time. This route is proxied
  through Flarum's own API — it forwards to ModShield Core's
  `/api/v1/capture/token` using the connector's configured API key, which
  never reaches the browser. Requires an authenticated user.
- It injects a hidden honeypot field (`ms_website`, positioned off-screen
  rather than `display:none`, so naive autofill bots still populate it) into
  the composer form.
- On submit, it attaches three attributes to the post request:
  `modshieldToken` (the fetched token, or `null` if the fetch failed),
  `modshieldFillMs` (time elapsed since the composer opened), and
  `modshieldHoneypot` (the honeypot field's value).

`PostSavingListener` reads these attributes off Flarum's `Saving` event and
stashes them on the post as `modshieldCapture`. `PayloadBuilder::fromPost()`
folds them into a `capture` block on the outbound payload: `form_token`,
`fill_time_ms` (only when a non-negative integer), and `honeypot_filled`
(boolean).

**No-JS behaviour**: if a post is created without the forum JS running (no-JS
client, or a direct API call), the capture attributes are simply absent.
`PostSavingListener` still emits a capture array, with a `null` token —
the absence of instrumentation is itself a signal ModShield Core can score.
Nothing is blocked or required.

## Signed Callbacks

ModShield Core can call back into Flarum to recommend a decision after
asynchronous scoring.

**Setup**:
1. In your ModShield Core site settings, set the callback URL to
   `<forum apiUrl>/modshield/callback`.
2. Copy the secret ModShield shows once at that point.
3. Paste it into the Flarum admin panel's **Callback secret** setting.

**Signature scheme** (Stripe-style): requests carry an
`X-ModShield-Signature` header shaped like
`t=<unix timestamp>,v1=<hex HMAC-SHA256 of "{t}.{raw body}" using the secret>`.
Multiple `v1=` entries are accepted, to support secret rotation. The
timestamp must be within 300 seconds of the current time, and match one of
the `v1` candidates.

**The callback recommends, the forum stays authoritative**: the
recommendation is only enforced when the extension mode is **Active** — in
any other mode the callback is recorded as observed and nothing changes. Even
in Active mode, an already-hidden post is left alone, and only a
`recommended_action` of `block` or `send_to_review` is enforced (anything
else, including `allow`, is a no-op). Enforcement hides the post, same as the
inline check.

**Status responses** (`POST /modshield/callback`):
- `503` — no callback secret configured yet (ModShield Core should keep
  retrying)
- `401` — signature missing or invalid (no further detail is returned)
- `200` with `{"status": "..."}` for every signature-verified payload, so
  ModShield Core stops retrying. `status` is one of:
  - `ignored` — request body isn't valid JSON
  - `expired` — payload's `expires_at` is in the past
  - `not_found` — `content_external_id` didn't resolve to a known post
    (expects the form `post_<id>`)
  - `observed` — extension mode isn't Active
  - `noop` — recommended action isn't enforceable, or the post was already
    hidden
  - `applied` — the post was hidden per the recommendation

## Requirements

- Flarum 1.x
- PHP 8.0+
- A running ModShield Core instance

## Development

```bash
# PHP
composer install --ignore-platform-reqs
php vendor/bin/phpunit

# Front-end (TypeScript; rebuild js/dist after changing js/src)
cd js
npm install
npm run check-typings   # tsc type check (needs composer install for Flarum core typings)
npm run format          # prettier
npm run build
```

## License

MIT
