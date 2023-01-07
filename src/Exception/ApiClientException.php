<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Exception;

use GuzzleHttp\Psr7\Response;

class ApiClientException extends \Exception
{
    /**
     * @param  Response  $response
     * @return static
     */
    public static function fromResponse(Response $response): ApiClientException
    {
        return new static($response->getReasonPhrase(), $response->getStatusCode());
    }
}
