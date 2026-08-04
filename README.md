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

## Requirements

- Flarum 1.x
- PHP 8.1+
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
