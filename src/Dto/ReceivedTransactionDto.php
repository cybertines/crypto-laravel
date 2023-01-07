<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Dto;

use Spatie\DataTransferObject\DataTransferObject;

class ReceivedTransactionDto extends DataTransferObject
{
    public string $hash;
    public float $amountInCoins;
    public string $toAddress;
    public string $contractAddress = '';
    public bool $isConfirmed = false;
    public int $confirmations = 0;
    public int $blockId = 0;
    public ?string $tag = null;
}
