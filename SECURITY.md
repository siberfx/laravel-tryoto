# Security Policy

## Supported versions

| Version | Supported |
| ------- | --------- |
| 2.x     | Yes       |
| 1.x     | No        |

## Reporting a vulnerability

Please do **not** open a public issue for security problems. Email **info@siberfx.com** with a
description, the affected version and steps to reproduce. You will get a reply within a few
working days, and a fix will be released as soon as possible.

## Hardening tips

- Set `TRYOTO_WEBHOOK_AUTHORIZATION_KEY` so the callback route rejects calls that don't come from OTO.
- Guard or remove the `GET /tryoto/set-webhook` route in production (publish the routes with
  `--tag=routes`).
- Never commit your OTO refresh token; keep it in `.env`.
