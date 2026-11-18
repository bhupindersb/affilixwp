import { NextRequest, NextResponse } from 'next/server';
import { validateLicense } from '@/lib/license';

export const runtime = 'nodejs';

export async function POST(request: NextRequest) {
  let body: { license_key?: unknown; domain?: unknown };
  try {
    body = await request.json();
  } catch {
    return NextResponse.json({ valid: false, error: 'invalid_json' }, { status: 400 });
  }

  const licenseKey = typeof body.license_key === 'string' ? body.license_key : '';
  const domain = typeof body.domain === 'string' ? body.domain : '';
  if (!licenseKey || !domain) {
    return NextResponse.json({ valid: false, error: 'license_key_and_domain_required' }, { status: 400 });
  }

  const result = await validateLicense(licenseKey, domain);
  if (!result.valid) {
    const status = result.reason === 'unavailable' ? 503 : 200;
    return NextResponse.json({ valid: false, reason: result.reason }, { status });
  }
  return NextResponse.json({ valid: true, plan: result.plan });
}
