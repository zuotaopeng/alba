<?php

namespace App\Library;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class GmoCoin
{
    protected $api_key;
    protected $api_secret;
    protected $public_base_url = "https://api.coin.z.com/public";
    protected $private_base_url = "https://api.coin.z.com/private";

    public function __construct($api_key, $api_secret) {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
    }

    protected function post_query($path, $query) {
        $key = $this->api_key;
        $secret = $this->api_secret;
        $base_url = $this->private_base_url;
        $post_data = json_encode($query);
        ini_set('precision', 13);
        $nonce=''.microtime(true)/0.001;
        $data = $nonce.'POST'.$path.$post_data;
        $sign = hash_hmac('sha256', $data, $secret);
        $headers = array(
            'API-KEY:'.$key,
            'API-TIMESTAMP:'.$nonce,
            'API-SIGN:'.$sign
        );
        static $ch = null;
        if (is_null($ch)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        }
        curl_setopt($ch, CURLOPT_URL, $base_url.$path);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $res = curl_exec($ch);
        if ($res === false){
            return false;
        }
        $dec = json_decode($res, true);
        if (!$dec){
            return false;
        }else{
            return $dec;
        }
    }

    protected function get_query($path, $query) {
        $key = $this->api_key;
        $secret = $this->api_secret;
        $base_url = $this->private_base_url;
        ini_set('precision', 13);
        $nonce=''.microtime(true)/0.001;
        $message = $nonce.'GET'.$path;
        $sign = hash_hmac('sha256', $message, $secret);
        $headers = array(
            'API-KEY:'.$key,
            'API-TIMESTAMP:'.$nonce,
            'API-SIGN:'.$sign
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $base_url.$path.$query);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        if ($res === false){
            return false;
        }
        $dec = json_decode($res, true);
        if (!$dec){
            return false;
        }else{
            return $dec;
        }
    }

    protected function retrieveJSON($URL) {
        $opts = array(
            'http' => array(
                'method'  => 'GET',
                'timeout' => 10
            ),
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            )
        );
        $context = stream_context_create($opts);
        $feed = file_get_contents($URL, false, $context);
        return json_decode($feed, true);
    }

    public function order($pair, $side, $amount) {
        $query = array(
            "symbol" => $pair,
            "side" => $side,
            "executionType" => "MARKET",
            "size" => $amount
        );
        return $this->post_query(
            "/v1/order", $query
        );
    }

    public function get_assets(){
        return $this->get_query('/v1/account/assets','');
    }

    public function get_order($orderid){
        return $this->get_query('/v1/orders', '?orderId='.$orderid);
    }

    public function get_executions($orderid){
        return $this->get_query('/v1/executions', '?orderId='.$orderid);
    }

    public function get_all_balance(): array
    {
        //currency_code:BTC,JPY
        $result = array();
        $result['JPY'] = 0;
        $result['BTC'] = 0;
        $result['ETH'] = 0;
        $result['XRP'] = 0;
        $assets = $this->get_assets();
        if(array_key_exists('data',$assets)){
            foreach($assets['data'] as $item){
                if($item['symbol'] == 'JPY'){
                    $result['JPY'] = $item['available'];
                } elseif ($item['symbol'] == 'BTC'){
                    $result['BTC'] = $item['available'];
                } elseif ($item['symbol'] == 'ETH'){
                    $result['ETH'] = $item['available'];
                } elseif ($item['symbol'] == 'XRP'){
                    $result['XRP'] = $item['available'];
                }
            }
        }
        return $result;
    }

    public function get_ticker(): array
    {
        $result = array();
        $res = $this->retrieveJSON($this->public_base_url.'/v1/ticker');
        if(array_key_exists('data',$res)){
            $data = $res['data'];
            if($data && is_array($data)){
                foreach($data as $item){
                    if($item['symbol'] == 'BTC'){
                        $result['btc'] = $item;
                    }elseif ($item['symbol'] == 'ETH'){
                        $result['eth'] = $item;
                    }elseif ($item['symbol'] == 'XRP'){
                        $result['xrp'] = $item;
                    }elseif ($item['symbol'] == 'LTC'){
                        $result['ltc'] = $item;
                    }elseif ($item['symbol'] == 'BCH'){
                        $result['bch'] = $item;
                    }
                    if(array_key_exists('btc', $result) &&
                        array_key_exists('xrp', $result) &&
                        array_key_exists('eth', $result) &&
                        array_key_exists('ltc', $result) &&
                        array_key_exists('bch', $result)){
                        break;
                    }
                }
            }

        }
        return $result;
    }

    //BTC
    public function get_average_price($pair, $amount): array
    {
        $depth = $this->retrieveJSON($this->public_base_url.'/v1/orderbooks?symbol='.$pair);
        $total_amount_ask = 0;
        $total_amount_bid = 0;
        $total_value_ask = 0;
        $total_value_bid = 0;
        $result = array();
        if(is_array($depth) && array_key_exists('data',$depth)){
            $ask_depth = $depth['data']['asks'];
            $bid_depth = $depth['data']['bids'];
            foreach($ask_depth as $id => $item){
                $total_amount_ask = $total_amount_ask + $item['size'];
                $total_value_ask = $total_value_ask + $item['price']*$item['size'];
                if($total_amount_ask >= $amount){
                    $total_value_ask = $total_value_ask - ($total_amount_ask - $amount)*$item['price'];
                    $result['ask'] = round($total_value_ask/($amount),4);
                    break;
                }
            }
            foreach($bid_depth as $id => $item){
                $total_amount_bid = $total_amount_bid + $item['size'];
                $total_value_bid = $total_value_bid + $item['price']*$item['size'];
                if($total_amount_bid>= $amount){
                    $total_value_bid = $total_value_bid - ($total_amount_bid-$amount)*$item['price'];
                    $result['bid'] = round($total_value_bid/($amount),4);
                    break;
                }
            }
        }
        return $result;
    }

    public function get_balance($currency_code){
        //currency_code:BTC,JPY
        $value = 0;
        $assets = $this->get_assets();
        if(array_key_exists('data',$assets)){
            foreach($assets['data'] as $item){
                if($item['symbol'] == $currency_code){
                    $value = $item['available'];
                    break;
                }
            }
        }
        return $value;
    }



}