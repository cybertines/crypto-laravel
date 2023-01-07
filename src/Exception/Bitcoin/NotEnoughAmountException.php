<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Exception\Bitcoin;

use App\CryptoGatewayEngine\Exception\PaymentGatewayException;

class NotEnoughAmountException extends PaymentGatewayException
{
    private int $fee;
    private int $transferAmount;
    private int $amountInUtxo;

    public function __construct(int $fee, int $transferAmount, int $amountInUtxo, $message = "")
    {
        $this->fee = $fee;
        $this->transferAmount = $transferAmount;
        $this->amountInUtxo = $amountInUtxo;
        parent::__construct($message);
    }

    public function getFeeSatoshi(): int
    {
        return $this->fee;
    }

    public function getTransferAmountSatoshi(): int
    {
        return $this->transferAmount;
    }

    public function getAmountInUtxoSatoshi(): int
    {
        return $this->amountInUtxo;
    }
}
