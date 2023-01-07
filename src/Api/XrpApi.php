<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Api;

use App\CryptoGatewayEngine\Api\Request\ApiRequester;
use App\CryptoGatewayEngine\Dto\BlockInfoDto;
use App\CryptoGatewayEngine\Exception\PaymentGatewayException;
use App\CryptoGatewayEngine\Entity\{BroadcastResponse, Currency, Transaction};
use App\CryptoGatewayEngine\Helper\Converter;
use Carbon\Carbon;

class XrpApi extends AbstractApi
{
    /** @see https://xrpl.org/basic-data-types.html#specifying-time */
    public const RIPPLE_TIME_EPOCH = 946684800;
    protected int $requestCount = 0;
    protected string $uri;

    public function __construct(Currency $currency, ApiRequester $client, bool $testnet = false, array $config = [])
    {
        parent::__construct($currency, $client, $testnet, $config);

        $this->uri = $testnet
            ? $config['xrp']['url_test'] ?? ''
            : $config['xrp']['url'] ?? '';
    }

    protected function request(string $method, array $params = []): array
    {
        $this->requestCount++;

        $body = [
            'id'       => $this->requestCount,
            'method'   => $method,
            'json_rpc' => '2.0',
            'params'   => $params ? [$params] : $params
        ];

        return $this->client->request('POST', $this->uri, $body)->json();
    }

    public function pushTransaction(Transaction $transaction): BroadcastResponse
    {
        $data = $this->request('submit', ['tx_blob' => $transaction->getHex()]);
        $txId = $data['result']['tx_json']['hash'] ?? null;
        $isBroadcasted = $data['result']['broadcast'] ?? false;

        $response = new BroadcastResponse();
        if ($txId && $isBroadcasted) {
            $response->setTxId($txId)->setSuccess();
        } else {
            throw new PaymentGatewayException($data['result']['engine_result_message'] ?? '');
        }

        return $response;
    }

    public function addressBalance(string $address): float
    {
        $data = $this->request('account_info', ['account' => $address]);

        return (float)Converter::coinToValue(
            (float)($data['result']['account_data']['Balance'] ?? 0),
            $this->getCurrency()->getDecimals()
        );
    }

    public function getFee(): float
    {
        $data = $this->getServerInfo();
        $loadFactor = $data['result']['info']['load_factor'] ?? 1;
        $baseFeeXrp = $data['result']['info']['validated_ledger']['base_fee_xrp'] ?? 0.00001;

        return $loadFactor * $baseFeeXrp;
    }

    public function getSequence(string $address): int
    {
        $data = $this->getAccountInfo($address);

        return $data['result']['account_data']['Sequence'] ?? 0;
    }

    public function getServerInfo(): array
    {
        return $this->request('server_info');
    }

    public function getBlockById(int $id, bool $withTransactions = true): array
    {
        return $this->request('ledger', [
            "ledger_index" => $id,
            "full"         => false,
            "accounts"     => false,
            "transactions" => $withTransactions,
            "expand"       => true,
            "owner_funds"  => false
        ]);
    }

    public function getTransactionByHash(string $hash): array
    {
        return $this->request('tx', [
            "transaction" => $hash,
            "binary"      => false
        ]);
    }

    public function getAccountInfo(string $address): array
    {
        return $this->request('account_info', [
            'account'      => $address,
            'ledger_index' => 'current'
        ]);
    }

    public function isAddressExist(string $address): bool
    {
        return (bool)$this->getSequence($address);
    }

    public function getTransactionFeeByHash(string $hash): float
    {
        $transaction = $this->getTransactionByHash($hash);
        if (!isset($transaction['result']['Fee'])) {
            throw new PaymentGatewayException('Could not get transaction fee');
        }

        return (float)Converter::coinToValue((float)$transaction['result']['Fee'], $this->getCurrencyDecimals());
    }

    public function getBlockIdByHash(string $hash): int
    {
        $transaction = $this->getTransactionByHash($hash);

        if (!isset($transaction['result']['ledger_index'])) {
            throw new PaymentGatewayException('Transaction not included at anyone block');
        }

        return (int) $transaction['result']['ledger_index'];
    }

    public function getCurrentBlockInfo(): BlockInfoDto
    {
        $currentBlockId = $this->getServerInfo()['result']['info']['validated_ledger']['seq'] ?? null;
        if (is_null($currentBlockId)) {
            throw new PaymentGatewayException('Could not get current block id from blockchain');
        }

        return new BlockInfoDto([
           'blockId' => (int) $currentBlockId,
           'createdAt' => now()->subSeconds($this->getServerInfo()['result']['info']['validated_ledger']['age'] ?? 0)
        ]);
    }

    public function getBlockInfoByBlockId(int $blockId): BlockInfoDto
    {
        $blockInfo = $this->getBlockById($blockId, false);
        $createdAt = $blockInfo['result']['ledger']['close_time'] ?? null;

        if (is_null($createdAt)) {
            throw new PaymentGatewayException('Could not get block info');
        }

        return new BlockInfoDto([
            'blockId' => $blockId,
            'createdAt' => Carbon::createFromTimestamp(self::RIPPLE_TIME_EPOCH + $createdAt)
        ]);
    }
}
