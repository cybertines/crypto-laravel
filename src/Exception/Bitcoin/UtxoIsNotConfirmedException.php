<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Exception\Bitcoin;

use App\CryptoGatewayEngine\Exception\PaymentGatewayException;

class UtxoIsNotConfirmedException extends PaymentGatewayException
{

}
