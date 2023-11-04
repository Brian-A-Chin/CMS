<?php


    class Cryptography {

        private static string $method = 'AES-256-CFB';
        private static string $padwith = '`';
        private static int $blocksize = 32;

        private static function getKey(): string{
            return substr(hash('sha256', SECRET_KEY), 0, 32);
        }

        private static function base64url_encode($bin) {
            return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($bin));
        }

        private static function base64url_decode($str) {
            return base64_decode(str_replace(['-', '_'], ['+', '/'], $str));
        }

        private static function getIv(): string{
            return substr(hash('sha256', SALT), 0, 16);
        }

        public static function encrypt($string):string{
            $padded_secret = $string . str_repeat(self::$padwith, (self::$blocksize - strlen($string) % self::$blocksize));
            $encrypted_string = openssl_encrypt($padded_secret, self::$method, self::getKey(), OPENSSL_RAW_DATA, self::getIv());
            return self::base64url_encode($encrypted_string);
        }

        public static function decrypt($string):string{
            $decoded_secret = self::base64url_decode($string);
            $decrypted_secret = openssl_decrypt($decoded_secret, self::$method, self::getKey(), OPENSSL_RAW_DATA, self::getIv());
            return rtrim($decrypted_secret, self::$padwith);
        }


    }