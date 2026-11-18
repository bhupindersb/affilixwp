import { NextRequest, NextResponse } from 'next/server';
import { validateLicense } from '@/lib/license';

export const runtime = 'nodejs';

export async function POST(request: NextRequest) {
  let body: { slug?: unknown; version?: unknown; licenseKey?: unknown; domain?: unknown };
  try {
    body = await request.json();
  } catch {
    return NextResponse.json({ error: 'invalid_json' }, { status: 400 });
  }

  if (body.slug !== 'affilixwp') return NextResponse.json({}, { status: 404 });
  const version = typeof body.version === 'string' ? body.version : '';
  const licenseKey = typeof body.licenseKey === 'string' ? body.licenseKey : '';
  const domain = typeof body.domain === 'string' ? body.domain : '';
  if (!version || !licenseKey || !domain) {
    return NextResponse.json({ error: 'version_license_and_domain_required' }, { status: 400 });
  }

  const license = await validateLicense(licenseKey, domain);
  if (!license.valid) {
    return NextResponse.json({}, { status: license.reason === 'unavailable' ? 503 : 200 });
  }

  const latestVersion = process.env.AFFILIXWP_LATEST_VERSION || '';
  const downloadUrl = process.env.AFFILIXWP_DOWNLOAD_URL || '';
  const homepage = process.env.AFFILIXWP_HOMEPAGE || 'https://affilixwp.beveeztech.com';

  if (!latestVersion || !downloadUrl || compareVersions(version, latestVersion) >= 0) {
    return NextResponse.json({});
  }

  return NextResponse.json({
    slug: 'affilixwp',
    new_version: latestVersion,
    homepage,
    download_url: downloadUrl,
  });
}

function compareVersions(left: string, right: string): number {
  const a = left.split('.').map((part) => Number.parseInt(part, 10) || 0);
  const b = right.split('.').map((part) => Number.parseInt(part, 10) || 0);
  for (let i = 0; i < Math.max(a.length, b.length); i += 1) {
    const diff = (a[i] || 0) - (b[i] || 0);
    if (diff !== 0) return diff > 0 ? 1 : -1;
  }
  return 0;
}
