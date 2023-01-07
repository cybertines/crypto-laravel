<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Transaction;

use App\CryptoGatewayEngine\Api\AbstractApi;
use App\CryptoGatewayEngine\Dto\DestinationAddressDto;
use App\CryptoGatewayEngine\Exception\PaymentGatewayException;
use App\CryptoGatewayEngine\Entity\{Currency, Transaction, Wallet};

abstract class AbstractTransaction
{
    protected Wallet $wallet;
    protected Currency $currency;
    protected AbstractApi $api;
    protected array $config = [];

    protected int $feeSatoshi = 0;
    protected bool $extractFee = false;

    public static function instance(Currency $currency, Wallet $wallet, AbstractApi $api, array $config = []): self
    {
        $transaction = $config['concrete']['transaction'][$currency->getNode()] ?? null;
        if (!$transaction) {
            throw new PaymentGatewayException('Not found concrete implementation for ' . $currency->getNode() . ' transaction');
        }

        return new $transaction($currency, $wallet, $api, $config);
    }

    public function __construct(Currency $currency, Wallet $wallet, AbstractApi $api, array $config = [])
    {
        $this->currency = $currency;
        $this->wallet = $wallet;
        $this->api = $api;
        $this->config = $config;
    }

    /**
     * @param DestinationAddressDto[] $destinationAddresses
     * @param Wallet[] $senderWallets
     * @param bool $extractFee - could be used only if passed one destination address
     * @return Transaction
     */
    public abstract function create(
        array $destinationAddresses,
        array $senderWallets = [],
        bool $extractFee = false
    ): Transaction;
}
