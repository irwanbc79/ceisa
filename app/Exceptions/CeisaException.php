<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class CeisaException extends Exception
{
    /**
     * Kode error spesifik dari CEISA (mis. 1008, 1023, 1028, 1042).
     */
    public ?string $ceisaCode;

    /**
     * Payload response mentah dari CEISA untuk keperluan logging/debug.
     *
     * @var array<string, mixed>|null
     */
    public ?array $context;

    /**
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        string $message,
        ?string $ceisaCode = null,
        ?array $context = null,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);

        $this->ceisaCode = $ceisaCode;
        $this->context = $context;
    }

    /**
     * Buat exception dari kode error CEISA, dengan pesan ramah dari config.
     *
     * @param  array<string, mixed>|null  $context
     */
    public static function fromCode(string $ceisaCode, ?string $fallback = null, ?array $context = null): self
    {
        $message = config("ceisa.error_codes.{$ceisaCode}")
            ?? $fallback
            ?? "CEISA mengembalikan kode error {$ceisaCode}.";

        return new self($message, $ceisaCode, $context);
    }
}
