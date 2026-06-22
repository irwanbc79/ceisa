<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage:
     *   php artisan admin:create
     *   php artisan admin:create admin@m2b.co.id
     *   php artisan admin:create admin@m2b.co.id MySecretPass123
     *
     * @var string
     */
    protected $signature = 'admin:create
                            {email=admin@m2b.co.id : Email admin}
                            {password? : Password (akan digenerate jika kosong)}
                            {--name=Admin M2B : Nama admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Buat user admin untuk portal CEISA H2H';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $name = $this->option('name');
        $password = $this->argument('password') ?? Str::random(16);

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => bcrypt($password),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ],
        );

        if (! $user->wasRecentlyCreated) {
            // User sudah ada — update password agar pasti bisa login.
            $user->update([
                'password' => bcrypt($password),
                'email_verified_at' => $user->email_verified_at ?? now(),
                'role' => $user->role ?? User::ROLE_ADMIN,
            ]);

            $this->info("User {$email} sudah ada — password diperbarui.");
        } else {
            $this->info("User admin berhasil dibuat.");
        }

        $this->newLine();
        $this->line('  <fg=green>Email    :</> '.$email);
        $this->line('  <fg=green>Password :</> '.$password);
        $this->line('  <fg=green>Role     :</> admin');
        $this->newLine();
        $this->warn('  Simpan password ini di tempat aman.');

        return self::SUCCESS;
    }
}
