<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\WalletGenerator\Util;

class TronHelper
{
    public static function getBase58CheckAddress(string $addressBin): string
    {
        $checksum = substr(self::makeHash(self::makeHash($addressBin)), 0, 4);
        $checksum = $addressBin . $checksum;

        return self::encodeByNum(self::bin2bc($checksum));
    }

    public static function makeHash(string $data, bool $raw = true): string
    {
        return hash('sha256', $data, $raw);
    }

    public static function dec2base(string $dec, int $base, ?string $digits = null): string
    {
        if (extension_loaded('bcmath')) {
            if ($base < 2 || $base > 256) {
                die('Invalid Base: ' . $base);
            }
            bcscale(0);
            $value = "";
            if (!$digits) {
                $digits = self::digits($base);
            }
            while ($dec > $base - 1) {
                $rest = bcmod($dec, (string)$base);
                $dec = bcdiv($dec, (string)$base);
                $value = $digits[$rest] . $value;
            }
            return $digits[intval($dec)] . $value;
        } else {
            die('Please install BCMATH');
        }
    }

    public static function base2dec(string $value, int $base, ?string $digits = null): string
    {
        if (extension_loaded('bcmath')) {
            if ($base < 2 || $base > 256) {
                die('Invalid Base: ' . $base);
            }
            bcscale(0);
            if ($base < 37) {
                $value = strtolower($value);
            }
            if (!$digits) {
                $digits = self::digits($base);
            }
            $size = strlen($value);
            $dec = '0';
            for ($loop = 0; $loop < $size; $loop++) {
                $element = strpos($digits, $value[$loop]);
                $power = bcpow((string)$base, (string)($size - $loop - 1));
                $dec = bcadd($dec, bcmul((string)$element, $power));
            }

            return $dec;
        } else {
            die('Please install BCMATH');
        }
    }

    public static function digits(int $base): string
    {
        if ($base > 64) {
            $digits = "";
            for ($loop = 0; $loop < 256; $loop++) {
                $digits .= chr($loop);
            }
        } else {
            $digits = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
        }
        $digits = substr($digits, 0, $base);

        return (string)$digits;
    }

    public static function bin2bc(string $num): string
    {
        return self::base2dec($num, 256);
    }

    public static function encodeByNum(string $num, int $length = 58): string
    {
        return self::dec2base($num, $length, '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz');
    }

    public static function decodeByAddress(string $address, int $length = 58): string
    {
        return self::base2dec($address, $length, '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz');
    }
}
