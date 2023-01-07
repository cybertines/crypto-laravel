<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Api\Request\Logger;

use GuzzleHttp\Psr7\Response;

final class NullLogger implements RequestLogger
{
    public function log(string $method, string $uri, array $data = [], array $headers = [], ?Response $response = null, ?\Throwable $exception = null): void
    {

    }
}
