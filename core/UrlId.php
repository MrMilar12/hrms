<?php

/** Encrypts database IDs for URLs. Authorization must still be checked after decoding. */
final class UrlId
{
    private const CIPHER = 'aes-256-gcm';

    public static function encode(int $id): string
    {
        if ($id < 1) throw new InvalidArgumentException('Invalid record ID.');
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt((string) $id, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag, 'hrms-url-id', 16);
        if ($ciphertext === false) throw new RuntimeException('Unable to protect URL identifier.');
        return self::base64UrlEncode($iv . $tag . $ciphertext);
    }

    public static function decode(string $token): ?int
    {
        // Plain sequential IDs are deliberately rejected.
        $payload = self::base64UrlDecode($token);
        if ($payload === null || strlen($payload) < 29) return null;
        $plain = openssl_decrypt(substr($payload, 28), self::CIPHER, self::key(), OPENSSL_RAW_DATA, substr($payload, 0, 12), substr($payload, 12, 16), 'hrms-url-id');
        if ($plain === false || !ctype_digit($plain) || (int) $plain < 1) return null;
        return (int) $plain;
    }

    private static function key(): string
    {
        static $key;
        if ($key !== null) return $key;
        $path = STORAGE_PATH . '/app.key';
        if (is_file($path)) {
            $stored = trim((string) file_get_contents($path));
            $decoded = base64_decode($stored, true);
            if ($decoded !== false && strlen($decoded) === 32) return $key = $decoded;
        }
        $generated = random_bytes(32);
        if (file_put_contents($path, base64_encode($generated) . "\n", LOCK_EX) === false) {
            throw new RuntimeException('Unable to create the URL encryption key.');
        }
        @chmod($path, 0640);
        return $key = $generated;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): ?string
    {
        if ($value === '' || preg_match('/[^A-Za-z0-9_-]/', $value)) return null;
        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        return $decoded === false ? null : $decoded;
    }
}
