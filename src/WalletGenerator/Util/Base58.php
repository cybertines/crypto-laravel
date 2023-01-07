<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\WalletGenerator\Util;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Exceptions\{Base58ChecksumFailure, Base58InvalidCharacter};
use BitWasp\Buffertools\{Buffer, BufferInterface};
use Exception;

class Base58
{
    protected static string $base58chars = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    public static function setBase(string $base): void
    {
        static::$base58chars = $base;
    }

    /**
     * Encode a given hex string in base58
     *
     * @param BufferInterface $buffer
     * @param bool $replace
     * @return string
     */
    public static function encode(BufferInterface $buffer, bool $replace = true): string
    {
        $size = $buffer->getSize();
        if ($size === 0) {
            return '';
        }

        $orig = $buffer->getBinary();
        $decimal = $buffer->getGmp();

        $return = '';
        $zero = gmp_init(0);
        $_58 = gmp_init(58);
        while (gmp_cmp($decimal, $zero) > 0) {
            $div = gmp_div($decimal, $_58);
            $rem = gmp_sub($decimal, gmp_mul($div, $_58));
            $return .= self::$base58chars[(int)gmp_strval($rem, 10)];
            $decimal = $div;
        }
        $return = strrev($return);

        if ($replace) {
            for ($i = 0; $i < $size && $orig[$i] === "\x00"; $i++) {
                $return = '1' . $return;
            }
        } else {
            for ($i = 0; $i < $size && $orig[$i] === "\x00"; $i++) {
                $return = 'r' . $return;
            }
        }

        return $return;
    }

    /**
     * Decode a base58 string
     * @param string $base58
     * @return BufferInterface
     * @throws Base58InvalidCharacter
     * @throws Exception
     */
    public static function decode(string $base58): BufferInterface
    {
        if ($base58 === '') {
            return new Buffer('', 0);
        }

        $original = $base58;
        $length = strlen($base58);
        $return = gmp_init(0);
        $_58 = gmp_init(58);
        for ($i = 0; $i < $length; $i++) {
            $loc = strpos(self::$base58chars, $base58[$i]);
            if ($loc === false) {
                throw new Base58InvalidCharacter('Found character that is not allowed in base58: ' . $base58[$i]);
            }
            $return = gmp_add(gmp_mul($return, $_58), gmp_init($loc, 10));
        }

        $binary = gmp_cmp($return, gmp_init(0)) === 0 ? '' : Buffer::int(gmp_strval($return, 10))->getBinary();
        for ($i = 0; $i < $length && $original[$i] === '1'; $i++) {
            $binary = "\x00" . $binary;
        }

        return new Buffer($binary);
    }

    /**
     * @param BufferInterface $data
     * @return BufferInterface
     * @throws Exception
     */
    public static function checksum(BufferInterface $data): BufferInterface
    {
        return Hash::sha256d($data)->slice(0, 4);
    }

    /**
     * Decode a base58 checksum string and validate checksum
     * @param string $base58
     * @return BufferInterface
     * @throws Base58ChecksumFailure
     * @throws Base58InvalidCharacter
     * @throws Exception
     */
    public static function decodeCheck(string $base58): BufferInterface
    {
        $decoded = self::decode($base58);
        $checksumLength = 4;
        if ($decoded->getSize() < $checksumLength) {
            throw new Base58ChecksumFailure('Missing base58 checksum');
        }

        $data = $decoded->slice(0, -$checksumLength);
        $csVerify = $decoded->slice(-$checksumLength);

        if (!hash_equals(self::checksum($data)->getBinary(), $csVerify->getBinary())) {
            throw new Base58ChecksumFailure('Failed to verify checksum');
        }

        return $data;
    }
}
