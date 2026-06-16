<?php

namespace App\Services\AI;

use RuntimeException;

/**
 * Kesalahan saat memanggil provider AI (koneksi, auth, atau response invalid).
 */
class AiException extends RuntimeException {}
