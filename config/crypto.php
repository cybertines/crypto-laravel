<?php

return [
    'btc'      => [
        'confirmations'        => env('BTC_CONFIRMATIONS', 3),
        'testnet'              => env('BTC_TESTNET', true),
        'fee_satoshi_per_byte' => env('BTC_FEE_SATOSHI_PER_BYTE', 3),
        'max_fee_satoshi'      => env('BTC_MAX_FEE_SATOSHI', 5000),
        'monitor_ttl_cache'    => env('BTC_MONITOR_TTL_CACHE', 3600),
        'custom_start_block'   => env('BTC_CUSTOM_START_BLOCK', 0),
        'decimals'             => 8,
        'only_confirmed_tx'    => env('BTC_ONLY_CONFIRMED_TX', false),
    ],
    'ltc'      => [
        'confirmations'        => env('LTC_CONFIRMATIONS', 3),
        'testnet'              => env('LTC_TESTNET', true),
        'fee_satoshi_per_byte' => env('LTC_FEE_SATOSHI_PER_BYTE', 3),
        'max_fee_satoshi'      => env('LTC_MAX_FEE_SATOSHI', 5000),
        'monitor_ttl_cache'    => env('LTC_MONITOR_TTL_CACHE', 3600),
        'custom_start_block'   => env('LTC_CUSTOM_START_BLOCK', 0),
        'decimals'             => 8,
        'only_confirmed_tx'    => env('LTC_ONLY_CONFIRMED_TX', false),
    ],
    'eth'      => [
        'confirmations'      => env('ETH_CONFIRMATIONS', 3),
        'testnet'            => env('ETH_TESTNET', true),
        'url'                => env('ETH_NODE_URL', 'https://mainnet.infura.io/v3/'),
        'url_test'           => env('ETH_NODE_URL_TEST', 'https://ropsten.infura.io/v3/'),
        'project_id'         => env('INFURA_PROJECT_ID'),
        'gas'                => env('ETH_GAS', '21000'),
        'gas_token'          => env('ETH_GAS_TOKEN', '77000'),
        'gas_cache_ttl'      => env('ETH_GAS_CACHE_TTL', 30),
        'monitor_ttl_cache'  => env('ETH_MONITOR_TTL_CACHE', 3600),
        'custom_start_block' => env('ETH_CUSTOM_START_BLOCK', 0),
        'decimals'           => 18,
    ],
    'xrp'      => [
        'confirmations'      => env('XRP_CONFIRMATIONS', 3),
        'testnet'            => env('XRP_TESTNET', true),
        'url'                => env('XRP_NODE_URL', 'https://s1.ripple.com:51234/'),
        'url_test'           => env('XRP_NODE_URL_TEST', 'https://s.altnet.rippletest.net:51234/'),
        'monitor_ttl_cache'  => env('XRP_MONITOR_TTL_CACHE', 300),
        'custom_start_block' => env('XRP_CUSTOM_START_BLOCK', 0),
        'decimals'           => 6,
    ],
    'trx'      => [
        'confirmations'      => env('TRX_CONFIRMATIONS', 3),
        'testnet'            => env('TRX_TESTNET', true),
        'node_url'           => env('TRX_NODE_URL', 'https://api.trongrid.io/'),
        'node_url_test'      => env('TRX_NODE_URL_TEST', 'https://api.shasta.trongrid.io/'),
        'monitor_ttl_cache'  => env('TRX_MONITOR_TTL_CACHE', 3600),
        'custom_start_block' => env('TRX_CUSTOM_START_BLOCK', 0),
        'decimals'           => 6,
    ],
    'concrete' => [
        'factory'     => [
            'trx' => \App\CryptoGatewayEngine\Factory\TrxFactory::class,
            'eth' => \App\CryptoGatewayEngine\Factory\EthFactory::class,
            'btc' => \App\CryptoGatewayEngine\Factory\BtcFactory::class,
            'ltc' => \App\CryptoGatewayEngine\Factory\LtcFactory::class,
            'xrp' => \App\CryptoGatewayEngine\Factory\XrpFactory::class
        ],
        'api'         => [
            'trx' => \App\CryptoGatewayEngine\Api\TrxApi::class,
            'eth' => \App\CryptoGatewayEngine\Api\EthApi::class,
            'btc' => \App\CryptoGatewayEngine\Api\BtcApi::class,
            'ltc' => \App\CryptoGatewayEngine\Api\LtcApi::class,
            'xrp' => \App\CryptoGatewayEngine\Api\XrpApi::class
        ],
        'transaction' => [
            'trx' => \App\CryptoGatewayEngine\Transaction\TrxTransaction::class,
            'eth' => \App\CryptoGatewayEngine\Transaction\EthTransaction::class,
            'btc' => \App\CryptoGatewayEngine\Transaction\BtcTransaction::class,
            'ltc' => \App\CryptoGatewayEngine\Transaction\LtcTransaction::class,
            'xrp' => \App\CryptoGatewayEngine\Transaction\XrpTransaction::class
        ],
        'logger'      => [
            'null' => \App\CryptoGatewayEngine\Api\Request\Logger\NullLogger::class,
            'file' => \App\CryptoGatewayEngine\Api\Request\Logger\FileLogger::class
        ],
        'monitor'     => [
            'trx' => \App\CryptoGatewayEngine\Monitor\TrxMonitor::class,
            'eth' => \App\CryptoGatewayEngine\Monitor\EthMonitor::class,
            'btc' => \App\CryptoGatewayEngine\Monitor\BtcMonitor::class,
            'ltc' => \App\CryptoGatewayEngine\Monitor\LtcMonitor::class,
            'xrp' => \App\CryptoGatewayEngine\Monitor\XrpMonitor::class
        ]
    ],

    'blockchair' => [
        'api_key' => env('BLOCKCHAIR_API_KEY', '')
    ],
    'nownodes'   => [
        'api_key' => env('NOWNODES_API_KEY', '')
    ],
    'logger'     => env('CRYPTO_REQUEST_LOGGER', \App\CryptoGatewayEngine\Api\Request\Logger\NullLogger::class),
    'mnemonic'   => env('MNEMONIC_PHRASE', ''),
    'contracts'  => [
        //USDT ERC20 testnet
        '0x101848D5C5bBca18E6b4431eEdF6B95E9ADF82FA' => [
            'decimals' => 18,
            'network'  => 'ERC-20',
            'currency' => 'usdt',
            'is_test'  => true
        ],
        //USDT ERC20 mainnet
        '0xdac17f958d2ee523a2206206994597c13d831ec7' => [
            'decimals' => 6,
            'network'  => 'ERC-20',
            'currency' => 'usdt',
            'is_test'  => false
        ],
        //USDT TRC20 testnet
        'THsGBTkgXC63oQnKtMznu4EdyrvCUH5XLQ'         => [
            'decimals' => 18,
            'network'  => 'TRC-20',
            'currency' => 'usdt',
            'is_test'  => true
        ],
        //USDT TRC20 mainnet
        'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'         => [
            'decimals' => 6,
            'network'  => 'TRC-20',
            'currency' => 'usdt',
            'is_test'  => false
        ],
    ]

];
