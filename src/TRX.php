<?php

namespace Tron;

use Phactor\Key;
use IEXBase\TronAPI\Exception\TronException;
use IEXBase\TronAPI\Tron;
use IEXBase\TronAPI\Provider\HttpProvider;
use Tron\Interfaces\WalletInterface;
use Tron\Exceptions\TronErrorException;
use Tron\Exceptions\TransactionException;
use Tron\Support\Key as SupportKey;
use InvalidArgumentException;

class TRX implements WalletInterface
{
    public $tron;

    protected $_api;
    protected $host;

    public function __construct(Api $_api)
    {
        $base_uri     = $_api->getClient()->getConfig('base_uri');
        $this->_api   = $_api;
        $this->host   = $base_uri->getScheme() . '://' . $base_uri->getHost();
        $fullNode     = new HttpProvider($this->host);
        $solidityNode = new HttpProvider($this->host);
        $eventServer  = new HttpProvider($this->host);
        try {
            $this->tron = new Tron($fullNode, $solidityNode, $eventServer);
        } catch (TronException $e) {
            throw new TronErrorException($e->getMessage(), $e->getCode());
        }
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
                $addressBase58 = SupportKey::getBase58CheckAddress($addressHex);
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

        $body = $this->_api->post('/wallet/validateaddress', [
            'address' => $address->address,
        ]);

        return $body->result;
    }

    public function privateKeyToAddress(string $privateKeyHex): Address
    {
        try {
            $addressHex    = Address::ADDRESS_PREFIX . SupportKey::privateKeyToAddress($privateKeyHex);
            $addressBase58 = SupportKey::getBase58CheckAddress($addressHex);
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

    public function balance(string $address)
    {
        return $this->tron->getBalance($address, true);
    }

    public function transfer(Address $account, string $to_address, float $amount, $message = null): Transaction
    {
        try {
            $this->tron->setAddress($account->address);
            $this->tron->setPrivateKey($account->privateKey);

            $response = $this->tron->sendTransaction($to_address, $amount, $message, $account->address);
        } catch (TronException $e) {
            throw new TransactionException($e->getMessage(), $e->getCode());
        }

        if (!isset($response['result']) || $response['result'] != true) {
            throw new TransactionException(hex2bin($response['message']));
        }

        return new Transaction(
            $response['txID'],
            $response['raw_data'],
            'PACKING'
        );
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
