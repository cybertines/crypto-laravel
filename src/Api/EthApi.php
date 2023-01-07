<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Api;

use App\CryptoGatewayEngine\Api\Request\ApiRequester;
use App\CryptoGatewayEngine\Dto\BlockInfoDto;
use App\CryptoGatewayEngine\Dto\ParsedTokenDto;
use App\CryptoGatewayEngine\Exception\PaymentGatewayException;
use App\CryptoGatewayEngine\Entity\{BroadcastResponse, Currency, Transaction};
use App\CryptoGatewayEngine\Helper\Converter;
use Carbon\Carbon;

class EthApi extends AbstractApi
{
    protected int $requestCount = 0;
    protected string $uri;

    public function __construct(Currency $currency, ApiRequester $client, bool $testnet = false, array $config = [])
    {
        parent::__construct($currency, $client, $testnet, $config);
        $this->uri = $testnet
            ? $config['eth']['url_test'] ?? ''
            : $config['eth']['url'] ?? '';
    }

    protected function prepareRequestParams(string $command, array $params = []): array
    {
        $this->requestCount++;

        return [
            'jsonrpc' => '2.0',
            'method'  => $command,
            'params'  => $params,
            'id'      => $this->requestCount
        ];
    }

    public function gasPrice(): int
    {
        return (int)Converter::hexToDec($this->getRawGasPrice());
    }

    public function getRawGasPrice(): string
    {
        return $this->request('eth_gasPrice');
    }

    public function version(): string
    {
        return $this->request('net_version');
    }

    public function getTransactionCount(string $address, string $tag = 'latest'): string
    {
        return $this->request('eth_getTransactionCount', [$address, $tag]);
    }

    public function encode($amount): string
    {
        return '0x' . Converter::bcdechex($amount);
    }

    public function decodeTransactionResponse(array $data, array $keys = []): array
    {
        if ($keys) {
            foreach ($keys as $key) {
                $data[$key] = Converter::hexToDec($data[$key]);
            }
        } else {
            $data = array_map(fn($item) => Converter::hexToDec($item), $data);
        }


        return $data;
    }

    public function getTransactionByHash(string $hash, bool $decode = true): array
    {
        $data = $this->request('eth_getTransactionByHash', [$hash]);

        if ($data && $decode) {
            $data = $this->decodeTransactionResponse($data, ['blockNumber', 'nonce', 'v', 'gas', 'transactionIndex', 'value', 'gasPrice']);
        }

        return (array)$data;
    }

    public function getBlockByHash(string $hash): array
    {
        return (array)$this->request('eth_getBlockByHash', [$hash, true]);
    }

    public function getBlockById(int $blockId): array
    {
        return (array)$this->request('eth_getBlockByNumber', [Converter::decToHex((string)$blockId), true]);
    }

    public function addressBalance(string $address): float
    {
        if ($this->getCurrency()->getTokenCode()) {
            $value = Converter::hexToDec($this->getTokenBalance($address));
        } else {
            $value = Converter::hexToDec($this->getRawAddressBalance($address));
        }

        return (float)Converter::coinToValue($value, $this->getCurrency()->getDecimals());
    }

    /**
     * For tokens that adhere to the ERC20 standard, to get the token balance for a given address,
     * you need to execute the method on the token contract called balanceOf(address).
     *
     * To generate the "data" variable, for this specific contract interaction, it's going to be a 32-byte hex string,
     * in two 16-byte pieces. The first piece is the function selector for the balanceOf(address)
     * function (0x70a08231000000000000000000000000), and the second half is the address you want to look up.
     *
     * @param string $address
     * @return string
     * @throws \Exception
     */
    public function getTokenBalance(string $address): string
    {
        return $this->request('eth_call', [
            [
                'to'   => $this->getCurrency()->getContractAddress() ?? '',
                'data' => '0x70a08231000000000000000000000000' . substr($address, 2),
            ],
            'latest'
        ]);
    }

    public function getRawAddressBalance(string $address, string $block = 'latest'): string
    {
        return $this->request('eth_getBalance', [$address, $block]);
    }

    public function blockNumber(): int
    {
        return (int)Converter::hexToDec($this->request('eth_blockNumber'));
    }

    public function pushTransaction(Transaction $transaction): BroadcastResponse
    {
        $txId = $this->sendRawTransaction($transaction->getHex());

        $response = new BroadcastResponse();
        if ($txId) {
            $response->setTxId($txId)->setSuccess();
            //To prevent error with creating multiple transaction, because api could recalculate utxos immediately
            sleep(1);
        }

        return $response;
    }

    private function sendRawTransaction(string $hex): ?string
    {
        return $this->request('eth_sendRawTransaction', [$hex]);
    }

    /**
     * For tokens that adhere to the ERC20 standard, to send tokens, you need send 0 eth to contract and pass property "data"
     * where will be decoded method on the token contract called transfer(address,uint256).
     *
     * To generate the "data" variable, for this specific contract interaction, it's going to be a hex string.
     * The first piece is the function selector for the transfer(address,uint256) function (0a9059cbb),
     * then address and amount of tokens.
     *
     * For example: 0xa9059cbb00000000000000000000000027f0d8cfb5b0fd8966f2cd9aafe3e939c3aebd05000000000000000000000000000000000000000000000000002386f26fc10000
     * If we decode it via etherscan we will see:
     * Function: transfer(address to, uint256 tokens)
     *
     * MethodID: 0xa9059cbb
     * [0]:  00000000000000000000000027f0d8cfb5b0fd8966f2cd9aafe3e939c3aebd05
     * [1]:  000000000000000000000000000000000000000000000000002386f26fc10000
     *
     * If $method === 'a9059cbb' it means that it's erc-20 contact and was used method transfer
     *
     * @param string $input
     * @return ParsedTokenDto|null
     */
    public function parseToken(string $input): ?ParsedTokenDto
    {
        $method = substr($input, 2, 8);

        if ($method === 'a9059cbb') {
            return new ParsedTokenDto([
                'address' => '0x' . substr($input, 34, 40),
                'value'   => Converter::hexToDec(substr($input, -32)),
            ]);
        }

        return null;
    }

    /**
     * @param string $command
     * @param array $params
     * @return array|string|int|bool
     * @throws \Exception
     */
    protected function request(string $command, array $params = [])
    {
        $projectId = $this->config['eth']['project_id'] ?? '';
        $response = $this->client->request(
            'POST',
            $this->uri . $projectId,
            $this->prepareRequestParams($command, $params)
        )->json();

        if (isset($response['error']) && $response['error']) {
            throw new PaymentGatewayException($response['error']['message'] ?? '');
        }

        return $response['result'] ?? null;
    }

    public function isValidAddress(string $address): bool
    {
        if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $address)) {
            return false;
        }

        return true;
    }

    protected function getTransactionReceipt(string $hash): array
    {
        return $this->request('eth_getTransactionReceipt', [$hash]);
    }

    public function getTransactionFeeByHash(string $hash): float
    {
        $transaction = $this->getTransactionReceipt($hash);

        if (!isset($transaction['gasUsed'])
            || !isset($transaction['effectiveGasPrice'])
            || !isset($this->config['eth']['decimals'])) {
            throw new PaymentGatewayException('Could not get transaction fee');
        }
        $gasUsed = Converter::hexToDec($transaction['gasUsed']);
        $effectiveGasPrice = Converter::hexToDec($transaction['effectiveGasPrice']);

        return (float)Converter::coinToValue($gasUsed * $effectiveGasPrice, $this->config['eth']['decimals']);
    }

    public function getBlockIdByHash(string $hash): int
    {
        $transaction = $this->getTransactionReceipt($hash);

        if (!isset($transaction['blockNumber'])) {
            throw new PaymentGatewayException('Transaction not included at anyone block');
        }

        return (int) Converter::hexToDec($transaction['blockNumber']);
    }

    public function getCurrentBlockInfo(): BlockInfoDto
    {
        $currentBlockId = $this->blockNumber();
        if (is_null($currentBlockId)) {
            throw new PaymentGatewayException('Could not get current block id from blockchain');
        }

        return $this->getBlockInfoByBlockId($currentBlockId);
    }

    public function getBlockInfoByBlockId(int $blockId): BlockInfoDto
    {
        $blockInfo = $this->getBlockById($blockId);
        $createdAt = $blockInfo['timestamp'] ?? null;
        if (is_null($createdAt)) {
            throw new PaymentGatewayException('Could not get block info');
        }

        return new BlockInfoDto([
            'blockId' => $blockId,
            'createdAt' => Carbon::createFromTimestamp(Converter::hexToDec($createdAt))
        ]);
    }
}
