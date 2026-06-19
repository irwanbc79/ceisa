<?php

/**
 * Bootstrap khusus PHPUnit.
 *
 * Memaksa APP_ENV=testing SEBELUM aplikasi Laravel di-bootstrap, sehingga
 * nilai APP_ENV di file .env (biasanya "local" untuk development) tidak
 * menimpa konfigurasi testing dari phpunit.xml.
 *
 * Latar belakang: Dotenv Laravel berjalan dalam mode immutable, namun .env
 * sering dimuat lebih awal daripada saat PHPUnit menerapkan <env> dari
 * phpunit.xml. Akibatnya VerifyCsrfToken::runningUnitTests() mengembalikan
 * false dan seluruh feature test yang melakukan POST/PUT/DELETE gagal dengan
 * 419 (CSRF token mismatch). Mengeset APP_ENV=testing di sini menjamin
 * `app()->runningUnitTests()` selalu benar tanpa bergantung pada isi .env.
 *
 * Jalankan test via `composer test` atau `vendor/bin/phpunit`.
 */

// Saat test runner berjalan, environment HARUS 'testing' agar
// VerifyCsrfToken::runningUnitTests() mengembalikan true. Selalu paksa,
// jangan kondisional — APP_ENV bisa sudah ter-set ke 'local' oleh .env
// yang dimuat lebih awal atau oleh shell environment.
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';
$_SERVER['APP_ENV'] = 'testing';

require __DIR__.'/../vendor/autoload.php';
