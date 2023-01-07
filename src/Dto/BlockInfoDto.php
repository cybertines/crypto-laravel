<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Dto;

use Spatie\DataTransferObject\DataTransferObject;

class BlockInfoDto extends DataTransferObject
{
    public int $blockId;
    public \DateTime $createdAt;
}
