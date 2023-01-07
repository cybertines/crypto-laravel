<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Entity;

use App\CryptoGatewayEngine\Enum\CryptoCurrency;

class Currency
{
    protected CryptoCurrency $currency;
    protected CurrencyNetwork $network;

    public function __construct(CryptoCurrency $currency, CurrencyNetwork $network)
    {
        $this->currency = $currency;
        $this->network = $network;
    }

    public function getNetwork(): CurrencyNetwork
    {
        return $this->network;
    }

    public function getCode(): string
    {
        return $this->currency->value;
    }

    public function getUpperCode(): string
    {
        return strtoupper($this->getCode());
    }

    public function isErc20(): bool
    {
        return $this->network->isErc20();
    }

    public function isTrc20(): bool
    {
        return $this->network->isTrc20();
    }

    public function isDefaultType(): bool
    {
        return $this->network->isDefaultType();
    }

    public function getContractAddress(): string
    {
        return $this->network->getContract();
    }

    public function getFullCoinCode(): string
    {
        return $this->isDefaultType() ? $this->getCode() : $this->getCode() . '-' . $this->network->getTokenType();
    }

    public function getDecimals(): int
    {
        return $this->network->getDecimals();
    }

    public function getNode(): string
    {
        if ($this->isErc20()) {
            $node = CryptoCurrency::ETH;
        } elseif ($this->isTrc20()) {
            $node = CryptoCurrency::TRX;
        }

        return $node ?? $this->getCode();
    }

    public function getTokenCode(): string
    {
        if ($this->isErc20() || $this->isTrc20()) {
            $token = $this->getCode();
        }

        return $token ?? '';
    }
}
