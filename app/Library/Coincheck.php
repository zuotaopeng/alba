<?php

namespace App\Library;

use Exception;
use Illuminate\Support\Facades\Log;

class Coincheck
{
    protected $api_key;
    protected $api_secret;
    protected string $base_url = "https://coincheck.com";

    public function __construct($api_key, $api_secret) {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
    }

    protected function post_query($path, $query) {
        $key = $this->api_key;
        $secret = $this->api_secret;
        $base_url = $this->base_url;
        ini_set('precision', 16);
        $nonce=''.microtime(true)/0.000001;
        $url = $base_url . $path;
        $query_body = http_build_query($query);
        $message = $nonce . $url . $query_body;
        $sign = hash_hmac("sha256", $message, $secret);
        $headers = array(
            'ACCESS-NONCE:'.$nonce,
            'ACCESS-KEY:'.$key,
            'ACCESS-SIGNATURE:'.$sign
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $base_url . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query_body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        if ($res === false) {
            throw new Exception('Curl error: '.curl_error($ch));
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
        $base_url = $this->base_url;
        ini_set('precision', 16);
        $nonce=''.microtime(true)/0.000001;
        // クエリ文字列の構築
        $query_str = $query ? ('?' . $query) : '';
        $url = $this->base_url.$path;
        $message = $nonce.$url.http_build_query($query);
        $sign = hash_hmac("sha256", $message, $secret);
        // ヘッダー
        $headers = [
            'ACCESS-KEY: ' . $key,
            'ACCESS-NONCE: ' . $nonce,
            'ACCESS-SIGNATURE: ' . $sign
        ];

        // CURL設定
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $base_url . $path . $query_str);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET'); // ←ここが一番重要！！

        $res = curl_exec($ch);
        if ($res === false) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        return json_decode($res, true) ?: false;
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

    public function order($query) {
        return $this->post_query(
            "/api/exchange/orders", $query
        );
    }

    public function get_assets(){
        return $this->get_query('/api/accounts/balance',[]);
    }

    public function get_balance_all(): array
    {
        //currency_code:btc,jpy
        $result = array();
        $result['btc'] = 0;
        $result['eth'] = 0;
        $result['jpy'] = 0;
        $result['xrp'] = 0;
        $balances = $this->get_assets();
        if(array_key_exists('btc', $balances)){
            $result['btc'] = $balances['btc'] ?? 0;
            $result['eth'] = $balances['eth'] ?? 0;
            $result['jpy'] = $balances['jpy'] ?? 0;
            $result['xrp'] = $balances['xrp'] ?? 0;
        }
        return $result;
    }

    public function get_ticker($pair) {
        return $this->retrieveJSON($this->base_url.'/api/ticker?pair='.$pair);
    }

    public function get_average_price($amount): array
    {
        $depth = $this->retrieveJSON($this->base_url.'/api/order_books');
        $total_amount_ask = 0;
        $total_amount_bid = 0;
        $total_value_ask = 0;
        $total_value_bid = 0;
        $result = array();
        if(is_array($depth) && array_key_exists('asks',$depth)){
            $ask_depth = $depth['asks'];
            $bid_depth = $depth['bids'];
            foreach($ask_depth as $id => $item){
                $total_amount_ask = $total_amount_ask + $item[1];
                $total_value_ask = $total_value_ask + $item[0]*$item[1];
                if($total_amount_ask >= $amount){
                    $total_value_ask = $total_value_ask - ($total_amount_ask - $amount)*$item[0];
                    $result['ask'] = round($total_value_ask/($amount),2);
                    break;
                }
            }
            foreach($bid_depth as $id => $item){
                $total_amount_bid = $total_amount_bid + $item[1];
                $total_value_bid = $total_value_bid + $item[0]*$item[1];
                if($total_amount_bid>= $amount){
                    $total_value_bid = $total_value_bid - ($total_amount_bid-$amount)*$item[0];
                    $result['bid'] = round($total_value_bid/($amount),2);
                    break;
                }
            }
        }
        return $result;
    }

    public function get_balance($currency_code){
        //currency_code:btc,jpy
        $value = 0;
        $balances = $this->get_assets();
        if(array_key_exists($currency_code, $balances)){
            $value = $balances[$currency_code] ?? 0;
        }
        return $value;
    }


    public function setSignature($path, $arr = array())
    {
        ini_set('precision', 16);
        $nonce=''.microtime(true)/0.000001;
        $url = $this->base_url.$path;
        $message = $nonce.$url.http_build_query($arr);
        return hash_hmac("sha256", $message, $this->api_secret);
    }

}