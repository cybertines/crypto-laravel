<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Entity;

class Wallet
{
    private string $address;
    private string $privateKey;
    private Currency $currency;
    private string $pubKey;

    public function __construct(string $address, string $privateKey, Currency $currency, string $pubKey = '')
    {
        $this->address = $address;
        $this->privateKey = $privateKey;
        $this->currency = $currency;
        $this->pubKey = $pubKey;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function isErc20(): bool
    {
        return $this->currency->isErc20();
    }

    public function isTrc20(): bool
    {
        return $this->currency->isTrc20();
    }

    public function isDefaultType(): bool
    {
        return $this->currency->isDefaultType();
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getContractAddress(): string
    {
        return $this->currency->getContractAddress();
    }

    public function getDecimals(): int
    {
        return $this->currency->getDecimals();
    }

    public function getPublicKey(): string
    {
        return $this->pubKey;
    }
}
