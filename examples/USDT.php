<?php
require("./vendor/autoload.php");

use GuzzleHttp\Client;
use IEXBase\TronAPI\Exception\TronException;
use Tron\Api;
use Tron\TRC20;

// $tronApi     = new Api(new Client(['base_uri' => 'https://api.trongrid.io']));         //正式链
$tronApi     = new Api(new Client(['base_uri' => 'https://api.shasta.trongrid.io']));  //测试链
$trc20Wallet = new TRC20($tronApi, [
    'contract_address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',   // USDT TRC20
    'decimals'         => 6,
]);

$from_privtekey = "0000000";                                           //波场私钥
$to_address     = "Txxxxxx";                                           //波场公钥（波场地址）
$fromAddr       = $trc20Wallet->privateKeyToAddress($from_privtekey);  //发起地址

var_dump($fromAddr->address);                          //Adress类中获取公钥
$usdt    = $trc20Wallet->balance($fromAddr->address);  //获取usdt余额
var_dump($usdt);

try {
    $transferData = $trc20Wallet->transfer($fromAddr, $to_address, 1);  //转账1usdt
} catch (TronException $e) {
    debug_print_backtrace();
    var_dump($e->getMessage(), $e->getCode());
} catch (\Exception $e) {
    debug_print_backtrace();
    var_dump($e->getMessage());
}

var_dump($transferData);
