<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Helper;

use Illuminate\Support\Str;

class Converter
{
    public static function coinToValue(float $amount, int $decimals, int $scale = 8): string
    {
        return static::div($amount, pow(10, $decimals), $scale);
    }

    public static function valueToCoin(float $amount, int $decimals, int $scale = 8): string
    {
        return static::mul($amount, pow(10, $decimals), $scale);
    }

    private static function div(float $num1, float $num2, int $scale = 8): string
    {
        return bcdiv(static::number($num1), static::number($num2), $scale);
    }

    private static function mul(float $num1, float $num2, int $scale = 8): string
    {
        return bcmul(static::number($num1), static::number($num2), $scale);
    }

    private static function number(float $value, int $decimals = 8): string
    {
        return number_format($value, $decimals, '.', '');
    }

    public static function hexToDec($hexString): float
    {
        return (float)hexdec($hexString);
    }

    public static function decToHex(string $amount): string
    {
        return '0x' . static::bcdechex($amount);
    }

    public static function leftPadZero(string $str, int $toLength = 64): string
    {
        return Str::padLeft($str, $toLength, '0');
    }

    /**
     * Convert large decimal numbers to hex numbers.
     * @see https://www.php.net/manual/en/ref.bc.php#99130
     * @param string $dec
     * @return string
     */
    public static function bcdechex(string $dec): string
    {
        $hex = '';
        do {
            $last = bcmod($dec, '16');
            $hex = dechex((int)$last) . $hex;
            $dec = bcdiv(bcsub($dec, $last), '16');
        } while ($dec > 0);

        return $hex;
    }
}
