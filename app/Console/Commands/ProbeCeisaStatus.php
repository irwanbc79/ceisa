<?php

namespace App\Console\Commands;

use App\Models\CeisaCredential;
use App\Services\CeisaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProbeCeisaStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ceisa:probe-status {--user= : ID user pemilik kredensial H2H}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probe status/document list endpoints on CEISA API to discover response schema';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting CEISA Status Probing...');

        $credential = $this->resolveCredential();

        if (! $credential) {
            $this->error('Kredensial CEISA H2H tidak ditemukan di database.');
            $this->line('Isi kredensial H2H terlebih dahulu melalui halaman Pengaturan di portal.');
            return self::FAILURE;
        }

        $this->info("Kredensial ditemukan untuk user: {$credential->user->name} (NPWP: {$credential->npwp})");
        $this->info("Menggunakan Base URL: " . ($credential->base_url ?: config('ceisa.base_url')));

        $service = CeisaService::forCredential($credential);

        // Uji login & dapatkan token
        try {
            $this->info('Mencoba autentikasi ke CEISA...');
            $token = $service->refreshTokenIfExpired();
            $this->info('✓ Autentikasi berhasil.');
        } catch (Throwable $e) {
            $this->error('✗ Gagal autentikasi: ' . $e->getMessage());
            return self::FAILURE;
        }

        $npwp = $credential->npwp;
        $npwp15 = (strlen($npwp) === 16 && str_starts_with($npwp, '0')) ? substr($npwp, 1) : $npwp;
        $statusEndpoint = config('ceisa.endpoints.status', '/v2/openapi/status');

        $probes = [
            [
                'name' => '1. GET /v2/openapi/status?idPerusahaan={NPWP16} (16-digit NPWP)',
                'method' => 'GET',
                'path' => $statusEndpoint,
                'query' => ['idPerusahaan' => $npwp],
            ],
            [
                'name' => '2. GET /v2/openapi/status?idPerusahaan={NPWP15} (15-digit NPWP)',
                'method' => 'GET',
                'path' => $statusEndpoint,
                'query' => ['idPerusahaan' => $npwp15],
            ],
            [
                'name' => '3. GET /v2/openapi/status?npwp={NPWP15} (Variant parameter NPWP15)',
                'method' => 'GET',
                'path' => $statusEndpoint,
                'query' => ['npwp' => $npwp15],
            ],
            [
                'name' => '4. GET /v2/openapi/status (No query parameters)',
                'method' => 'GET',
                'path' => $statusEndpoint,
                'query' => [],
            ],
            [
                'name' => '5. GET /v2/openapi/document (Check list endpoint)',
                'method' => 'GET',
                'path' => '/v2/openapi/document',
                'query' => [],
            ]
        ];

        // Ambil maksimal 5 dokumen riil untuk dicoba tracking status per nomor_aju
        try {
            $realDocs = \App\Models\Document::whereNotNull('nomor_aju')
                ->where('nomor_aju', '!=', '')
                ->latest()
                ->limit(5)
                ->get();

            foreach ($realDocs as $idx => $doc) {
                $probes[] = [
                    'name' => "Real Doc Status - {$doc->nomor_aju} (Tanpa idHeader)",
                    'method' => 'GET',
                    'path' => rtrim($statusEndpoint, '/') . '/' . $doc->nomor_aju,
                    'query' => [],
                ];
                if (!empty($doc->id_header)) {
                    $probes[] = [
                        'name' => "Real Doc Status - {$doc->nomor_aju} (Dengan idHeader: {$doc->id_header})",
                        'method' => 'GET',
                        'path' => rtrim($statusEndpoint, '/') . '/' . $doc->nomor_aju,
                        'query' => ['idHeader' => $doc->id_header],
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Abaikan jika tabel documents belum ada/error
        }

        $logPath = storage_path('logs/ceisa-probe.log');
        @unlink($logPath); // Bersihkan log sebelumnya jika ada

        $this->logAndOutput("==============================================", $logPath);
        $this->logAndOutput(" CEISA H2H API PROBE RUN: " . now()->toDateTimeString(), $logPath);
        $this->logAndOutput(" NPWP Perusahaan: {$npwp}", $logPath);
        $this->logAndOutput("==============================================", $logPath);

        foreach ($probes as $probe) {
            $this->newLine();
            $this->info("Running probe: {$probe['name']}...");

            try {
                $response = $service->probe($probe['method'], $probe['path'], $probe['query']);
                $statusCode = $response->status();
                $body = $response->body();
                $headers = $response->headers();

                $this->logAndOutput("--> Request: {$probe['method']} {$probe['path']} " . json_encode($probe['query']), $logPath);
                $this->logAndOutput("<-- Response Status: {$statusCode}", $logPath);
                
                // Format headers untuk log
                $this->logAndOutput("<-- Response Headers:", $logPath);
                foreach ($headers as $name => $values) {
                    $this->logAndOutput("    {$name}: " . implode(', ', $values), $logPath);
                }

                // Format body
                $decoded = json_decode($body, true);
                $formattedBody = json_last_error() === JSON_ERROR_NONE
                    ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    : $body;

                $this->logAndOutput("<-- Response Body:", $logPath);
                $this->logAndOutput($formattedBody, $logPath);
                $this->logAndOutput("----------------------------------------------", $logPath);

                if ($response->successful()) {
                    $this->info("✓ Berhasil (HTTP {$statusCode})");
                } else {
                    $this->warn("⚠ Gagal (HTTP {$statusCode})");
                }
            } catch (Throwable $e) {
                $this->error("✗ Error: " . $e->getMessage());
                $this->logAndOutput("--> Request: {$probe['method']} {$probe['path']}", $logPath);
                $this->logAndOutput("✗ Exception: " . $e->getMessage(), $logPath);
                $this->logAndOutput($e->getTraceAsString(), $logPath);
                $this->logAndOutput("----------------------------------------------", $logPath);
            }
        }

        $this->newLine();
        $this->info("Probing selesai! Seluruh detail request & response disimpan di:");
        $this->comment($logPath);

        return self::SUCCESS;
    }

    /**
     * Resolve ceisa credential.
     */
    private function resolveCredential(): ?CeisaCredential
    {
        if ($userId = $this->option('user')) {
            return CeisaCredential::where('user_id', $userId)->first();
        }

        return CeisaCredential::query()->latest('id')->first();
    }

    /**
     * Log to file and output to console.
     */
    private function logAndOutput(string $message, string $logPath): void
    {
        $this->line($message);
        file_put_contents($logPath, $message . PHP_EOL, FILE_APPEND);
    }
}
