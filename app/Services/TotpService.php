<?php

namespace App\Services;

/**
 * Minimal RFC 6238 TOTP implementation (SHA-1, 6 digits, 30-second period)
 * so two-factor authentication does not require an external dependency.
 */
class TotpService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const PERIOD = 30;

    private const DIGITS = 6;

    public function generateSecret(int $bytes = 20): string
    {
        return $this->base32Encode(random_bytes($bytes));
    }

    public function code(string $secret, ?int $timestamp = null): string
    {
        $counter = intdiv($timestamp ?? time(), self::PERIOD);
        $binary = pack('N*', 0).pack('N*', $counter);
        $hash = hash_hmac('sha1', $binary, $this->base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;

        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (! preg_match('/^\d{'.self::DIGITS.'}$/', $code)) {
            return false;
        }

        $now = time();

        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals($this->code($secret, $now + ($offset * self::PERIOD)), $code)) {
                return true;
            }
        }

        return false;
    }

    public function provisioningUri(string $secret, string $account, string $issuer): string
    {
        $label = rawurlencode($issuer.':'.$account);
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);

        return "otpauth://totp/{$label}?{$params}";
    }

    private function base32Encode(string $data): string
    {
        $bits = '';

        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';

        foreach (str_split($bits, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $encoded .= self::ALPHABET[bindec($chunk)];
        }

        return $encoded;
    }

    private function base32Decode(string $secret): string
    {
        $map = array_flip(str_split(self::ALPHABET));
        $bits = '';

        foreach (str_split(strtoupper($secret)) as $char) {
            if (! isset($map[$char])) {
                continue;
            }

            $bits .= str_pad(decbin($map[$char]), 5, '0', STR_PAD_LEFT);
        }

        $decoded = '';

        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $decoded .= chr(bindec($byte));
            }
        }

        return $decoded;
    }
}
