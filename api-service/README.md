# AffilixWP API

Private backend for AffilixWP licence validation and WordPress update metadata.

## Routes

- `POST /api/license/validate` — accepts `license_key` and `domain`; returns `{ valid, plan }`.
- `POST /api/update/check` — accepts `slug`, `version`, `licenseKey`, and `domain`; returns update metadata only when the licence is valid and a newer release is configured.
- `GET /api/health` — service health check.

## Environment

Copy `.env.example` values into Vercel after creating a dedicated AffilixWP database project. Never use UNLIVO's database or credentials.

Apply `supabase/schema.sql` to that dedicated project. Store only SHA-256 hashes of licence keys in `affilixwp_licenses.key_hash`; never store raw keys. The table has RLS enabled and no anon/authenticated grants.

Set `AFFILIXWP_LATEST_VERSION` and `AFFILIXWP_DOWNLOAD_URL` only after publishing a matching, tested GitHub release ZIP. Until both are set, the updater safely reports no update.

## Local development

```sh
npm install
npm run dev
```
