<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Api\Request\Logger;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

final class FileLogger implements RequestLogger
{
    public function log(string $method, string $uri, array $data = [], array $headers = [], ?Response $response = null, ?\Throwable $exception = null): void
    {
        try {
            $data = [
                'method'          => $method,
                'uri'             => $uri,
                'request'         => $data,
                'request_headers' => $headers
            ];

            if ($response) {
                $responseContent = $response->getBody()->getContents();

                $responseContentArray = json_decode($responseContent, true);

                if (is_array($responseContentArray)) {
                    $data['response'] = $responseContentArray;
                } else {
                    $data['response'] = ['response_content' => $responseContent];
                }

                $data['response_headers'] = $response->getHeaders();
                $data['status_code'] = $response->getStatusCode();
            }

            if ($exception) {
                $data['exception'] = $exception->getMessage()
                    . ' in ' . $exception->getFile() . ':' . $exception->getLine()
                    . "\n"
                    . $exception->getTraceAsString();
            }
            Log::channel('crypto')->debug('API', $data);
        } catch (\Exception $exception) {
            logger('Exception from crypto logger', [$exception->getMessage()]);
        }
    }
}
