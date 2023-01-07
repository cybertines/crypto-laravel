### Api services
This component use next api services:

[Infura](https://infura.io/)
You must create account. Then create new project with product Ethereum. After that you will receive project id value.
Then paste this key to env "INFURA_PROJECT_ID". This service uses for Ethereum blockchain.

[Nownodes](https://nownodes.io/)
You just need to fill email input and press button "Get Free Api Key". Then you will receive api key on your email.
Then paste this key to env "NOWNODES_API_KEY". This service uses for Bitcoin/Litecoin blockchain.

[Trongrid](https://www.trongrid.io/)
This service uses for Tron blockchain. It is used without any api key.

[Xrp ledger](https://xrpl.org/public-servers.html#public-servers)
This service uses for Ripple blockchain. It is used without any api key.

### Mainnet/Testnet blockchain
If you want to use component in testnet you must set this env parameters:
```
BTC_TESTNET=true
LTC_TESTNET=true
ETH_TESTNET=true
XRP_TESTNET=true
TRX_TESTNET=true
```

### Mnemonic phrase
At this moment all project use only one mnemonic phase. From this phrase will be generated new wallet addresses.
Also from wallet with path index 0 will be processed all withdrawal. To set this phrase you must fill env parameter ``MNEMONIC_PHRASE``


### Test wallets
For test use can generate your own wallets or can use next one:

## TRX
[Test explorer](https://shasta.tronscan.org/)

<i>Attention: when you send trc20 token your trx balance must be greater than 0, because fee paid only in trx</i>

## ETH
[Test explorer](https://ropsten.etherscan.io/)

<i>Attention: when you send erc20 token your eth balance must be greater than 0, because fee paid only in eth</i>

##XRP
[Test explorer](https://testnet.xrpl.org/)

<i>Attention: first transaction to new address must be greater than 10 XRP, otherwise transaction will be failed,
and you even could not find address at explorer, more [details](https://xrpl.org/reserves.html) </i>

## BTC
[Test explorer](https://live.blockcypher.com/btc-testnet/)

## LTC
[Test explorer](https://blockexplorer.one/litecoin/testnet)


### How to use this component?
When you want to generate wallet, you need seed phrase (mnemonic). It is the main keys.
You can use your own seed phrase or generate new one.
To generate new seed phrase you can make next step:
```php
$service = app(WalletGenerator::class);
$mnemonic = $service->generateSeed();
```

To generate BTC crypto wallet:
```php
$service = app(WalletGenerator::class);
$mnemonic = $service->generateSeed();
        
$path = 0;
$wallet = $service->generate(new CryptoCurrencyCoin(CryptoCurrencyCoin::BTC), $mnemonic, $path);
```

From one mnemonic you can generate (2^31 - 1) addresses, just you need to put needed $path. 
More detail you can reed at [BIP-32](https://github.com/bitcoin/bips/blob/master/bip-0032.mediawiki) specification

To send ``0.0001 BTC`` from our wallet to ``n2k8dJGvQa9Z4G5gPDogKPsopbvoGoJmaR`` you can use next code, 
of course at your wallet must be sum greater than 0.0001 + network fee:
```php
$walletService = app(WalletGenerator::class);
$mnemonic = $walletService->generateSeed();

$coin = new CryptoCurrencyCoin(CryptoCurrencyCoin::BTC);
$senderWalletDto = $walletService->generate($coin, $mnemonic, 0);

$currency = new Currency(CryptoCurrency::BTC(), new CurrencyNetwork(TokenType::DEFAULT(), 8));
$senderWallet = new Wallet($senderWalletDto->address, $senderWalletDto->privateKey, $currency, $senderWalletDto->pubKey);

$crypto = AbstractCryptoFactory::instanceByCurrency($currency);
$destinationAddressDto = new DestinationAddressDto([
    'address' => 'n2k8dJGvQa9Z4G5gPDogKPsopbvoGoJmaR',
    'amount' => 0.0001
]);
$transaction = $crypto->createTransaction($senderWallet)->create($destinationAddressDto);
$result = $crypto->broadcastTransaction($transaction);
```
