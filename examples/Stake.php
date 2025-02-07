<?php
require("./vendor/autoload.php");

use GuzzleHttp\Client;
use Tron\Api;
use Tron\Stake;

$tronApi = new Api(new Client(['base_uri' => 'https://nile.trongrid.io']));

$from_privtekey = "0000000";                                           //波场私钥
$to_address     = "Txxxxxx";                                           //波场公钥（波场地址）
//转换成Address类
$stakeWallet = new Stake($tronApi, $from_privtekey);

$stakeWallet->delegate($to_address, 1);                        //代理1trx产生的能量
$stakeWallet->undelegate($to_address, 1);                        //收回1trx产生的能量
$stakeWallet->delegate($to_address, 1, "BANDWITH");            //代理1trx产生的带宽
$stakeWallet->undelegate($to_address, 1, "BANDWITH");            //收回1trx产生的带宽
$stakeWallet->delegate($to_address, 1, "ENERGY", true, 1200);  //代理1trx产生的能量,锁定期1小时，单位为3秒
$stakeWallet->tron->getdelegatedresourceaccountindexv2($stakeWallet->tron->getAddress()['base58']);                    //获取全部已经代理的资源

//下面价格计算公式见 https://tronscan.org/#/tools/tronstation
$stakeWallet->getFrozenEnergyPrice();  //质押1trx获得的能量 例如12.369
$stakeWallet->getFrozenNetPrice();  //质押1trx获得的带宽 例如1.197