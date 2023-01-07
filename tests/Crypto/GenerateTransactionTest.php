<?php
declare(strict_types=1);

namespace Tests\Unit\Crypto;

use App\CryptoGatewayEngine\Tests\TestCase;
use App\CryptoGatewayEngine\Api\{BtcApi, EthApi, TrxApi, XrpApi};
use App\CryptoGatewayEngine\Api\Request\{ApiRequester, Logger\NullLogger, TrxHttpProvider};
use App\CryptoGatewayEngine\Dto\DestinationAddressDto;
use App\CryptoGatewayEngine\Factory\{BtcFactory, EthFactory, TrxFactory, XrpFactory};
use App\CryptoGatewayEngine\Transaction\{BtcTransaction, EthTransaction, TrxTransaction, XrpTransaction};
use App\CryptoGatewayEngine\Entity\{Currency, CurrencyNetwork, Wallet};
use App\CryptoGatewayEngine\Enum\{CryptoCurrency, TokenType};
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Mockery;

class GenerateTransactionTest extends TestCase
{
    protected function setUp(): void
    {
        $this->markTestIncomplete();
    }

    public function test_generate_erc20_transaction()
    {
        $responseJson = <<<JSON
        {"jsonrpc":"2.0","id":1,"result":"0x1d"}
        JSON;
        $currency = new Currency(
            CryptoCurrency::USDT(),
            new CurrencyNetwork(TokenType::ERC20(), 18, '0x101848d5c5bbca18e6b4431eedf6b95e9adf82fa')
        );
        $senderWallet = new Wallet(
            '0xFdF4d699d048046B75b10E3C92a368360A02c79E',
            '0x714ec871825aac7994ec0d6440cd3c634cbf15e405991dc729a4651959291016',
            $currency
        );
        Cache::set('eth_gasPrice', '0x59682f08', 30);
        $customApiMock = Mockery::mock(EthFactory::class);
        $customApiMock->shouldReceive('createTransaction')->once()->andReturn(
            new EthTransaction(
                $currency,
                $senderWallet,
                new EthApi(
                    $currency,
                    $this->mockApiRequesterHttpResponse($responseJson),
                    true
                )
            )
        );
        $address = new DestinationAddressDto(['address' => '0x27f0d8cfb5B0FD8966F2Cd9AAfE3e939c3aebd05', 'amount' => 0.01]);
        $transaction = $customApiMock->createTransaction($senderWallet)->create([$address]);

        self::assertEquals(
            '0xf8a91d8459682f0883012cc894101848d5c5bbca18e6b4431eedf6b95e9adf82fa80b844a9059cbb00000000000000000000000027f0d8cfb5B0FD8966F2Cd9AAfE3e939c3aebd05000000000000000000000000000000000000000000000000002386f26fc1000029a01260f4cba5e7a7bef744aa01ed2ae89d871b9639691ab2fc9937cdf4bcf1f8b8a02ca4f7ff10d663c520782ec8c6669a0a6f732590ecd58a590928b4643899021d',
            $transaction->getHex()
        );
    }

    public function test_generate_eth_transaction()
    {
        $responseJson = <<<JSON
        {"jsonrpc":"2.0","id":1,"result":"0x1d"}
        JSON;
        $currency = new Currency(CryptoCurrency::ETH(), new CurrencyNetwork(TokenType::DEFAULT(), 18));
        $senderWallet = new Wallet(
            '0xFdF4d699d048046B75b10E3C92a368360A02c79E',
            '0x714ec871825aac7994ec0d6440cd3c634cbf15e405991dc729a4651959291016',
            $currency
        );
        Cache::set('eth_gasPrice', '0x59682f08', 30);
        $customApiMock = Mockery::mock(EthFactory::class);
        $customApiMock->shouldReceive('createTransaction')->once()->andReturn(
            new EthTransaction(
                $currency,
                $senderWallet,
                new EthApi(
                    $currency,
                    $this->mockApiRequesterHttpResponse($responseJson),
                    true
                )
            )
        );
        $address = new DestinationAddressDto(['address' => '0x27f0d8cfb5B0FD8966F2Cd9AAfE3e939c3aebd05', 'amount' => 0.01]);
        $transaction = $customApiMock->createTransaction($senderWallet)->create([$address]);

        self::assertEquals(
            '0xf86a1d8459682f088252089427f0d8cfb5B0FD8966F2Cd9AAfE3e939c3aebd05872386f26fc100008029a079d530a3e0b0cdc05276af8310a0a3fa3b7a6e187c792630f60e887e9e06dd5da0393fe668e394d2dcf8d9e70b669e919cdf455bcbb4fa28fd902232851a2e893d',
            $transaction->getHex()
        );
    }

    public function test_generate_trc20_transaction()
    {
        $responseJson1 = <<<JSON
        {
            "result":{
                "result":true
            },
            "energy_used":365,
            "constant_result":["0000000000000000000000000000000000000000000000000000000000000012"],
            "transaction":{
                "ret":[[]],
                "visible":false,
                "txID":"d403ae82a66ea749fa2070be50474a8d0d67beadbf39488d66e62a538e8e88c8",
                "raw_data":{
                    "contract":[
                        {
                            "parameter":{
                                "value":{
                                    "data":"313ce567",
                                    "owner_address":"410000000000000000000000000000000000000000",
                                    "contract_address":"4156a12a1c915a9180b212823a36ae5bf40ad6261d"
                                },
                                "type_url":"type.googleapis.com/protocol.TriggerSmartContract"
                            },
                            "type":"TriggerSmartContract"
                        }
                    ],
                    "ref_block_bytes":"1f18",
                    "ref_block_hash":"a5b4b010af3019e8",
                    "expiration":1637825478000,
                    "timestamp":1637825418498
                },
                "raw_data_hex":"0a021f182208a5b4b010af3019e840f0fa85b0d52f5a6d081f12690a31747970652e676f6f676c65617069732e636f6d2f70726f746f636f6c2e54726967676572536d617274436f6e747261637412340a1541000000000000000000000000000000000000000012154156a12a1c915a9180b212823a36ae5bf40ad6261d2204313ce5677082aa82b0d52f"
            }
        }
        JSON;
        $responseJson2 = <<<JSON
        {
            "result":{
                "result":true
            },
            "transaction":{
                "visible":false,
                "txID":"9b426ec2c8238ba717889ba4a2ccc206a471b3ba41347dc7d3c03c98840887b6",
                "raw_data":{
                    "contract":[
                        {
                            "parameter":{
                                "value":{
                                    "data":"a9059cbb00000000000000000000004189879a3b645dab0ed4dfea3df4e608c88b6cb4800000000000000000000000000000000000000000000000008d8dadf544fc0000",
                                    "owner_address":"4104c8363b645e35234a137e41be9121f565edb3e4",
                                    "contract_address":"4156a12a1c915a9180b212823a36ae5bf40ad6261d"
                                },
                                "type_url":"type.googleapis.com/protocol.TriggerSmartContract"
                            },
                            "type":"TriggerSmartContract"
                        }
                    ],
                    "ref_block_bytes":"1f18",
                    "ref_block_hash":"a5b4b010af3019e8",
                    "expiration":1637825478000,
                    "fee_limit":10000000,
                    "timestamp":1637825418604
                },
                "raw_data_hex":"0a021f182208a5b4b010af3019e840f0fa85b0d52f5aae01081f12a9010a31747970652e676f6f676c65617069732e636f6d2f70726f746f636f6c2e54726967676572536d617274436f6e747261637412740a154104c8363b645e35234a137e41be9121f565edb3e412154156a12a1c915a9180b212823a36ae5bf40ad6261d2244a9059cbb00000000000000000000004189879a3b645dab0ed4dfea3df4e608c88b6cb4800000000000000000000000000000000000000000000000008d8dadf544fc000070ecaa82b0d52f900180ade204"
            }
        }
        JSON;
        $currency = new Currency(
            CryptoCurrency::USDT(),
            new CurrencyNetwork(TokenType::TRC20(), 18, 'THsGBTkgXC63oQnKtMznu4EdyrvCUH5XLQ')
        );
        $wallet = new Wallet(
            'TAQVYnindPV9hNBdAucpF365kFRTbWciwt',
            '630662c5169ab3a5f17f632fe682904815126e54c0772f90293f9e8e46ee8fb4',
            $currency
        );
        $clientInterfaceMock = $this->createMock(ClientInterface::class);
        $nodeUrl = 'https://api.shasta.trongrid.io/';

        $clientInterfaceMock
            ->method('request')
            ->will(
                $this->returnCallback(function ($arg, $link) use ($responseJson2, $responseJson1, $nodeUrl) {
                    if ($link === $nodeUrl . 'wallet/triggerconstantcontract') {
                        return $this->createResponse($responseJson1);
                    } elseif ($link === $nodeUrl . 'wallet/triggersmartcontract') {
                        return $this->createResponse($responseJson2);
                    }
                    throw new \Exception('Called unexpected link: ' . $link);
                })
            );

        $client = new ApiRequester($clientInterfaceMock, new NullLogger());
        $customApiMock = Mockery::mock(TrxFactory::class);
        $customApiMock->shouldReceive('createTransaction')->andReturn(
            new TrxTransaction(
                $currency,
                $wallet,
                new TrxApi($currency, new TrxHttpProvider($nodeUrl, $client), true)
            )
        );
        $address = new DestinationAddressDto(['address' => 'TNWQ2AVXzy9MZv336ABtFcEZm49iS56Csq', 'amount' => 10.2]);
        $transaction = $customApiMock->createTransaction($wallet)->create([$address]);
        self::assertEquals(
            '0a021f182208a5b4b010af3019e840f0fa85b0d52f5aae01081f12a9010a31747970652e676f6f676c65617069732e636f6d2f70726f746f636f6c2e54726967676572536d617274436f6e747261637412740a154104c8363b645e35234a137e41be9121f565edb3e412154156a12a1c915a9180b212823a36ae5bf40ad6261d2244a9059cbb00000000000000000000004189879a3b645dab0ed4dfea3df4e608c88b6cb4800000000000000000000000000000000000000000000000008d8dadf544fc000070ecaa82b0d52f900180ade204',
            json_decode($transaction->getHex())->raw_data_hex
        );
        self::assertEquals('9b426ec2c8238ba717889ba4a2ccc206a471b3ba41347dc7d3c03c98840887b6', $transaction->getId());
    }

    public function test_generate_btc_transaction()
    {
        $responseJson1 = <<<JSON
        {"result":{"isvalid":true,"address":"n2k8dJGvQa9Z4G5gPDogKPsopbvoGoJmaR","scriptPubKey":"76a914e8d8ac6bb0f0eabbd56e8212103797bdc5de7fab88ac","isscript":false,"iswitness":false},"error":null,"id":1}
        JSON;

        $linkUrl = 'https://btc-testnet.nownodes.io/api/v2/utxo/n4Z1y3Sgy3GhGoKMa3RVSjUA9Nj7hroAiK?confirmed=true';
        $responseJson2 = <<<JSON
            [
                {
                    "txid":"d1c38d9d22f3962ff7e29351ab1d8184b0ad5d078ec85907b72afbb2b532873b",
                    "vout":1,
                    "value":"1535324",
                    "height":2105201,
                    "confirmations":5
                },
                {
                    "txid":"4e2c9e90d5eddbfd6427730378e3615f7783171cebff22a7ff6c81bf65ab3b97",
                    "vout":0,
                    "value":"100000",
                    "height":2105110,
                    "confirmations":96
                },
                {
                    "txid":"243dcd6921b5feae71a05ea6b90e60d6800935b6f1e25cff9de323fde4f9eec9",
                    "vout":1,
                    "value":"79000",
                    "height":2105103,
                    "confirmations":103
                }
            ]
        JSON;
        $currency = new Currency(CryptoCurrency::BTC(), new CurrencyNetwork(TokenType::DEFAULT(), 8));
        $wallet = new Wallet(
            'n4Z1y3Sgy3GhGoKMa3RVSjUA9Nj7hroAiK',
            'cRNxTuqwhNKV9dcxBokKEFKyMQLjrgHPytxqMZcauM7xkueDc1xt',
            $currency
        );
        $clientInterfaceMock = $this->createMock(ClientInterface::class);

        $clientInterfaceMock
            ->method('request')
            ->will(
                $this->returnCallback(function ($arg, $link, $params) use ($linkUrl, $responseJson1, $responseJson2) {
                    if (Arr::get($params, 'json.method') === 'validateaddress') {
                        return $this->createResponse($responseJson1);
                    } elseif ($link === $linkUrl) {
                        return $this->createResponse($responseJson2);
                    }
                    throw new \Exception('Called unexpected method');
                })
            );
        $client = new ApiRequester($clientInterfaceMock, new NullLogger());
        $customApiMock = Mockery::mock(BtcFactory::class);
        $customApiMock->shouldReceive('createTransaction')->once()->andReturn(
            new BtcTransaction($currency, $wallet, new BtcApi($currency, $client, true)
            )
        );

        $address = new DestinationAddressDto(['address' => 'n2k8dJGvQa9Z4G5gPDogKPsopbvoGoJmaR', 'amount' => 0.0001]);
        $transaction = $customApiMock->createTransaction($wallet)->create([$address]);

        self::assertEquals(
            '01000000013b8732b5b2fb2ab70759c88e075dadb084811dab5193e2f72f96f3229d8dc3d1010000006b4830450221008c54835f092eb0976e203f92c9691e1e39b458b26fc152b63a2c40c72362ec7a02207de12cda4b76a9a965738d0c2ebcc475e6378a49613eaccb736d55e8e42746d2012102d2b93505a8aa41c34e9df3782530635387e19296c8a027c593ad0d5fe0a897e1ffffffff0210270000000000001976a914e8d8ac6bb0f0eabbd56e8212103797bdc5de7fab88aca9431700000000001976a914fcaeca558787c947ad83e36f31f2929fe1d77e0888ac00000000',
            $transaction->getHex()
        );
        self::assertEquals('386748e317d58ec9c5ab46da55a15abc86e50b251c0549cd7c479405e39e6430', $transaction->getId());
    }

    protected function mockApiRequesterHttpResponse(string $response): ApiRequester
    {
        $clientInterfaceMock = $this->createMock(ClientInterface::class);

        $clientInterfaceMock
            ->method('request')
            ->willReturn($this->createResponse($response));

        return new ApiRequester($clientInterfaceMock, new NullLogger());
    }

    protected function createResponse(string $response): Response
    {
        return new Response(200, [], $response);
    }

    public function test_generate_xrp_transaction()
    {
        $reqJson1 = <<<JSON
        {"json":{"id":1,"method":"server_info","json_rpc":"2.0","params":[]},"headers":[]}
        JSON;

        $responseJson1 = <<<JSON
        {
            "result": {
                "info":{
                    "build_version":"1.8.0-rc1",
                    "complete_ledgers":"21996156-23029265",
                    "hostid":"SET",
                    "io_latency_ms":1,
                    "jq_trans_overflow":"64",
                    "last_close":{
                        "converge_time_s":2,
                        "proposers":6
                    },
                    "load_factor":1,
                    "peer_disconnects":"137785",
                    "peer_disconnects_resources":"3975",
                    "peers":117,
                    "pubkey_node":"n9LXHjbYjz5byTrS5gf37WJj5XvdeXmdWCCJHL59wvpbXe5GT4f3",
                    "server_state":"full",
                    "server_state_duration_us":"44068292723",
                    "state_accounting":{
                        "connected":{
                            "duration_us":"236257814",
                            "transitions":2
                        },
                        "disconnected":{
                            "duration_us":"1051130",
                            "transitions":2
                        },
                        "full":{
                            "duration_us":"1171622070114",
                            "transitions":3
                        },
                        "syncing":{
                            "duration_us":"8028967",
                            "transitions":4
                        },
                        "tracking":{
                            "duration_us":"999839",
                            "transitions":4
                        }
                    },
                    "time":"2021-Nov-25 13:58:24.939974 UTC",
                    "uptime":1171868,
                    "validated_ledger":{
                        "age":1,
                        "base_fee_xrp":1.0e-5,
                        "hash":"F1AA9B14B95B3C93D41A38DF18E758AB9475036F5098BE22A09259403A573E9F",
                        "reserve_base_xrp":10,
                        "reserve_inc_xrp":2,
                        "seq":23029265
                    },
                    "validation_quorum":5
                },
                "status":"success"
            }
        }
        JSON;

        $reqJson2 = <<<JSON
        {"json":{"id":2,"method":"account_info","json_rpc":"2.0","params":[{"account":"rsDcYbcK7HUVNF34M1Z5yqs6BUP55kXiuL","ledger_index":"current"}]},"headers":[]}
        JSON;

        $responseJson2 = <<<JSON
        {
            "result":{
                "account_data":{
                    "Account":"rsDcYbcK7HUVNF34M1Z5yqs6BUP55kXiuL",
                    "Balance":"967999980",
                    "Flags":0,
                    "LedgerEntryType":"AccountRoot",
                    "OwnerCount":0,
                    "PreviousTxnID":"493503730A2130CC52437F8A1C550AEE4983EDB988F13E7D082EFC61326698F5",
                    "PreviousTxnLgrSeq":22947324,
                    "Sequence":22861433,
                    "index":"78C3E7696B85886195BA3578392161772145047DCCE32975D52DD8CD34B8C69B"
                },
                "ledger_current_index":23029266,
                "status":"success",
                "validated":false
            }
        }
        JSON;

        $reqJson3 = <<<JSON
        {"json":{"id":3,"method":"account_info","json_rpc":"2.0","params":[{"account":"r4rYJnxSh54YPRyuUgtSSQC4ws7RTqjH1D","ledger_index":"current"}]},"headers":[]}
        JSON;

        $responseJson3 = <<<JSON
        {
            "result":{
                "account_data":{
                    "Account":"r4rYJnxSh54YPRyuUgtSSQC4ws7RTqjH1D",
                    "Balance":"967999980",
                    "Flags":0,
                    "LedgerEntryType":"AccountRoot",
                    "OwnerCount":0,
                    "PreviousTxnID":"493503730A2130CC52437F8A1C550AEE4983EDB988F13E7D082EFC61326698F5",
                    "PreviousTxnLgrSeq":22947324,
                    "Sequence":22861433,
                    "index":"78C3E7696B85886195BA3578392161772145047DCCE32975D52DD8CD34B8C69B"
                },
                "ledger_current_index":23029266,
                "status":"success",
                "validated":false
            }
        }
        JSON;

        $currency = new Currency(CryptoCurrency::XRP(), new CurrencyNetwork(TokenType::DEFAULT(), 6));
        $wallet = new Wallet(
            'r4rYJnxSh54YPRyuUgtSSQC4ws7RTqjH1D',
            '1aa91e3440c0cf8d4c1f7a21da68008a5456fa5d9989f7124eabb11400b60671',
            $currency,
            '02afd5187fd437c78953c6d60ebc257efc9a24184605aa03c8225c7692f5bf74c8'
        );
        $clientInterfaceMock = $this->createMock(ClientInterface::class);

        $clientInterfaceMock
            ->method('request')
            ->will(
                $this->returnCallback(function ($arg, $link, $params) use (
                    $reqJson1,
                    $reqJson2,
                    $reqJson3,
                    $responseJson3,
                    $responseJson2,
                    $responseJson1
                ) {
                    if (json_encode($params) === $reqJson1) {
                        return $this->createResponse($responseJson1);
                    } elseif (json_encode($params) === $reqJson2) {
                        return $this->createResponse($responseJson2);
                    } elseif (json_encode($params) === $reqJson3) {
                        return $this->createResponse($responseJson3);
                    }
                    throw new \Exception('Called unexpected method');
                })
            );
        $client = new ApiRequester($clientInterfaceMock, new NullLogger());
        $factoryMock = Mockery::mock(XrpFactory::class);
        $factoryMock->shouldReceive('createTransaction')->once()->andReturn(
            new XrpTransaction($currency, $wallet, new XrpApi($currency, $client, true))
        );

        $addresses = new DestinationAddressDto(['address' => 'rsDcYbcK7HUVNF34M1Z5yqs6BUP55kXiuL', 'amount' => 21.0]);
        $transaction = $factoryMock->createTransaction($wallet)->create([$addresses]);
        self::assertEquals('B54C3DBE18909807C5CDC949BF125E55778461145D5149ACD3C0125145B9366E', $transaction->getId());
        self::assertEquals(
            '12000024015CD679614000000001406F4068400000000000000A732102AFD5187FD437C78953C6D60EBC257EFC9A24184605AA03C8225C7692F5BF74C874463044022016A4EDDDF154E1ACA46AF08F31946E77F870275F2097DAB520F2499125207E8102204A3F89CDBDEC9208A00BB43BF088A7FDE470B9F0AB3CC2BBD2E78E5278FA4E528114E674C3CD9FB75754A8AEE41D7BADF73577AF538283141852EA156D4309D42C4D6EA2AE5A208104D74AE9',
            $transaction->getHex()
        );
    }
}
