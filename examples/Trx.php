<?php
require("./vendor/autoload.php");

use GuzzleHttp\Client;
use IEXBase\TronAPI\Exception\TronException;
use Tron\Api;
use Tron\TRX;

// $tronApi   = new Api(new Client(['base_uri' => 'https://api.trongrid.io']));         //正式链
$tronApi   = new Api(new Client(['base_uri' => 'https://api.shasta.trongrid.io']));  //测试链
$trxWallet = new TRX($tronApi);

$from_privtekey = "0000000";  //波场私钥
$to_address     = "Txxxxxx";  //波场公钥（波场地址）

$fromAddr = $trxWallet->privateKeyToAddress($from_privtekey);  //发起地址

var_dump($fromAddr->address);                        //Adress类中获取公钥
$trx     = $trxWallet->balance($fromAddr->address);  //获取trx余额
var_dump($trx);

try {
    $transferData = $trxWallet->transfer($fromAddr, $to_address, 1);  //转账1trx
} catch (TronException $e) {
    debug_print_backtrace();
    var_dump($e->getMessage(), $e->getCode());
} catch (\Exception $e) {
    debug_print_backtrace();
    var_dump($e->getMessage());
}
var_dump($transferData);
