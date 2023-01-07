<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Entity;

use App\CryptoGatewayEngine\Exception\PaymentGatewayException;

final class CryptoCurrencyCoin
{
    public const BTC = 'btc';
    public const ETH = 'eth';
    public const LTC = 'ltc';
    public const XRP = 'xrp';
    public const TRX = 'trx';

    public const CURRENCIES = [
        self::BTC,
        self::ETH,
        self::LTC,
        self::XRP,
        self::TRX,
    ];

    /**
     * @var string|int
     */
    protected $value;

    /**
     * @param mixed $value
     * @throws PaymentGatewayException
     */
    public function __construct($value)
    {
        $this->value = $this->getValidValue($value);
    }

    public function getValue(): string
    {
        return (string) $this->value;
    }

    /**
     * @throws PaymentGatewayException
     */
    public static function instance(string $value): CryptoCurrencyCoin
    {
        return new CryptoCurrencyCoin($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @param $value
     * @return string|int
     * @throws PaymentGatewayException
     */
    protected function getValidValue($value)
    {
        if ($this->isValidValue($value)) {
            return $value;
        }

        throw new PaymentGatewayException(sprintf('Unsupported value: %s', $value));
    }

    protected function isValidValue($value): bool
    {
        return in_array($value, self::CURRENCIES);
    }
}
