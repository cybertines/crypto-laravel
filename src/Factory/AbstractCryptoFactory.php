<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Factory;

use App\CryptoGatewayEngine\Api\AbstractApi;
use App\CryptoGatewayEngine\Api\Request\ApiRequester;
use App\CryptoGatewayEngine\Exception\PaymentGatewayException;
use App\CryptoGatewayEngine\Monitor\AbstractMonitor;
use App\CryptoGatewayEngine\Entity\{BroadcastResponse, Currency, Transaction, Wallet};
use App\CryptoGatewayEngine\Transaction\AbstractTransaction;

abstract class AbstractCryptoFactory
{
    protected bool $testnet = false;
    protected int $confirmations = 1;
    protected Currency $currency;
    protected array $config = [];
    protected ApiRequester $apiRequester;

    public static function instanceByCurrency(Currency $currency, ApiRequester $apiRequester, array $config): AbstractCryptoFactory
    {
        $factory = $config['concrete']['factory'][$currency->getNode()] ?? null;

        if (!$factory) {
            throw new PaymentGatewayException('Not found concrete implementation for ' . $currency->getNode() . ' factory');
        }

        return new $factory($currency, $apiRequester, $config[$currency->getNode()] ?? [], $config);
    }

    /**
     * @param Currency $currency
     * @param ApiRequester $apiRequester
     * @param array $options Array containing the necessary params.
     *    $options = [
     *      'testnet'       => (bool) Is testnet network?.
     *      'confirmations' => (int) Number of confirmations, when check transactions.
     *    ]
     * @param array $config
     */
    public function __construct(Currency $currency, ApiRequester $apiRequester, array $options, array $config)
    {
        $this->currency = $currency;
        $this->configure($options);
        $this->config = $config;
        $this->apiRequester = $apiRequester;
    }

    public function createTransaction(Wallet $wallet): AbstractTransaction
    {
        return AbstractTransaction::instance($this->currency, $wallet, $this->createApi(), $this->config);
    }

    public function createApi(): AbstractApi
    {
        return AbstractApi::instance($this->currency, $this->apiRequester, $this->testnet, $this->config);
    }

    public function broadcastTransaction(Transaction $transaction): BroadcastResponse
    {
        return $this->createApi()->pushTransaction($transaction);
    }

    public function createMonitor(): AbstractMonitor
    {
        return AbstractMonitor::instance($this->currency, $this->createApi(), $this->confirmations, $this->config);
    }

    private function configure(array $data)
    {
        foreach($data as $attribute => $value) {
            if (property_exists($this, $attribute)) {
                $this->$attribute = $value;
            }
        }
    }
}
