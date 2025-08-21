<?php

namespace App\Library;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class Bitbank
{
    protected $api_key;
    protected $api_secret;
    protected $public_base_url = "https://public.bitbank.cc";
    protected $private_base_url = "https://api.bitbank.cc/v1";

    public function __construct($api_key, $api_secret) {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
    }

    protected function post_query($path, $query) {
        $key = $this->api_key;
        $secret = $this->api_secret;
        $base_url = $this->private_base_url;
        $post_data = json_encode($query);
        ini_set('precision', 16);
        $nonce=''.microtime(true)/0.000001;
        $data = $nonce.$post_data;
        $sign = hash_hmac('sha256', $data, $secret);
        $headers = array(
            'ACCESS-NONCE:'.$nonce,
            'Content-Type:application/json; charset=utf-8',
            'ACCESS-KEY:'.$key,
            'ACCESS-SIGNATURE:'.$sign,
            'Content-Length: ' . strlen($post_data)
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
        if ($res === false) throw new Exception('Curl error: '.curl_error($ch));
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
        $data = '/v1'.$path.$query;
        ini_set('precision', 16);
        $nonce=''.microtime(true)/0.000001;
        $message = $nonce.$data;
        $sign = hash_hmac('sha256', $message, $secret);
        $headers = array(
            'ACCESS-NONCE:'.$nonce,
            'Content-Type:application/json; charset=utf-8',
            'ACCESS-KEY:'.$key,
            'ACCESS-SIGNATURE:'.$sign
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $base_url.$path.$query);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        if ($res === false) throw new Exception('Curl error: '.curl_error($ch));
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
            "pair" => $pair,
            "amount" => $amount,
            "price" => 0,
            "side" => $side,
            "type" => "market"
        );
        return $this->post_query(
            "/user/spot/order", $query
        );
    }

    public function get_assets(){
        $assets = $this->get_query('/user/assets','');
        return $assets['data'];
    }

    public function get_order($orderid){
        return $this->get_query('/user/spot/order', '?pair=btc_jpy&order_id='.$orderid);
    }

    public function get_eth_order($orderid){
        return $this->get_query('/user/spot/order', '?pair=eth_jpy&order_id='.$orderid);
    }

    public function get_xrp_order($orderid){
        return $this->get_query('/user/spot/order', '?pair=xrp_jpy&order_id='.$orderid);
    }

    public function getStatus(): array
    {
        $result  = array();
        $response = $this->retrieveJSON('https://api.bitbank.cc/v1/spot/status');
        if(array_key_exists('data',$response) && array_key_exists('statuses',$response['data'])){
            $statuses = $response['data']['statuses'];
            foreach($statuses as $status){
                if($status['pair'] == 'btc_jpy'){
                    $result['btc'] = $status['status'];
                }elseif($status['pair'] == 'xrp_jpy'){
                    $result['xrp'] = $status['status'];
                }
                if(array_key_exists('btc',$result) && array_key_exists('xrp',$result)){
                    break;
                }
            }
        }
        return $result;
    }

    public function get_balance_all(): array
    {
        //currency_code:btc,jpy
        $result = array();
        $result['btc'] = 0;
        $result['eth'] = 0;
        $result['jpy'] = 0;
        $result['xrp'] = 0;
        $assets = $this->get_assets();
        if(array_key_exists('assets', $assets)){
            $balances = $assets['assets'];
            foreach($balances as $idx => $item){
                if($item['asset'] == 'btc'){
                    $result['btc'] = $item['free_amount'];
                }elseif($item['asset'] == 'eth'){
                    $result['eth'] = $item['free_amount'];
                }elseif($item['asset'] == 'jpy'){
                    $result['jpy'] = $item['free_amount'];
                }elseif($item['asset'] == 'xrp'){
                    $result['xrp'] = $item['free_amount'];
                }
            }
        }
        else if(array_key_exists('code', $assets)){
            if($assets['code'] == 20001){
                $result['btc'] = -1;
                $result['eth'] = -1;
                $result['jpy'] = -1;
                $result['xrp'] = -1;
            }else if($assets['code'] == 70001 || $assets['code'] == 70002 || $assets['code'] == 70003 || $assets['code'] == 10001){
                $result['btc'] = -2;
                $result['eth'] = -2;
                $result['jpy'] = -2;
                $result['xrp'] = -2;
            }else{
                $result['btc'] = -3;
                $result['eth'] = -3;
                $result['jpy'] = -3;
                $result['xrp'] = -3;
            }
        }
        return $result;
    }

    public function get_ticker($pair) {
        return $this->retrieveJSON($this->public_base_url.'/'.$pair.'/ticker');
    }

    public function get_average_price($pair, $amount): array
    {
        $depth = $this->retrieveJSON($this->public_base_url.'/'.$pair.'/depth');
        $total_amount_ask = 0;
        $total_amount_bid = 0;
        $total_value_ask = 0;
        $total_value_bid = 0;
        $result = array();
        if(is_array($depth) && array_key_exists('data',$depth)){
            $ask_depth = $depth['data']['asks'];
            $bid_depth = $depth['data']['bids'];
            foreach($ask_depth as $id => $item){
                $total_amount_ask = $total_amount_ask + $item[1];
                $total_value_ask = $total_value_ask + $item[0]*$item[1];
                if($total_amount_ask >= $amount){
                    $total_value_ask = $total_value_ask - ($total_amount_ask - $amount)*$item[0];
                    $result['ask'] = round($total_value_ask/($amount),4);
                    break;
                }
            }
            foreach($bid_depth as $id => $item){
                $total_amount_bid = $total_amount_bid + $item[1];
                $total_value_bid = $total_value_bid + $item[0]*$item[1];
                if($total_amount_bid>= $amount){
                    $total_value_bid = $total_value_bid - ($total_amount_bid-$amount)*$item[0];
                    $result['bid'] = round($total_value_bid/($amount),4);
                    break;
                }
            }
        }
        return $result;
    }

    public function get_balance($currency_code){
        //currency_code:btc,jpy
        $value = 0;
        $assets = $this->get_assets();
        if(array_key_exists('assets', $assets)){
            $balances = $assets['assets'];
            foreach($balances as $item){
                if($item['asset'] == $currency_code){
                    $value = $item['free_amount'];
                    break;
                }
            }
        }
        return $value;
    }

}