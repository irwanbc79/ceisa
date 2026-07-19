<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('ceisa_references')
            ->where('type', 'kantor_pabean')
            ->where('code', '010700')
            ->update([
                'label' => 'KPPBC Tipe Madya Pabean Belawan',
                'updated_at' => now(),
            ]);

        Cache::forget('ceisa.kantor_pabean_map');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('ceisa_references')
            ->where('type', 'kantor_pabean')
            ->where('code', '010700')
            ->where('label', 'KPPBC Tipe Madya Pabean Belawan')
            ->update([
                'label' => 'KPPBC ELAWAN',
                'updated_at' => now(),
            ]);

        Cache::forget('ceisa.kantor_pabean_map');
    }
};
