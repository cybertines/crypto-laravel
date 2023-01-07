<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Api\Combine;

use App\CryptoGatewayEngine\Api\AbstractApi;
use App\CryptoGatewayEngine\Entity\{AddressUtxo, BroadcastResponse, Currency, Transaction, Wallet};
use App\CryptoGatewayEngine\Api\Request\ApiRequester;
use App\CryptoGatewayEngine\Dto\BlockInfoDto;
use App\CryptoGatewayEngine\Exception\PaymentGatewayException;
use App\CryptoGatewayEngine\Helper\Converter;
use Carbon\Carbon;

abstract class NownodesApi extends AbstractApi
{
    protected string $network;
    protected string $apiKey;
    protected string $baseUrl;
    protected int $requestCount = 0;

    public function __construct(Currency $currency, ApiRequester $client, bool $testnet = false, array $config = [])
    {
        parent::__construct($currency, $client, $testnet, $config);

        $this->apiKey = (string) ($config['nownodes']['api_key'] ?? '');
        $this->baseUrl = 'https://' . $this->defineNodeUrl() . '.nownodes.io/';
    }

    public function pushTransaction(Transaction $transaction): BroadcastResponse
    {
        $result = $this->sendRawTransaction($transaction->getHex());
        $txId = $result['result'] ?? null;

        $response = new BroadcastResponse();
        if ($txId) {
            $response->setTxId($txId)->setSuccess();
            //To prevent error with creating multiple transaction, because api could recalculate utxos immediately
            sleep(1);
        }

        return $response;
    }

    private function sendRawTransaction(string $hex): array
    {
        return $this->requestNode('sendrawtransaction', [$hex], false);
    }

    public function addressBalance(string $address): float
    {
        $response = $this->requestApi('address/' . $address);
        $balance = $response['balance'] ?? 0;
        $unconfirmedBalance = $response['unconfirmedBalance'] ?? 0;

        return (float)Converter::coinToValue((float)($balance + $unconfirmedBalance), $this->getCurrency()->getDecimals());
    }

    protected abstract function defineNodeUrl(): string;

    public function addressData(Wallet $wallet, bool $onlyConfirmed = true): AddressUtxo
    {
        $addressData = new AddressUtxo($wallet->getAddress());

        $data = $this->requestApi('utxo/' . $wallet->getAddress() . '?confirmed=' . $onlyConfirmed);

        if ($data) {
            foreach ($data as $out) {
                $addressData->addUtxo($out['txid'], $out['vout'], (int)$out['value'], $wallet);
            }
        }

        return $addressData;
    }

    protected function requestApi(string $method): array
    {
        $endpoint = $this->baseUrl . 'api/v2/' . $method;

        return $this->client->request('GET', $endpoint, [], ['api-key' => $this->apiKey])->json();
    }

    protected function requestNode(string $command, array $params = [], bool $parseResult = true): array
    {
        $response = $this->client->request('POST', $this->baseUrl, $this->prepareRequestParams($command, $params))
            ->json();

        if ($parseResult) {
            return $response['result'] ?? [];
        }

        return $response;
    }

    protected function prepareRequestParams(string $command, array $params = []): array
    {
        $this->requestCount++;

        return [
            'API_key' => $this->apiKey,
            'jsonrpc' => '2.0',
            'method'  => $command,
            'params'  => $params,
            'id'      => $this->requestCount
        ];
    }

    public function getBlockchainInfo(): array
    {
        return $this->requestNode('getblockchaininfo');
    }

    public function getBlockHash(int $id): string
    {
        $response = $this->requestNode('getblockhash', [$id], false);

        return $response['result'] ?? '';
    }

    public function getBlockByHash(string $hash): array
    {
        return $this->requestNode('getblock', [$hash, 2]);
    }

    public function getRawTransaction(string $hash): array
    {
        return $this->requestNode('getrawtransaction', [$hash, true]);
    }

    public function isValidAddress(string $address): bool
    {
        $result = $this->requestNode('validateaddress', [$address]);

        return (bool) ($result['isvalid'] ?? false);
    }

    /**
     * To calculate fee we need to make next steps:
     * 1. Get raw transaction
     * 2. Then for each vin input get raw transaction and sum it outputs
     * 3. Sum current transaction outputs
     * 4. Fee is result of inputs amount minus outputs amount
     * @param string $hash
     * @return float
     * @throws PaymentGatewayException
     */
    public function getTransactionFeeByHash(string $hash): float
    {
        $transaction = $this->getRawTransaction($hash);
        $totalInputAmount = 0;
        $totalOutputAmount = 0;
        if (!isset($transaction['vin']) || !isset($transaction['vout'])) {
            throw new PaymentGatewayException('Could not get transaction fee');
        }

        foreach ($transaction['vin'] as $input) {
            $inputTransaction = $this->getRawTransaction($input['txid']);
            if (!isset($inputTransaction['vout'])) {
                throw new PaymentGatewayException('Could not get transaction fee');
            }
            $voutId = $input['vout'];

            foreach ($inputTransaction['vout'] as $output) {
                if ($output['n'] === $voutId) {
                    $totalInputAmount += $output['value'];
                }
            }
        }

        foreach ($transaction['vout'] as $output) {
            $totalOutputAmount += $output['value'];
        }

        return $totalInputAmount - $totalOutputAmount;
    }

    public function getTransactionFeeByHashPredict(string $hash): float
    {
        $transaction = $this->getRawTransaction($hash);
        if (!isset($transaction['size']) || !isset($this->config['btc']['fee_satoshi_per_byte'])) {
            throw new PaymentGatewayException('Could not get transaction fee');
        }

        return (float)($transaction['size'] * $this->config['btc']['fee_satoshi_per_byte']);
    }

    public function getBlockIdByHash(string $hash): int
    {
        $transaction = $this->getRawTransaction($hash);

        if (!isset($transaction['confirmations'])) {
            throw new PaymentGatewayException('Transaction not included at anyone block');
        }

        $info = $this->getBlockchainInfo();
        $currentBlockId = $info['blocks'] ?? 0;

        if (!$currentBlockId) {
            throw new PaymentGatewayException('Could not get current block id');
        }

        return (int) ($currentBlockId - $transaction['confirmations'] + 1);
    }

    public function getCurrentBlockInfo(): BlockInfoDto
    {
        $info = $this->getBlockchainInfo();
        $currentBlockId = $info['blocks'] ?? null;
        if (is_null($currentBlockId)) {
            throw new PaymentGatewayException('Could not get current block id from blockchain');
        }

        return $this->getBlockInfoByBlockId((int) $currentBlockId);
    }

    public function getBlockInfoByBlockId(int $blockId): BlockInfoDto
    {
        $hash = $this->getBlockHash($blockId);

        $block = $this->getBlockByHash($hash);
        $createdAt = $block['time'] ?? null;
        if (is_null($createdAt)) {
            throw new PaymentGatewayException('Could not get block info');
        }

        return new BlockInfoDto([
            'blockId' => $blockId,
            'createdAt' => Carbon::createFromTimestamp($createdAt)
        ]);
    }
}
