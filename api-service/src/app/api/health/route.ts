import { NextResponse } from 'next/server';

export async function GET() {
  return NextResponse.json({ service: 'affilixwp-api', status: 'ok' });
}
