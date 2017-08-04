<?php
/**
 * This file is part of Macaroons, a php implementation of Macaroons:
 * Cookies with Contextual Caveats for Decentralized Authorization in the Cloud
 *
 * (c) Mickaël Vieira <contact@mickael-vieira.com>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Macaroons\Crypto;

// @codingStandardsIgnoreLine
include_once __DIR__ . '/../../compatibility.php';

/**
 * @return string
 */
function crypto_gen_nonce(): string
{
    return random_bytes(crypto_get_nonce_length());
}

/**
 * @return int
 */
function crypto_get_nonce_length(): int
{
    return \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
}

/**
 * @param string $key
 *
 * @return string
 */
function crypto_gen_derived_key(string $key): string
{
    return crypto_hmac(str_pad('macaroons-key-generator', 32, "\0", STR_PAD_RIGHT), $key);
}

/**
 * @param string $plaintext
 * @param string $key
 *
 * @return string
 */
function crypto_encrypt(string $plaintext, string $key): string
{
    $nonce = crypto_gen_nonce();
    $box   = $nonce . \sodium_crypto_secretbox($plaintext, $nonce, $key);

    crypto_erase($nonce);
    crypto_erase($key);

    return $box;
}

/**
 * @param string $cipherText
 * @param string $nonce
 * @param string $key
 *
 * @return mixed
 *
 * @throws \LogicException
 */
function crypto_decrypt(string $cipherText, string $nonce, string $key)
{
    $plaintext = \sodium_crypto_secretbox_open($cipherText, $nonce, $key);

    crypto_erase($nonce);
    crypto_erase($key);

    if ($plaintext === false) {
        throw new \DomainException('Bad cipher text');
    }

    return $plaintext;
}

/**
 * @param string $key
 * @param string $data
 *
 * @return string
 */
function crypto_hmac(string $key, string $data): string
{
    return hash_hmac('sha256', $data, $key, true);
}

/**
 * @param string $key
 * @param string $data1
 * @param string $data2
 *
 * @return string
 */
function crypto_bound_hmac(string $key, string $data1, string $data2): string
{
    $hmac1 = crypto_hmac($key, $data1);
    $hmac2 = crypto_hmac($key, $data2);

    return crypto_hmac($key, $hmac1 . $hmac2);
}

/**
 * @param string $key
 *
 * @return string
 */
function crypto_ensure_key_length(string $key): string
{
    $thirtyTwo = \SODIUM_CRYPTO_SECRETBOX_KEYBYTES;

    if (strlen($key) > $thirtyTwo) {
        return substr($key, 0, $thirtyTwo);
    }

    if (strlen($key) < $thirtyTwo) {
        return str_pad($key, $thirtyTwo, "\0", STR_PAD_RIGHT);
    }

    return $key;
}

/**
 * @param mixed $data
 */
function crypto_erase($data)
{
    \sodium_memzero($data);
}

/**
 * @param string $data
 *
 * @return string
 */
function base64_url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * @param string $data
 *
 * @return string
 */
function base64_url_decode(string $data): string
{
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}
