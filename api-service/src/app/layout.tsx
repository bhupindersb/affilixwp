import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'AffilixWP API',
  description: 'Private API service for AffilixWP licence validation and updates.',
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="en">
      <body style={{ fontFamily: 'Arial, sans-serif', margin: 0, padding: '48px', color: '#16313b' }}>
        {children}
      </body>
    </html>
  );
}
