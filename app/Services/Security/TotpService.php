<?php

namespace App\Services\Security;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

/**
 * RFC 6238 TOTP (Time-Based One-Time Password), implemented directly against
 * PHP's built-in hash_hmac() rather than adding a dependency. This is not
 * "rolling your own crypto" — TOTP is a public, standardized algorithm built
 * entirely on HMAC-SHA1, the same primitive this codebase already uses for
 * webhook signature verification (see AbstractPaymentProvider::sign()).
 */
class TotpService
{
    private const DIGITS = 6;

    private const PERIOD = 30;

    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** A fresh random 160-bit secret, base32-encoded (the conventional TOTP secret format). */
    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    /** The otpauth:// URI an authenticator app imports (manual-entry key is the same $secret). */
    public function provisioningUri(string $accountName, string $secret, string $issuer): string
    {
        $label = rawurlencode("{$issuer}:{$accountName}");
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);

        return "otpauth://totp/{$label}?{$params}";
    }

    /** A ready-to-embed `data:image/png;base64,...` QR code of the
     *  provisioning URI. Scanning is far less error-prone than manually
     *  retyping a 32-character secret — a single mistyped character there
     *  produces an authenticator entry that will never generate a matching
     *  code, with no indication anything's wrong until the user tries it. */
    public function qrCodeDataUri(string $provisioningUri): string
    {
        $result = (new Builder(
            writer: new PngWriter(),
            data: $provisioningUri,
            size: 240,
            margin: 12,
        ))->build();

        return $result->getDataUri();
    }

    /** Verifies a 6-digit code, allowing 1 time-step of clock drift either side. */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', (string) $code);
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $key = $this->base32Decode($secret);
        $step = intdiv(time(), self::PERIOD);

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($this->codeAt($key, $step + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    private function codeAt(string $key, int $counter): string
    {
        $binaryCounter = pack('N*', 0, $counter); // 8-byte big-endian counter
        $hash = hash_hmac('sha1', $binaryCounter, $key, true);

        $offset = ord($hash[19]) & 0x0F;
        $truncated =
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($truncated % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /** @return string[] Plain recovery codes — shown once; the caller must hash before storing. */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return array_map(
            fn () => strtoupper(bin2hex(random_bytes(4))).'-'.strtoupper(bin2hex(random_bytes(4))),
            range(1, $count),
        );
    }

    private function base32Encode(string $binary): string
    {
        $bits = '';
        foreach (str_split($binary) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($bits, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $encoded .= self::BASE32_ALPHABET[bindec($chunk)];
        }

        return $encoded;
    }

    private function base32Decode(string $base32): string
    {
        $base32 = strtoupper((string) preg_replace('/[^A-Z2-7]/i', '', $base32));

        $bits = '';
        foreach (str_split($base32) as $char) {
            $pos = strpos(self::BASE32_ALPHABET, $char);
            if ($pos === false) {
                continue;
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $binary = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $binary .= chr(bindec($byte));
            }
        }

        return $binary;
    }
}
