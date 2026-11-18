import { createHash } from 'node:crypto';

export type LicenseResult =
  | { valid: true; plan: string }
  | { valid: false; reason: 'missing' | 'inactive' | 'expired' | 'domain_mismatch' | 'unavailable' };

function normalizeDomain(input: string): string | null {
  try {
    const url = new URL(input.includes('://') ? input : `https://${input}`);
    return url.hostname.toLowerCase().replace(/^www\./, '');
  } catch {
    return null;
  }
}

function hashLicenseKey(key: string): string {
  return createHash('sha256').update(key.trim()).digest('hex');
}

export async function validateLicense(licenseKey: string, domain: string): Promise<LicenseResult> {
  const supabaseUrl = process.env.SUPABASE_URL;
  const serviceRoleKey = process.env.SUPABASE_SERVICE_ROLE_KEY;
  if (!supabaseUrl || !serviceRoleKey) return { valid: false, reason: 'unavailable' };
  if (!licenseKey.trim()) return { valid: false, reason: 'missing' };

  const normalizedDomain = normalizeDomain(domain);
  if (!normalizedDomain) return { valid: false, reason: 'domain_mismatch' };

  const query = new URL('/rest/v1/affilixwp_licenses', supabaseUrl);
  query.searchParams.set('select', 'plan,status,expires_at,domain');
  query.searchParams.set('key_hash', `eq.${hashLicenseKey(licenseKey)}`);
  query.searchParams.set('limit', '1');

  try {
    const response = await fetch(query, {
      headers: {
        apikey: serviceRoleKey,
        Authorization: `Bearer ${serviceRoleKey}`,
        Accept: 'application/json',
      },
      cache: 'no-store',
    });
    if (!response.ok) return { valid: false, reason: 'unavailable' };
    const rows = (await response.json()) as Array<{
      plan: string;
      status: string;
      expires_at: string | null;
      domain: string | null;
    }>;
    const license = rows[0];
    if (!license) return { valid: false, reason: 'missing' };
    if (license.status !== 'active') return { valid: false, reason: 'inactive' };
    if (license.expires_at && Date.parse(license.expires_at) <= Date.now()) {
      return { valid: false, reason: 'expired' };
    }
    if (license.domain && normalizeDomain(license.domain) !== normalizedDomain) {
      return { valid: false, reason: 'domain_mismatch' };
    }
    return { valid: true, plan: license.plan || 'pro' };
  } catch {
    return { valid: false, reason: 'unavailable' };
  }
}
