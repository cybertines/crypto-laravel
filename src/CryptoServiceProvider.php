<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine;

use App\CryptoGatewayEngine\Api\Request\ApiRequester;
use App\CryptoGatewayEngine\Api\Request\TrxHttpProvider;
use App\CryptoGatewayEngine\Api\Request\Logger\RequestLogger;
use App\CryptoGatewayEngine\Api\TrxApi;
use App\CryptoGatewayEngine\Exception\PaymentGatewayException;
use App\CryptoGatewayEngine\Monitor\AbstractMonitor;
use App\CryptoGatewayEngine\Monitor\BlockStorage\BlockStorageContractInterface;
use App\CryptoGatewayEngine\Monitor\BlockStorage\CacheBlockStorageInterface;
use App\CryptoGatewayEngine\Entity\{Currency, CurrencyNetwork};
use App\CryptoGatewayEngine\Enum\{CryptoCurrency, TokenType};
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use IEXBase\TronAPI\Provider\HttpProviderInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class CryptoServiceProvider extends ServiceProvider
{
    //test
    public function register(): void
    {
        $this->app->bind(RequestLogger::class, static function (Application $app) {
            $logger = config('crypto.logger') ?: 'null';

            if (class_exists($logger) && in_array(RequestLogger::class, class_implements($logger))) {
                return $app->make($logger);
            }

            $availableLoggers = config('crypto.concrete.logger');

            if (in_array($logger, array_keys($availableLoggers))) {
                return $app->make($availableLoggers[$logger]);
            }

            throw new PaymentGatewayException('unknown request logger - ' . $logger);
        });

        $this->app->when(ApiRequester::class)
            ->needs(ClientInterface::class)
            ->give(fn(Application $app) => $app->make(Client::class));

        $this->app->when(AbstractMonitor::class)
            ->needs(BlockStorageContractInterface::class)
            ->give(fn(Application $app) => $app->make(CacheBlockStorageInterface::class));

        $this->app->bind(CurrencyNetwork::class, function (Application $app) {
            return new CurrencyNetwork(TokenType::DEFAULT(), 6);
        });
        $this->app->bind(Currency::class, function (Application $app) {
            return new Currency(CryptoCurrency::BTC(), $app->make(CurrencyNetwork::class));
        });

        $this->app->when(TrxApi::class)
            ->needs(HttpProviderInterface::class)
            ->give(function (Application $app) {
                return $app->make(TrxHttpProvider::class, [
                        'host' => config('crypto.trx.testnet') ?
                            config('crypto.trx.node_url_test') :
                            config('crypto.trx.node_url')
                    ]
                );
            });
    }
}
