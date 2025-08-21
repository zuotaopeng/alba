<?php

namespace App\Library;

use Illuminate\Support\Facades\Log;

class Bitget
{
    protected $api_key;
    protected $api_secret;
    protected $base_url = "https://api.bitget.com";
    protected $passphrase;

    public function __construct($api_key, $api_secret, $passphrase) {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        $this->passphrase = $passphrase;
    }

    public function post_query($path, $query) {
        $key = $this->api_key;
        $secret = $this->api_secret;
        $base_url = $this->base_url;
        $passphrase = $this->passphrase;
        $post_data = json_encode($query);
        $nonce = time() * 1000;
        $data = $nonce.'POST'.$path.$post_data;
        $sign = base64_encode(hash_hmac('sha256', $data, $secret, true));
        $headers = array();
        $headers[0] = "Content-type:application/json;";
        $headers[1] = "ACCESS-KEY:" . $key;
        $headers[2] = "ACCESS-SIGN:" . $sign;
        $headers[3] = "ACCESS-TIMESTAMP:" . $nonce;
        $headers[4] = "ACCESS-PASSPHRASE:" . $passphrase;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $base_url.$path);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $res = curl_exec($curl);
        curl_close($curl);
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

    public function get_query($path) {
        $key = $this->api_key;
        $secret = $this->api_secret;
        $base_url = $this->base_url;
        $passphrase = $this->passphrase;
        $nonce = time() * 1000;
        $data = $nonce.'GET'.$path;
        $sign = base64_encode(hash_hmac('sha256', $data, $secret, true));
        $headers = array();
        $headers[0] = "Content-type:application/json;";
        $headers[1] = "ACCESS-KEY:" . $key;
        $headers[2] = "ACCESS-SIGN:" . $sign;
        $headers[3] = "ACCESS-TIMESTAMP:" . $nonce;
        $headers[4] = "ACCESS-PASSPHRASE:" . $passphrase;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $base_url.$path);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $res = curl_exec($curl);
        curl_close($curl);
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

    public function order($symbol, $side, $amount) {
        $query = array(
            "symbol" => $symbol,
            "side" => $side,
            'orderType'=>"market",
            "quantity" => $amount,
            "force" => 'ioc',
        );
        return $this->post_query(
            "/api/spot/v1/trade/orders", $query
        );
    }

    public function get_ticker() {
        return $this->retrieveJSON($this->base_url.'/api/spot/v1/market/ticker?symbol=BTCUSDT_SPBL');
    }

    public function get_average_price($amount) {
        $averageprice = array();
        $averageprice['ask'] = 0;
        $averageprice['bid'] = 0;
        try {
            $result = $this->retrieveJSON($this->base_url."/api/spot/v1/market/depth?symbol=BTCUSDT_SPBL&type=step0");
            if(is_array($result) && array_key_exists('data',$result) && array_key_exists('asks',$result['data'])){
                $asks = $result['data']['asks'];
                $bids = $result['data']['bids'];
                $total_amount_ask = 0;
                $total_amount_bid = 0;
                $total_value_ask = 0;
                $total_value_bid = 0;
                foreach($asks as $ask){
                    $total_amount_ask = $total_amount_ask + $ask[1];
                    $total_value_ask = $total_value_ask + $ask[0]*$ask[1];
                    if($total_amount_ask >= $amount){
                        $total_value_ask = $total_value_ask - ($total_amount_ask - $amount)*$ask[0];
                        $averageprice['ask'] = round($total_value_ask/($amount),4);
                        break;
                    }
                }
                foreach($bids as $bid){
                    $total_amount_bid = $total_amount_bid + $bid[1];
                    $total_value_bid = $total_value_bid + $bid[0]*$bid[1];
                    if($total_amount_bid>= $amount){
                        $total_value_bid = $total_value_bid - ($total_amount_bid-$amount)*$bid[0];
                        $averageprice['bid'] = round($total_value_bid/($amount),4);
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::info('bitget 平均価格取得例外：'.$e->getMessage());
        }
        return $averageprice;
    }

    public function get_balance_all(){
        //currency_code
        $result = array();
        $result['btc'] = 0;
        $result['usdt'] = 0;
        $assets = $this->get_query('/api/spot/v1/account/assets');
        if(array_key_exists('data', $assets)){
            $balances = $assets['data'];
            if(!empty($balances) && is_array($balances)){
                foreach($balances as $item){
                    if($item['coinName'] == 'BTC'){
                        $result['btc'] = $item['available'];
                    }elseif($item['coinName'] == 'USDT'){
                        $result['usdt'] = $item['available'];
                    }
                }
            }
        }
        return $result;
    }
}