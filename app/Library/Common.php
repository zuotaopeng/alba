<?php

namespace App\Library;

use Illuminate\Support\Facades\Log;

class Common
{

    public static function getBitflyerBalance($bitflyer, $currency_code){
        //currency_code:BTC,JPY
        $value = 0;
        $balances = $bitflyer->meGetBalance();
        if($balances){
            foreach($balances as $item){
                if($item['currency_code'] == $currency_code){
                    $value = $item['available'];
                    break;
                }
            }
        }
        return $value;
    }

    public static function getBitflyerAllBalance($bitflyer): array
    {
        $result = array();
        $result['JPY'] = 0;
        $result['BTC'] = 0;
        $result['ETH'] = 0;
        $result['XRP'] = 0;
        try{
            $balances = $bitflyer->meGetBalance();
            if($balances){
                foreach($balances as $item){
                    if($item['currency_code'] == 'JPY'){
                        $result['JPY'] = $item['available'];
                    } elseif ($item['currency_code'] == 'BTC'){
                        $result['BTC'] = $item['available'];
                    } elseif ($item['currency_code'] == 'ETH'){
                        $result['ETH'] = $item['available'];
                    } elseif ($item['currency_code'] == 'XRP'){
                        $result['XRP'] = $item['available'];
                    }
                }
            }
        }catch (\Exception $e){
            Log::info('bitflyer 残高取得例外：'.$e->getMessage());
        }
        return $result;
    }

    public static function getBitflyerAveragePrice($bitflyer, $amount): array
    {
        $board = $bitflyer->getBoard('BTC_JPY');
        $ask_depth = $board['asks'];
        $bid_depth = $board['bids'];
        $total_amount_ask = 0;
        $total_amount_bid = 0;
        $total_value_ask = 0;
        $total_value_bid = 0;
        $result = array();
        foreach($ask_depth as $id => $item){
            $total_amount_ask = $total_amount_ask + $item['size'];
            $total_value_ask = $total_value_ask + $item['size']*$item['price'];
            if($total_amount_ask >= $amount){
                $total_value_ask = $total_value_ask - ($total_amount_ask - $amount)*$item['price'];
                $result['ask'] = round($total_value_ask/($amount),2);
                break;
            }
        }
        foreach($bid_depth as $id => $item){
            $total_amount_bid = $total_amount_bid + $item['size'];
            $total_value_bid = $total_value_bid + $item['size']*$item['price'];
            if($total_amount_bid>= $amount){
                $total_value_bid = $total_value_bid - ($total_amount_bid-$amount)*$item['price'];
                $result['bid'] = round($total_value_bid/($amount),2);
                break;
            }
        }
        return $result;
    }

    //Kucoin
    public static function get_kucoin_average_price($symbol, $pair, $amount) {
        $averageprice = array();
        $averageprice['ask'] = 0;
        $averageprice['bid'] = 0;
        $orderbook = $symbol->getAggregatedFullOrderBook($pair,1000);
        if(isset($orderbook) && is_array($orderbook) && array_key_exists('asks',$orderbook)){
            $ask_depth = $orderbook["asks"];
            $bid_depth = $orderbook["bids"];
            $total_amount_ask = 0;
            $total_amount_bid = 0;
            $total_value_ask = 0;
            $total_value_bid = 0;
            foreach($ask_depth as $item){
                $total_amount_ask = $total_amount_ask + $item[1];
                $total_value_ask = $total_value_ask + $item[0]*$item[1];
                if($total_amount_ask >= $amount){
                    $total_value_ask = $total_value_ask - ($total_amount_ask - $amount)*$item[0];
                    $averageprice['ask'] = round($total_value_ask/($amount),6);
                    break;
                }
            }
            foreach($bid_depth as $item){
                $total_amount_bid = $total_amount_bid + $item[1];
                $total_value_bid = $total_value_bid + $item[0]*$item[1];
                if($total_amount_bid>= $amount){
                    $total_value_bid = $total_value_bid - ($total_amount_bid-$amount)*$item[0];
                    $averageprice['bid'] = round($total_value_bid/($amount),6);
                    break;
                }
            }
        }
        return $averageprice;
    }

    public static function getKucoinBalance($kucoin,$coin){
        $balance = 0;
        try {
            $accounts = $kucoin->getList(['type' => 'trade']);
            if(count($accounts) > 0){
                foreach($accounts as $account){
                    if(is_array($account) && array_key_exists('currency',$account) &&
                        $account['currency'] == $coin){
                        $balance = $account['available'];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::info('kucoin 個別残高取得例外：'.$e->getMessage());
        }
        return $balance;
    }

    //get為替
    public static function getExchangeRate(): array
    {
        $result = array();
        $usdjpy = 0;
        $url = 'https://port-bot.com/api/rate/bad1043a16eac0ef1b58549dcf56cf61';
        $options['ssl']['verify_peer']=false;
        $options['ssl']['verify_peer_name']=false;
        $json = file_get_contents($url, false, stream_context_create($options));
        $data = json_decode($json,true);
        if(array_key_exists('usdjpy',$data) ){
            $usdjpy = $data['usdjpy'];
        }
        $result['ask'] = $usdjpy;
        $result['bid'] = $usdjpy;
        return $result;
    }


    public static function getKucoinAllBalance($kucoin){
        $balance = array();
        $balance['BTC'] = 0;
        $balance['USDT'] = 0;
        $btc_check = false;
        $usdt_check = false;
        try {
            $accounts = $kucoin->getList(['type' => 'trade']);
            if(count($accounts) > 0){
                foreach($accounts as $account){
                    if(is_array($account) && array_key_exists('currency',$account) &&
                        $account['currency'] == 'BTC'){
                        $balance['BTC'] = $account['available'];
                        $btc_check = true;
                        continue;
                    }
                    if(is_array($account) && array_key_exists('currency',$account) &&
                        $account['currency'] == 'USDT'){
                        $balance['USDT'] = $account['available'];
                        $usdt_check = true;
                    }
                    if($btc_check && $usdt_check){
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::info('kucoin 残高取得例外：'.$e->getMessage());
        }
        return $balance;
    }



    //MEXC
    public static function get_mexc_average_price($mexc, $pair, $amount) {
        $averageprice = array();
        $averageprice['ask'] = 0;
        $averageprice['bid'] = 0;
        try {
            $depth = $mexc->market()->getDepth([
                'depth' => 1000,
                'symbol' => $pair
            ]);
            if(is_array($depth) && array_key_exists('data',$depth) && array_key_exists('asks',$depth['data'])){
                $ask_depth = $depth['data']['asks'];
                $bid_depth = $depth['data']['bids'];
                $total_amount_ask = 0;
                $total_amount_bid = 0;
                $total_value_ask = 0;
                $total_value_bid = 0;
                foreach($ask_depth as $item){
                    $total_amount_ask = $total_amount_ask + $item['quantity'];
                    $total_value_ask = $total_value_ask + $item['price']*$item['quantity'];
                    if($total_amount_ask >= $amount){
                        $total_value_ask = $total_value_ask - ($total_amount_ask - $amount)*$item['price'];
                        $averageprice['ask'] = round($total_value_ask/($amount),6);
                        break;
                    }
                }
                foreach($bid_depth as $item){
                    $total_amount_bid = $total_amount_bid + $item['quantity'];
                    $total_value_bid = $total_value_bid + $item['price']*$item['quantity'];
                    if($total_amount_bid>= $amount){
                        $total_value_bid = $total_value_bid - ($total_amount_bid-$amount)*$item['price'];
                        $averageprice['bid'] = round($total_value_bid/($amount),6);
                        break;
                    }
                }
            }
        }catch (\Exception $e){
            echo $e->getMessage();
        }
        return $averageprice;
    }

    public static function get_mexc_balance($mexc,$coin){
        $balance = 0;
        try {
            $balances = $mexc->account()->getInfo();
            if(array_key_exists('data',$balances) && array_key_exists($coin,$balances['data'])){
                $balance = $balances['data'][$coin]['available'];
            }
        }catch (\Exception $e){
            print_r(json_decode($e->getMessage(),true));
        }
        return $balance;
    }

    public static function get_mexc_allbalances($mexc){
        $balances = array();
        $balances['BTC'] = 0;
        $balances['USDT'] = 0;
        try {
            $mexcbalances = $mexc->account()->getInfo();
            if(array_key_exists('data',$mexcbalances) && array_key_exists('BTC',$mexcbalances['data'])){
                $balances['BTC'] = $mexcbalances['data']['BTC']['available'];
            }
            if(array_key_exists('data',$mexcbalances) && array_key_exists('USDT',$mexcbalances['data'])){
                $balances['USDT'] = $mexcbalances['data']['USDT']['available'];
            }
        }catch (\Exception $e){
            print_r(json_decode($e->getMessage(),true));
        }
        return $balances;
    }




}