create table if not exists public.affilixwp_licenses (
  id uuid primary key default gen_random_uuid(),
  key_hash text not null unique,
  plan text not null default 'pro',
  status text not null default 'active' check (status in ('active', 'revoked', 'suspended')),
  domain text,
  expires_at timestamptz,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create index if not exists affilixwp_licenses_status_idx
  on public.affilixwp_licenses (status);

alter table public.affilixwp_licenses enable row level security;

-- No client-role policies or grants: only the API's server-side service key can read licenses.
revoke all on table public.affilixwp_licenses from anon, authenticated;
