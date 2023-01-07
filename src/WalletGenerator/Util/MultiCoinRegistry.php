<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\WalletGenerator\Util;

use BitWasp\Bitcoin\Key\Deterministic\Slip132\PrefixRegistry;
use BitWasp\Bitcoin\Script\ScriptType;

class MultiCoinRegistry extends PrefixRegistry
{
    private array $keyTypeMap;

    public function __construct(array $em)
    {
        $map = [];
        $t = [];

        $data = [
            'x' => ['keyType' => [ScriptType::P2PKH], 'em' => $em['xpub'] ?? null],
            'X' => ['keyType' => [ScriptType::P2SH, ScriptType::P2PKH], 'em' => $em['xpub'] ?? null],
            'y' => ['keyType' => [ScriptType::P2SH, ScriptType::P2WKH], 'em' => $em['ypub'] ?? null],
            'Y' => ['keyType' => [ScriptType::P2SH, ScriptType::P2WSH, ScriptType::P2PKH], 'em' => $em['Ypub'] ?? null],
            'z' => ['keyType' => [ScriptType::P2WKH], 'em' => $em['zpub'] ?? null],
            'Z' => ['keyType' => [ScriptType::P2WSH, ScriptType::P2PKH], 'em' => $em['Zpub'] ?? null],
        ];

        foreach ($data as $key => $val) {
            $exrM = $val['em'];
            $t[] = $this->check($exrM) ? [[$exrM['private'], $exrM['public']], $val['keyType']] : null;
            $this->keyTypeMap[$key] = $exrM;
        }

        foreach ($t as $row) {
            if (!$row) {
                continue;
            }
            list ($prefixList, $scriptType) = $row;
            foreach ($prefixList as &$val) {
                $val = str_replace('0x', '', $val);
            }
            $type = implode('|', $scriptType);
            $map[$type] = $prefixList;
        }
        parent::__construct($map);
    }

    private function check(?array $kt): bool
    {
        return ($kt['private'] ?? null) && ($kt['public'] ?? null);
    }

    public function prefixBytesByKeyType(string $keyType): ?array
    {
        return $this->keyTypeMap[$keyType] ?? null;
    }
}

