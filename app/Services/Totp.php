<?php

declare(strict_types=1);

namespace App\Services;

/** RFC 4226 / RFC 6238: SHA-1, six digits, 30-second steps, ±1 step clock tolerance. */
final class Totp
{
    public function secret(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split(random_bytes(20)) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        $secret = '';
        foreach (str_split($bits, 5) as $chunk) {
            $secret .= $alphabet[bindec($chunk)];
        }

        return $secret;
    }

    public function code(string $secret, int $step, int $digits = 6): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($secret) as $char) {
            $position = strpos($alphabet, $char);
            if ($position === false) {
                throw new \InvalidArgumentException('Invalid authenticator secret.');
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }
        $key = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $key .= chr(bindec($chunk));
            }
        }
        $digest = hash_hmac('sha1', pack('N2', intdiv($step, 4294967296), $step % 4294967296), $key, true);
        $offset = ord($digest[19]) & 15;
        $binary = unpack('N', substr($digest, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($binary % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    public function matchingStep(string $secret, string $code, int $lastUsedStep = -1): ?int
    {
        if (! preg_match('/^\d{6}$/D', $code)) {
            return null;
        }
        $current = intdiv(now()->timestamp, 30);
        foreach ([$current, $current - 1, $current + 1] as $step) {
            if ($step > $lastUsedStep && hash_equals($this->code($secret, $step), $code)) {
                return $step;
            }
        }

        return null;
    }
}
