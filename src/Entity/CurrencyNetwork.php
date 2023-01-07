<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Entity;

use App\CryptoGatewayEngine\Enum\TokenType;

class CurrencyNetwork
{
    protected int $decimals;
    protected TokenType $tokenType;
    protected string $contract;

    public function __construct(TokenType $tokenType, int $decimals, string $contract = '')
    {
        $this->tokenType = $tokenType;
        $this->decimals = $decimals;
        $this->contract = $contract;
    }

    public function getContract(): string
    {
        return $this->contract;
    }

    public function isErc20(): bool
    {
        return $this->tokenType->is(TokenType::ERC20);
    }

    public function isTrc20(): bool
    {
        return $this->tokenType->is(TokenType::TRC20);
    }

    public function isDefaultType(): bool
    {
        return $this->tokenType->is(TokenType::DEFAULT);
    }

    public function getTokenType(): string
    {
        return $this->tokenType->value;
    }

    public function getDecimals(): int
    {
        return $this->decimals;
    }
}
