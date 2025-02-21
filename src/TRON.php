<?php

namespace Tron;

use Phactor\Key;
use GuzzleHttp\Client;
use IEXBase\TronAPI\Exception\TronException;
use IEXBase\TronAPI\Provider\HttpProvider;
use Tron\Interfaces\WalletInterface;
use Tron\Exceptions\TronErrorException;
use Tron\Exceptions\TransactionException;
use Tron\Support\Key as SupportKey;
use InvalidArgumentException;

abstract class TRON implements WalletInterface
{
    public $tron;

    protected $api;

    protected ?Address $account = null;

    public function __construct(string $base_uri = 'https://api.trongrid.io', ?string $private_key = null)
    {
        $this->api    = new Api(new Client(['base_uri' => $base_uri]));
        $fullNode     = new HttpProvider($base_uri);
        $solidityNode = new HttpProvider($base_uri);
        $eventServer  = new HttpProvider($base_uri);
        try {
            $this->tron = new \IEXBase\TronAPI\Tron($fullNode, $solidityNode, $eventServer);
        } catch (TronException $e) {
            throw new TronErrorException($e->getMessage(), $e->getCode());
        }

        if ($private_key) {
            $this->account = $this->privateKeyToAddress($private_key);
            $this->tron->setAddress($this->account->address);
            $this->tron->setPrivateKey($this->account->privateKey);
        }
    }

    public function getAccount(): ?Address
    {
        return $this->account;
    }

    /**
     * 哈希转地址
     */
    public static function hexToAddress($addressHex)
    {
        return \Tron\Support\Key::getBase58CheckAddress($addressHex);
    }

    public function generateAddress(): Address
    {
        $attempts     = 0;
        $validAddress = false;

        do {
            if ($attempts++ === 5) {
                throw new TronErrorException('Could not generate valid key');
            }

            $key = new Key([
                'private_key_hex'       => '',
                'private_key_dec'       => '',
                'public_key'            => '',
                'public_key_compressed' => '',
                'public_key_x'          => '',
                'public_key_y'          => ''
            ]);
            $keyPair       = $key->GenerateKeypair();
            $privateKeyHex = $keyPair['private_key_hex'];
            $pubKeyHex     = $keyPair['public_key'];

            //We cant use hex2bin unless the string length is even.
            if (strlen($pubKeyHex) % 2 !== 0) {
                continue;
            }

            try {
                $addressHex    = Address::ADDRESS_PREFIX . SupportKey::publicKeyToAddress($pubKeyHex);
                $addressBase58 = self::hexToAddress($addressHex);
            } catch (InvalidArgumentException $e) {
                throw new TronErrorException($e->getMessage());
            }
            $address      = new Address($addressBase58, $privateKeyHex, $addressHex);
            $validAddress = $this->validateAddress($address);
        } while (!$validAddress);

        return $address;
    }

    public function validateAddress(Address $address): bool
    {
        if (!$address->isValid()) {
            return false;
        }

        $body = $this->api->post('/wallet/validateaddress', [
            'address' => $address->address,
        ]);

        return $body->result;
    }

    public function privateKeyToAddress(string $privateKeyHex): Address
    {
        try {
            $addressHex    = Address::ADDRESS_PREFIX . SupportKey::privateKeyToAddress($privateKeyHex);
            $addressBase58 = self::hexToAddress($addressHex);
        } catch (InvalidArgumentException $e) {
            throw new TronErrorException($e->getMessage());
        }
        $address      = new Address($addressBase58, $privateKeyHex, $addressHex);
        $validAddress = $this->validateAddress($address);
        if (!$validAddress) {
            throw new TronErrorException('Invalid private key');
        }

        return $address;
    }

    public function blockNumber(): Block
    {
        try {
            $block = $this->tron->getCurrentBlock();
        } catch (TronException $e) {
            throw new TransactionException($e->getMessage(), $e->getCode());
        }
        $transactions = isset($block['transactions']) ? $block['transactions'] : [];
        return new Block($block['blockID'], $block['block_header'], $transactions);
    }

    public function blockByNumber(int $blockID): Block
    {
        try {
            $block = $this->tron->getBlockByNumber($blockID);
        } catch (TronException $e) {
            throw new TransactionException($e->getMessage(), $e->getCode());
        }

        $transactions = isset($block['transactions']) ? $block['transactions'] : [];
        return new Block($block['blockID'], $block['block_header'], $transactions);
    }

    public function transactionReceipt(string $txHash): Transaction
    {
        try {
            $detail = $this->tron->getTransaction($txHash);
        } catch (TronException $e) {
            throw new TronErrorException($e->getMessage(), $e->getCode());
        }

        return new Transaction(
            $detail['txID'],
            $detail['raw_data'],
            $detail['ret'][0]['contractRet'] ?? ''
        );
    }
}
