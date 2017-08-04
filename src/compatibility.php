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

/**
 * Rough mapping between libsodium v1 and sodium v2
 * @see https://github.com/jedisct1/libsodium-php
 */

/**
 * @return bool
 */
function has_sodium_v1_installed(): bool
{
    return function_exists('Sodium\version_string');
}

/**
 * @return bool
 */
function has_sodium_v2_installed(): bool
{
    return defined('SODIUM_LIBRARY_VERSION');
}

/**
 * @return void
 */
function compatibility()
{
    // the library will be distributed with PHP >= 7.2
    if (version_compare(PHP_VERSION, '7.2.0', '<')) {
        if (!has_sodium_v1_installed() && !has_sodium_v2_installed()) {
            echo "\033[0;31m" .
                "The PHP extension 'libsodium' is required to use 'macaroons' \n" .
                "but it does not seem to installed on your system. \n\n" .
                "See. https://github.com/jedisct1/libsodium-php for more information \n" .
                "\033[0m\n";
            exit;
        }

        if (has_sodium_v1_installed()) {
            /**
             * @constant int
             */
            define('SODIUM_CRYPTO_SECRETBOX_NONCEBYTES', \Sodium\CRYPTO_SECRETBOX_NONCEBYTES);
            define('SODIUM_CRYPTO_SECRETBOX_KEYBYTES', \Sodium\CRYPTO_SECRETBOX_KEYBYTES);

            /**
             * @param string $plaintext
             * @param string $nonce
             * @param string $key
             *
             * @return string
             */
            function sodium_crypto_secretbox(string $plaintext, string $nonce, string $key): string
            {
                return \Sodium\crypto_secretbox($plaintext, $nonce, $key);
            }

            /**
             * @param string $cipherText
             * @param string $nonce
             * @param string $key
             *
             * @return string
             */
            function sodium_crypto_secretbox_open(string $cipherText, string $nonce, string $key)
            {
                return \Sodium\crypto_secretbox_open($cipherText, $nonce, $key);
            }

            /**
             * @param $secret
             */
            function sodium_memzero($secret)
            {
                \Sodium\memzero($secret);
            }
        }
    }
}

compatibility();
