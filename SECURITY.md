# Security Policy

## Supported versions

Only the latest major version receives security fixes. Please upgrade to 3.x before reporting.

| Version | PHP      | Laravel    | Supported |
| ------- | -------- | ---------- | --------- |
| 3.x     | 8.4, 8.5 | 12.x, 13.x | Yes       |
| 2.x     | 8.4, 8.5 | 12.x, 13.x | No        |
| 1.x     | 8.2, 8.3 | 10.x, 11.x | No        |

## Reporting a vulnerability

Please do **not** open a public issue, pull request or discussion for security problems.

Email **info@siberfx.com** with:

- a description of the issue and its impact;
- the affected package version (`composer show siberfx/laravel-tryoto`), PHP and Laravel versions;
- steps or a proof of concept to reproduce it.

You will get a reply within a few working days. Once the issue is confirmed, a fix will be
released in a new 3.x version as soon as possible, and you will be credited in the changelog
unless you prefer otherwise.

## Hardening tips

- Set `TRYOTO_WEBHOOK_AUTHORIZATION_KEY` so the callback route rejects calls that don't come from OTO.
- Set `TRYOTO_WEBHOOK_SECRET` and enable `TRYOTO_WEBHOOK_VERIFY_SIGNATURE` once you have confirmed
  that OTO's signatures validate for your account.
- Guard or remove the `GET /tryoto/set-webhook` route in production (publish the routes with
  `--tag=routes`).
- Keep every credential in `.env` and never commit it. That covers the OTO refresh tokens, the
  Slack webhook URL, the Telegram bot token and the webhook keys.
- Notifications include customer and order details (names, cities, tracking numbers). Only send
  them to private Slack channels, Telegram chats and mailboxes.
