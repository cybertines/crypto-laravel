<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Api\Request;

use App\CryptoGatewayEngine\Api\Request\Logger\RequestLogger;
use App\CryptoGatewayEngine\Exception\ApiClientException;
use App\CryptoGatewayEngine\Exception\PaymentGatewayException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

class ApiRequester
{
    private ClientInterface $client;
    private RequestLogger $requestLogger;

    private Response $response;

    public function __construct(ClientInterface $client, RequestLogger $requestLogger)
    {
        $this->client = $client;
        $this->requestLogger = $requestLogger;
    }

    /**
     * Make safe request (throw only ApiClientException)
     *
     * @param string $method HTTP method of request
     * @param string $endpoint API endpoint to call
     * @param array $data Data to send
     * @param array $headers Additional headers to send in request
     *
     * @return self
     *
     * @throws ApiClientException
     */
    public function request(string $method, string $endpoint, array $data = [], array $headers = []): self
    {
        try {
            $this->response = $this->sendRequest($method, $endpoint, $data, $headers);

            if ($this->response->getStatusCode() !== 200) {
                throw ApiClientException::fromResponse($this->pullResponse());
            }

            return $this;
        } catch (\Throwable $e) {
            throw new ApiClientException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Parse json from response body
     *
     * @return array Json body of the request
     *
     * @throws ApiClientException
     */
    public function json(): array
    {
        return $this->parseResponseJson($this->pullResponse());
    }

    /**
     * Retrieve raw content response
     *
     * @return string Raw response body
     *
     * @throws ApiClientException
     */
    public function content(): string
    {
        return $this->pullResponse()->getBody()->getContents();
    }

    /**
     * Get response object
     *
     * @return Response
     *
     * @throws ApiClientException
     */
    public function response(): Response
    {
        return $this->pullResponse();
    }

    /**
     * @param string $method HTTP method of request
     * @param string $uri
     * @param array $data Data to send
     * @param array $headers Additional headers to send in request
     *
     * @return Response Response object for request
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     */
    private function sendRequest(string $method, string $uri, array $data = [], array $headers = []): Response
    {
        try {

            $response = $this->client->request($method, $uri, [
                'json'    => $data,
                'headers' => $headers,
            ]);

            $this->requestLogger->log($method, $uri, $data, $headers, $response);

            $response->getBody()->rewind();

            return $response;
        } catch (RequestException $e) {
            $errorResponse = $e->getResponse();
            $this->requestLogger->log($method, $uri, $data, $headers, $errorResponse);

            return $errorResponse;
        } catch (\Throwable $e) {
            $this->requestLogger->log($method, $uri, $data, $headers, null, $e);

            throw $e;
        }
    }

    /**
     * Get response from attribute and clear it for future requests
     *
     * @return Response
     *
     * @throws ApiClientException
     */
    private function pullResponse(): Response
    {
        if (!isset($this->response)) {
            throw new ApiClientException('Unable to pull response. Make sure you perform the request before pulling the response');
        }

        $response = $this->response;

        unset($this->response);

        return $response;
    }

    /**
     * Parse json from response body into array or throw exception
     *
     * @param Response $response Response to parse json from
     *
     * @return array
     *
     * @throws ApiClientException
     */
    private function parseResponseJson(Response $response): array
    {
        try {
            $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($json)) {
                throw new PaymentGatewayException('Json response is not array');
            }

            return $json;
        } catch (\Throwable $e) {
            throw new ApiClientException('Unable to parse json from response', 0, $e);
        }
    }
}
