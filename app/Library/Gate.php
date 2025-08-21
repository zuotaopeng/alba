<?php

namespace App\Library;

use Illuminate\Support\Facades\Log;

class Gate
{
    protected $api_key;
    protected $api_secret;
    protected $base_url = "https://api.gateio.ws/api/v4";

    public function __construct($api_key, $api_secret) {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
    }

    public function post_query($path, $query) {
        $key = $this->api_key;
        $secret = $this->api_secret;
        $base_url = $this->base_url;
        $json_payload = json_encode($query);
        $hash_json_payload = hash('sha512', $json_payload);
        $queryParam = '';
        $timestamp = time();
        $signString="POST\n/api/v4$path\n$queryParam\n$hash_json_payload\n$timestamp";
        $signHash = hash_hmac('sha512', $signString, $secret);
        $headers = array();
        $headers[0] = "Content-type:application/json;";
        $headers[1] = "KEY:" . $key;
        $headers[2] = "SIGN:" . $signHash;
        $headers[3] = "Timestamp:" . $timestamp;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $base_url.$path);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_payload);
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

    public function get_query($path,$query) {
        $key = $this->api_key;
        $secret = $this->api_secret;
        $base_url = $this->base_url;
        $hash_json_payload = hash('sha512', '');
        $queryParam = $query;
        $timestamp = time();
        $signString="GET\n/api/v4$path\n$queryParam\n$hash_json_payload\n$timestamp";
        $signHash = hash_hmac('sha512', $signString, $secret);
        $headers = array();
        $headers[0] = "Content-type:application/json;";
        $headers[1] = "KEY:" . $key;
        $headers[2] = "SIGN:" . $signHash;
        $headers[3] = "Timestamp:" . $timestamp;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $base_url.$path.'?'.$query);
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
            'currency_pair' => $symbol,
            'account' => 'spot',
            'type' => 'market',
            'side' => $side,
            'amount' => $amount,
            'time_in_force' => 'ioc'
        );
        return $this->post_query(
            "/spot/orders", $query
        );
    }

    public function get_ticker() {
        return $this->retrieveJSON($this->base_url.'/spot/tickers?currency_pair=BTC_USDT');
    }

    public function get_average_price($amount) {
        $averageprice = array();
        $averageprice['ask'] = 0;
        $averageprice['bid'] = 0;
        try {
            $result = $this->retrieveJSON($this->base_url."/spot/order_book?currency_pair=BTC_USDT");
            if(is_array($result) && array_key_exists('asks',$result)){
                $asks = $result['asks'];
                $bids = $result['bids'];
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
            Log::info('Gate 平均価格取得例外：'.$e->getMessage());
        }
        return $averageprice;
    }

    public function get_balance($currency){
        //currency_code
        $available = 0;
        $balances = $this->get_query('/spot/accounts',"currency=$currency");
        if(!empty($balances) && is_array($balances)){
            foreach($balances as $item){
                $available = $item['available'];
            }
        }
        return $available;
    }

}