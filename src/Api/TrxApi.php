<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Api;

use App\CryptoGatewayEngine\Api\Request\ApiRequester;
use App\CryptoGatewayEngine\Api\Request\TrxHttpProvider;
use App\CryptoGatewayEngine\Dto\BlockInfoDto;
use App\CryptoGatewayEngine\Dto\ParsedTokenDto;
use App\CryptoGatewayEngine\Exception\PaymentGatewayException;
use App\CryptoGatewayEngine\Entity\{BroadcastResponse, Currency, Transaction};
use App\CryptoGatewayEngine\Helper\Converter;
use Carbon\Carbon;
use IEXBase\TronAPI\{Tron, TronAwareTrait};

class TrxApi extends AbstractApi
{
    use TronAwareTrait;

    protected Tron $tron;

    public function __construct(Currency $currency, ApiRequester $client, bool $testnet = false, array $config = [])
    {
        parent::__construct($currency, $client, $testnet, $config);

        $url = $testnet
            ? $config['trx']['node_url_test'] ?? ''
            : $config['trx']['node_url'] ?? '';
        $provider = new TrxHttpProvider($url, $client);
        $this->tron = new Tron($provider, $provider, $provider);
    }


    public function pushTransaction(Transaction $transaction): BroadcastResponse
    {
        $data = $this->tron->sendRawTransaction(json_decode($transaction->getHex(), true));
        $txId = $data['txid'] ?? null;

        $response = new BroadcastResponse();
        if ($txId) {
            $response->setTxId($txId)->setSuccess();
        }

        return $response;
    }

    public function addressBalance(string $address): float
    {
        if ($this->getCurrency()->isTrc20()) {
            return $this->getTokenBalance($address);
        }
        return $this->getTrxBalance($address);
    }

    public function getTokenBalance(string $address): float
    {
        return (float)$this->tron->contract($this->getCurrency()->getContractAddress())->balanceOf($address);
    }

    public function getTrxBalance(string $address):float
    {
        return $this->tron->getBalance($address, true);
    }

    public function generateTx(string $to, float $amount, string $from): array
    {
        $transaction = $this->tron->getTransactionBuilder()->sendTrx($to, $amount, $from);

        return $this->tron->signTransaction($transaction);
    }

    public function generateTxContract(string $to, string $amount, string $from): array
    {
        $trxLimit = 10;
        $addressBalance = $this->getTokenBalance($from);
        if ($addressBalance < $amount) {
            throw new PaymentGatewayException('Insufficient balance on crypto wallet');
        }
        $trxBalance = $this->getTrxBalance($from);
        if ($trxBalance < $trxLimit) {
            throw new PaymentGatewayException('Insufficient trx on crypto wallet');
        }
        $contract = $this->tron->contract($this->getCurrency()->getContractAddress());

        $tokenAmount = Converter::valueToCoin((float)$amount, $contract->decimals(), 0);

        $transfer = $this->tron->getTransactionBuilder()
            ->triggerSmartContract(
                $this->getAbi(),
                $this->tron->address2HexString($this->getCurrency()->getContractAddress()),
                'transfer',
                [$this->tron->address2HexString($to), $tokenAmount],
                $this->toTron($trxLimit),
                $this->tron->address2HexString($from)
            );

        return $this->tron->signTransaction($transfer);
    }

    public function setPrivateKey(string $prKey): void
    {
        $this->tron->setPrivateKey($prKey);
    }

    public function signTransaction(array $tx): array
    {
        return $this->tron->signTransaction($tx);
    }

    public function getLatestBlock(): array
    {
        return $this->tron->getLatestBlocks(10);
    }

    public function getCurrentBlock(): ?int
    {
        $data = $this->tron->getCurrentBlock();

        return $data['block_header']['raw_data']['number'] ?? null;
    }

    public function getBlockRange(int $from, int $to): array
    {
        return $this->tron->getBlockRange($from, $to);
    }

    public function getTransactionInfo(string $hash): array
    {
        return $this->tron->getTransactionInfo($hash);
    }

    public function getTransactionByHash(string $hash): array
    {
        return $this->tron->getTransaction($hash);
    }

    /**
     * For tokens that adhere to the TRC20 standard, to send tokens, you need send 0 trx to contract and pass property "data"
     * where will be decoded method on the token contract called transfer(address,uint256).
     *
     * To generate the "data" variable, for this specific contract interaction, it's going to be a string.
     * The first piece is the function selector for the transfer(address,uint256) function (a9059cbb),
     * then address and amount of tokens.
     *
     * For example: a9059cbb00000000000000000000004189879a3b645dab0ed4dfea3df4e608c88b6cb4800000000000000000000000000000000000000000000000008d8dadf544fc0000
     *
     * If $method === 'a9059cbb' it means that it's trc-20 contact and was used method transfer
     * @param string $input
     * @return ParsedTokenDto|null
     */
    public function parseToken(string $input): ?ParsedTokenDto
    {
        $method = substr($input, 0, 8);

        if ($method === 'a9059cbb') {
            return new ParsedTokenDto([
                'address' => $this->hexString2Address('41' . substr($input, 32, 40)),
                'value'   => Converter::hexToDec(substr($input, -32)),
            ]);
        }

        return null;
    }

    private function getAbi(?string $abi = null): array
    {
        if (is_null($abi)) {
            $abi = file_get_contents(__DIR__ . '/trc20.json');
        }

        return json_decode($abi, true);
    }

    public function isValidAddress(string $address): bool
    {
        return $this->tron->isAddress($address);
    }

    public function getTransactionFeeByHash(string $hash): float
    {
        $transaction = $this->getTransactionInfo($hash);
        $feeExist = isset($transaction['fee']) || isset($transaction['receipt']['net_usage']);
        if (!$feeExist || !isset($this->config['trx']['decimals'])) {
            throw new PaymentGatewayException('Could not get transaction fee');
        }
        // If trx transfer, we need to take net_usage * 10 SUN
        // @link https://developers.tron.network/docs/bandwith
        $fee = $transaction['fee'] ?? $transaction['receipt']['net_usage'] * 10;

        return (float)Converter::coinToValue((float)$fee, $this->config['trx']['decimals']);
    }

    public function getBlockIdByHash(string $hash): int
    {
        $transaction = $this->getTransactionInfo($hash);

        if (!isset($transaction['blockNumber'])) {
            throw new PaymentGatewayException('Transaction not included at anyone block');
        }

        return (int) $transaction['blockNumber'];
    }

    public function getCurrentBlockInfo(): BlockInfoDto
    {
        return $this->arrayToBlockInfoDto($this->tron->getCurrentBlock());
    }

    public function getBlockInfoByBlockId(int $blockId): BlockInfoDto
    {
        return $this->arrayToBlockInfoDto($this->tron->getBlock($blockId));
    }

    protected function arrayToBlockInfoDto(array $data): BlockInfoDto
    {
        $blockId =  $data['block_header']['raw_data']['number'] ?? null;
        $createdAt = $data['block_header']['raw_data']['timestamp'] ?? null;

        if (is_null($blockId) || is_null($createdAt)) {
            throw new PaymentGatewayException('Could not get block info');
        }

        return new BlockInfoDto([
            'blockId' => (int) $blockId,
            'createdAt' => Carbon::createFromTimestampMs($createdAt)
        ]);
    }
}
