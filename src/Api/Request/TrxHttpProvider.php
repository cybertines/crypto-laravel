<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Api\Request;

use IEXBase\TronAPI\Exception\{NotFoundException, TronException};
use IEXBase\TronAPI\Provider\HttpProviderInterface;
use Psr\Http\Message\StreamInterface;

class TrxHttpProvider implements HttpProviderInterface
{
    protected ApiRequester $apiRequester;
    protected string $host;
    protected string $statusPage = '/';

    public function __construct(string $host, ApiRequester $apiRequester)
    {
        $this->host = $host;
        $this->apiRequester = $apiRequester;
    }

    public function setStatusPage(string $page = '/'): void
    {
        $this->statusPage = $page;
    }

    public function isConnected(): bool
    {
        $response = $this->request($this->statusPage);

        if (array_key_exists('blockID', $response) || array_key_exists('status', $response)) {
            return true;
        }

        return false;
    }

    public function request($url, array $payload = [], string $method = 'get'): array
    {
        $method = strtoupper($method);

        if (!in_array($method, ['GET', 'POST'])) {
            throw new TronException('The method is not defined');
        }

        $request = $this->apiRequester->request($method, $this->host . $url, $payload);

        $rawResponse = $request->response();

        return $this->decodeBody($rawResponse->getBody(), $rawResponse->getStatusCode());
    }

    protected function decodeBody(StreamInterface $stream, int $status): array
    {
        $decodedBody = json_decode($stream->getContents(), true);

        if ((string)$stream === 'OK') {
            $decodedBody = ['status' => 1];
        } elseif (is_null($decodedBody) || !is_array($decodedBody)) {
            $decodedBody = [];
        }

        if ($status === 404) {
            throw new NotFoundException('Page not found');
        }

        return $decodedBody;
    }
}
