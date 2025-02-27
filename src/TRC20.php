<?php

namespace Tron;

use IEXBase\TronAPI\Exception\TronException;
use Tron\Exceptions\TransactionException;
use Tron\Exceptions\TronErrorException;
use Tron\Support\Formatter;
use Tron\Support\Utils;
use InvalidArgumentException;
use GuzzleHttp\Client;

class TRC20 extends TRON
{
    protected $contractAddress;
    protected $decimals = 6;

    public function __construct(string $base_uri = '', ?string $private_key = null, string $contract_address = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t')
    {
        parent::__construct($base_uri, $private_key);

        $this->contractAddress = new Address(
            $contract_address,
            '',
            $this->tron->address2HexString($contract_address)
        );
    }

    public function balance(?string $address = null)
    {
        $address = $address ?: $this->account->address;
        $hex_addr = $this->tron->address2HexString($address);

        $body   = $this->api->post('/wallet/triggersmartcontract', [
            'contract_address'  => $this->contractAddress->hexAddress,
            'function_selector' => 'balanceOf(address)',
            'parameter'         => Formatter::toAddressFormat($hex_addr),
            'owner_address'     => $hex_addr,
        ]);

        if (isset($body->result->code)) {
            if ($body->result->code == 'CONTRACT_VALIDATE_ERROR') {
                //没有激活过合约
                return '0';
            }

            throw new TronErrorException(hex2bin($body->result->message));
        }

        try {
            $balance = Utils::toDisplayAmount(hexdec($body->constant_result[0]), $this->decimals);
        } catch (InvalidArgumentException $e) {
            throw new TronErrorException($e->getMessage());
        }

        return $balance;
    }

    public function transfer(string $to_address, float $amount, $message = null): Transaction
    {
        try {
            // $token_id = '1002000';
            $token_id = $this->contractAddress->address;
            $response = $this->tron->sendToken($to_address, $amount, $token_id, $this->account->address);
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

    // public function transfer_back(Address $from, string $to_address, float $amount, $message = null): Transaction
    // {
    //     $this->tron->setAddress($from->address);
    //     $this->tron->setPrivateKey($from->privateKey);

    //     $troncli       = new Client(['verify' => false]);
    //     $accResource   = $troncli->post($this->host . "/wallet/getaccountresource", ['form_params' => [
    //         'privateKey'        => $from->privateKey,
    //         'function_selector' => 'transfer(address,uint256)',
    //         'call_value'        => 0,
    //         'amount'            => $amount
    //     ]]);
    //     $body = $accResource->getBody()->getContents();
    //     $body = json_decode($body, true);
    //     if ($body['result']['congestion'] == 0) {
    //         throw new TransactionException($body['result']['message']);
    //     }
    //     if ($body['result']['congestion'] == 2) {
    //         $to =  new Address(
    //             $body['result']['contract'],
    //             '',
    //             $this->tron->address2HexString($body['result']['contract'])
    //         );
    //     }

    //     $toFormat = Formatter::toAddressFormat($to->hexAddress);
    //     try {
    //         $amount = Utils::toMinUnitByDecimals($amount, $this->decimals);
    //     } catch (InvalidArgumentException $e) {
    //         throw new TronErrorException($e->getMessage());
    //     }
    //     $numberFormat = Formatter::toIntegerFormat($amount);
    //     $body         = $this->api->post('/wallet/triggersmartcontract', [
    //         'contract_address'  => $this->contractAddress->hexAddress,
    //         'function_selector' => 'transfer(address,uint256)',
    //         'parameter'         => "{$toFormat}{$numberFormat}",
    //         'fee_limit'         => 100000000,
    //         'call_value'        => 0,
    //         'owner_address'     => $from->hexAddress,
    //     ], true);

    //     if (isset($body['result']['code'])) {
    //         throw new TransactionException(hex2bin($body['result']['message']));
    //     }

    //     try {
    //         $tradeobj = $this->tron->signTransaction($body['transaction']);
    //         $response = $this->tron->sendRawTransaction($tradeobj);
    //     } catch (TronException $e) {
    //         throw new TransactionException($e->getMessage(), $e->getCode());
    //     }

    //     if (isset($response['result']) && $response['result'] == true) {
    //         return new Transaction(
    //             $body['transaction']['txID'],
    //             $body['transaction']['raw_data'],
    //             'PACKING'
    //         );
    //     } else {
    //         throw new TransactionException('Transfer Fail');
    //     }
    // }
}
