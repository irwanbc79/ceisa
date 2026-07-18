<?php

namespace App\Console\Commands;

use App\Models\CeisaCredential;
use App\Services\CeisaService;
use Illuminate\Console\Command;
use Throwable;

class SyncCeisaDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ceisa:sync-documents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronkan status dan respons seluruh dokumen perusahaan dari CEISA';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $credential = CeisaCredential::shared();

        if (! $credential || ! $credential->user) {
            $this->error('Kredensial CEISA perusahaan belum tersedia.');

            return self::FAILURE;
        }

        try {
            $count = CeisaService::forCredential($credential)->syncDocuments($credential->user);
        } catch (Throwable $e) {
            report($e);
            $this->error('Sinkronisasi CEISA gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Sinkronisasi CEISA selesai: {$count} dokumen diperbarui.");

        return self::SUCCESS;
    }
}
