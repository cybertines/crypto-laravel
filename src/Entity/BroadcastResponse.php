<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Entity;

class BroadcastResponse
{
    private string $txId = '';
    private bool $success = false;

    public function setTxId(string $txId): self
    {
        $this->txId = $txId;
        return $this;
    }

    public function getTxId(): string
    {
        return $this->txId;
    }

    public function setSuccess(bool $success = true): self
    {
        $this->success = $success;
        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
}
