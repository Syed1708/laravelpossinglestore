<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Software Protection & Licensing Architecture Blueprint</title>
    <style>
        @page {
            margin: 22mm 18mm 22mm 18mm;
        }

        body {
            font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif;
            color: #1e293b;
            font-size: 10px;
            line-height: 1.45;
            background: #ffffff;
        }

        /* HEADER & FOOTER */
        .page-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 4px 0;
        }

        .subtitle {
            font-size: 10px;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }

        /* HEADINGS */
        h2 {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            background: #f1f5f9;
            border-left: 3px solid #3b82f6;
            padding: 4px 8px;
            margin-top: 16px;
            margin-bottom: 8px;
            page-break-after: avoid;
        }

        h3 {
            font-size: 10.5px;
            font-weight: bold;
            color: #1e293b;
            margin-top: 10px;
            margin-bottom: 4px;
            page-break-after: avoid;
        }

        p {
            margin: 0 0 6px 0;
        }

        /* CODE & PRE */
        pre {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 8px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 8px;
            line-height: 1.35;
            white-space: pre-wrap;
            word-break: break-all;
            margin: 6px 0 10px 0;
            page-break-inside: avoid;
            color: #0f172a;
        }

        code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 8.5px;
            background: #f1f5f9;
            padding: 1px 3px;
            border-radius: 3px;
            color: #2563eb;
        }

        /* TABLES */
        table.tbl {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 12px 0;
            page-break-inside: avoid;
        }

        table.tbl th, table.tbl td {
            padding: 5px 8px;
            border: 1px solid #cbd5e1;
            font-size: 8.5px;
            text-align: left;
        }

        table.tbl th {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }

        /* BOXES */
        .callout {
            background-color: #eff6ff;
            border-left: 3px solid #3b82f6;
            padding: 6px 10px;
            margin: 8px 0;
            font-size: 9px;
            color: #1e40af;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>

    <!-- COVER / HEADER -->
    <div class="page-header">
        <div class="title">Technical Blueprint &amp; Execution Guide</div>
        <div class="subtitle">Zero-Cost On-Premise Software Protection &amp; Licensing</div>
        <p style="margin-top: 4px; font-size: 8.5px; color: #64748b;">
            Target Architecture: Laravel 12 API + Next.js Standalone &nbsp;|&nbsp; Security: Asymmetric RSA-256 &nbsp;|&nbsp; Generated on: {{ now()->format('Y-m-d H:i') }}
        </p>
    </div>

    <!-- 1. THREAT MODEL -->
    <h2>1. Threat Model &amp; Architecture Overview</h2>
    <p>
        When client companies host software on their own servers or local computers, standard interpreted PHP files can be read, duplicated, or pirated. 
        This blueprint implements an asymmetric cryptographic verification model (RSA-256) where the software cannot run or be copied without an official signed license file.
    </p>

    <div class="callout">
        <strong>The Security Rule:</strong> The client machine only holds the <strong>Public Key</strong>. They can verify licenses, but they can never forge a license because the <strong>Private Key</strong> remains only on your private computer.
    </div>

    <!-- 2. DIAGRAMS -->
    <h2>2. Visual Workflow &amp; System Diagrams</h2>

    <h3>A. End-to-End Cryptographic License Lifecycle</h3>
<pre>
[ CLIENT SERVER / LAPTOP ]
           │
           ▼
Run: php artisan license:fingerprint
           │
           ▼
Sends Hardware ID (e.g. 8f4b2...a1) ──────────┐
                                              │
[ YOUR COMPUTER (Vendor) ]                    │
                                              ▼
Vendor Private Key + Hardware ID + Expiry ──▶ Artisan License Generator
                                              │
                                              ▼
Produces Encrypted &amp; Signed license.key ◀─────┘
           │
           ▼
[ EXECUTION RUNTIME ]
Client places license.key in project root
           │
           ▼
Incoming HTTP Request ──▶ CheckLicenseMiddleware
                               │
                               ├── License file exists? ────────[NO]──▶ 403 Forbidden
                               ├── RSA Signature valid? ────────[NO]──▶ 403 (Forged)
                               ├── Hardware ID matches PC? ─────[NO]──▶ 403 (Pirated)
                               ├── Date expired? ───────────────[YES]─▶ 403 (Expired)
                               └── Valid? ──────────────────────[YES]─▶ Proceed to API
</pre>

    <h3>B. Interlocked Next.js Frontend &amp; Laravel Backend Flow</h3>
<pre>
┌─────────────────────────────────┐                 ┌─────────────────────────────────┐
│     Next.js Standalone App      │                 │       Laravel 12 Backend        │
│    (Minified / Obfuscated)      │                 │     (RSA-Guarded API Engine)    │
└────────────────┬────────────────┘                 └────────────────┬────────────────┘
                 │                                                   │
                 │ 1. API Call (e.g., Get Menu / Create Order)       │
                 ├──────────────────────────────────────────────────▶│
                 │                                                   │ CheckLicenseMiddleware
                 │ 2. If Valid: Returns 200 OK + Data                │
                 │◀──────────────────────────────────────────────────┤
                 │                                                   │
                 │ 3. If Expired/Pirated: Returns 403 Error          │
                 │◀──────────────────────────────────────────────────┤
                 │                                                   │
                 ▼                                                   │
     Global Axios/Fetch Interceptor                                  │
                 │                                                   │
        Error === "LICENSE_RESTRICTED"?                              │
                 ├── [YES] ──▶ Display Fullscreen Lockout Screen     │
                 └── [NO]  ──▶ Normal Application Error Handling     │
</pre>

    <div class="page-break"></div>

    <!-- 3. LARAVEL CODE -->
    <h2>3. Backend Implementation (Laravel 12)</h2>

    <h3>Step 3.1: Licensing Service (app/Services/Licensing/LicenseService.php)</h3>
<pre>
&lt;?php

namespace App\Services\Licensing;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class LicenseService
{
    // Hardcoded Public Key (Safe to distribute to clients)
    public const PUBLIC_KEY = &lt;&lt;&lt;EOD
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA10J8eX9U3kXF8iGzV2vR...
-----END PUBLIC KEY-----
EOD;

    public function getHardwareIdentifier(): string
    {
        $raw = '';
        if (PHP_OS_FAMILY === 'Windows') {
            $uuid = shell_exec('powershell -Command "(Get-CimInstance Win32_ComputerSystemProduct).UUID" 2>NUL');
            $disk = shell_exec('powershell -Command "(Get-CimInstance Win32_DiskDrive)[0].SerialNumber" 2>NUL');
            $raw  = trim((string) $uuid) . '-' . trim((string) $disk);
        } else {
            if (File::exists('/etc/machine-id')) {
                $raw = trim(File::get('/etc/machine-id'));
            } else {
                $raw = shell_exec('cat /sys/class/net/$(ip route show default | awk \'{print $5}\')/address 2>/dev/null');
            }
        }
        return hash('sha256', !empty($raw) ? $raw : php_uname('n'));
    }

    public function verifyActiveLicense(): array
    {
        $path = base_path('license.key');
        if (!File::exists($path)) {
            return ['valid' => false, 'code' => 'LICENSE_MISSING', 'message' => 'license.key is missing.'];
        }

        $envelope = json_decode(File::get($path), true);
        $payloadJson = $envelope['payload'] ?? '';
        $signature   = base64_decode($envelope['signature'] ?? '');

        // 1. Verify Digital Signature with Public Key
        if (openssl_verify($payloadJson, $signature, self::PUBLIC_KEY, OPENSSL_ALGO_SHA256) !== 1) {
            return ['valid' => false, 'code' => 'SIGNATURE_INVALID', 'message' => 'License signature is forged.'];
        }

        $payload = json_decode($payloadJson, true);

        // 2. Hardware ID Check
        if (!empty($payload['hardware_id']) &amp;&amp; $payload['hardware_id'] !== $this->getHardwareIdentifier()) {
            return ['valid' => false, 'code' => 'HARDWARE_MISMATCH', 'message' => 'Machine ID does not match.'];
        }

        // 3. Domain Check
        if (!empty($payload['allowed_domains'])) {
            $host = request()->getHost();
            if (!in_array($host, (array) $payload['allowed_domains'], true)) {
                return ['valid' => false, 'code' => 'DOMAIN_UNAUTHORIZED', 'message' => "Domain [$host] unauthorized."];
            }
        }

        // 4. Expiration Date Check
        if (!empty($payload['expires_at']) &amp;&amp; Carbon::now()->gt(Carbon::parse($payload['expires_at'])->endOfDay())) {
            return ['valid' => false, 'code' => 'LICENSE_EXPIRED', 'message' => "License expired on {$payload['expires_at']}."];
        }

        return ['valid' => true, 'code' => 'ACTIVE', 'message' => 'License is operational.', 'payload' => $payload];
    }
}
</pre>

    <h3>Step 3.2: Middleware (app/Http/Middleware/CheckLicenseMiddleware.php)</h3>
<pre>
&lt;?php

namespace App\Http\Middleware;

use App\Services\Licensing\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckLicenseMiddleware
{
    public function __construct(protected LicenseService $licenseService) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/v1/license/*') || $request->is('up')) {
            return $next($request);
        }

        $check = $this->licenseService->verifyActiveLicense();

        if (!$check['valid']) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error'   => 'LICENSE_RESTRICTED',
                    'code'    => $check['code'],
                    'message' => $check['message'],
                ], 403);
            }
            abort(403, $check['message']);
        }

        return $next($request);
    }
}
</pre>

    <div class="page-break"></div>

    <h3>Step 3.3: License Generator Command (Run ONLY on Vendor Computer)</h3>
<pre>
&lt;?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class IssueLicenseCommand extends Command
{
    protected $signature = 'license:issue {--client=} {--hardware=} {--domain=*} {--days=365}';
    protected $description = 'Generate signed license.key using Vendor Private Key';

    public function handle(): int
    {
        $privateKey = File::get(storage_path('keys/license_private.pem'));

        $payload = [
            'client_name'     => $this->option('client') ?? 'Client Store',
            'hardware_id'     => $this->option('hardware') ?: null,
            'allowed_domains' => $this->option('domain') ?: ['localhost', '127.0.0.1'],
            'expires_at'      => Carbon::now()->addDays((int) $this->option('days'))->toDateString(),
            'issued_at'       => Carbon::now()->toIso8601String(),
        ];

        $payloadJson = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $signature   = '';
        openssl_sign($payloadJson, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $envelope = [
            'payload'   => $payloadJson,
            'signature' => base64_encode($signature),
        ];

        File::put(base_path('license.key'), json_encode($envelope, JSON_PRETTY_PRINT));
        $this->info("license.key created successfully!");
        return 0;
    }
}
</pre>

    <!-- 4. NEXT.JS CODE -->
    <h2>4. Frontend Implementation (Next.js Standalone)</h2>

    <h3>Step 4.1: Standalone Output Config (next.config.mjs)</h3>
<pre>
/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'standalone', // Bundles everything into minimal compiled JS inside .next/standalone
  poweredByHeader: false,
  reactStrictMode: true,
};

export default nextConfig;
</pre>

    <h3>Step 4.2: Automated Release Packaging &amp; Obfuscation (package-release.js)</h3>
<pre>
import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';

console.log('📦 1. Building Next.js Standalone Release...');
execSync('npm run build', { stdio: 'inherit' });

console.log('📂 2. Copying Assets to Standalone Folder...');
fs.cpSync(path.join('.next', 'static'), path.join('.next', 'standalone', '.next', 'static'), { recursive: true });
if (fs.existsSync('public')) {
  fs.cpSync('public', path.join('.next', 'standalone', 'public'), { recursive: true });
}

console.log('🛡️ 3. Obfuscating Entrypoint with Domain Lock...');
const serverFile = path.join('.next', 'standalone', 'server.js');
execSync(`npx javascript-obfuscator ${serverFile} --output ${serverFile} ` +
  `--domain-lock localhost 127.0.0.1 ` +
  `--self-defending true ` +
  `--string-array-encoding rc4`, { stdio: 'inherit' });

console.log('✅ Production bundle ready in [.next/standalone]!');
</pre>

    <!-- 5. CHECKLIST -->
    <h2>5. Deployment &amp; Delivery Checklist</h2>
    <table class="tbl">
        <thead>
            <tr>
                <th>Step</th>
                <th>Action</th>
                <th>Files Given to Client</th>
                <th>Files Kept on Your PC</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>1. Fingerprint</strong></td>
                <td>Run <code>php artisan license:fingerprint</code> on client PC</td>
                <td>None</td>
                <td>Client's Hardware ID</td>
            </tr>
            <tr>
                <td><strong>2. Issue</strong></td>
                <td>Run <code>php artisan license:issue --hardware="..."</code></td>
                <td><code>license.key</code></td>
                <td><code>license_private.pem</code> (Keep Secret)</td>
            </tr>
            <tr>
                <td><strong>3. Backend</strong></td>
                <td>Deploy Laravel with public key &amp; <code>license.key</code></td>
                <td>Project files (No <code>.git</code>)</td>
                <td>Private Git repo</td>
            </tr>
            <tr>
                <td><strong>4. Frontend</strong></td>
                <td>Run <code>node package-release.js</code></td>
                <td>Only <code>.next/standalone</code></td>
                <td>Raw React <code>src/</code> components</td>
            </tr>
        </tbody>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        Software Protection Blueprint &bull; Laravel 12 &bull; Next.js Standalone &bull; Certified Anti-Piracy Architecture
    </div>

</body>
</html>