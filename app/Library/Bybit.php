<?php

namespace App\Library;

use GuzzleHttp\Exception\GuzzleException;

class Bybit
{
    protected $api_key;
    protected $api_secret;

    protected $host = "https://api.bybit.com";

    public function __construct($api_key, $api_secret)
    {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
    }


    public function query($endpoint, $params, $method = "POST")
    {
        $api_key = $this->api_key;
        $secret_key = $this->api_secret;
        $url = $this->host;
        $curl = curl_init();
        $timestamp = time() * 1000;
        if ($method == "GET") {
            $params = http_build_query($params);
            $endpoint = $endpoint . "?" . $params;
        } else {
            $params = json_encode($params);
        }
        $params_for_signature = $timestamp . $api_key . "5000" . $params;
        $signature = hash_hmac('sha256', $params_for_signature, $secret_key);
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_HTTPHEADER => array(
                "X-BAPI-API-KEY: $api_key",
                "X-BAPI-SIGN: $signature",
                "X-BAPI-SIGN-TYPE: 2",
                "X-BAPI-TIMESTAMP: $timestamp",
                "X-BAPI-RECV-WINDOW: 5000",
                "Content-Type: application/json"
            ),
        ));
        if ($method == "GET") {
            curl_setopt($curl, CURLOPT_HTTPGET, true);
        }
        $response = curl_exec($curl);
        $dec = json_decode($response, true);
        curl_close($curl);
        if (!$dec) {
            return false;
        } else {
            return $dec;
        }
    }

    protected function retrieveJSON($URL)
    {
        $opts = array(
            'http' => array(
                'method' => 'GET',
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


    public function getTicker($symbol)
    {
        $endpoint = "/v5/market/tickers";
        $params = array(
            'category' => 'spot',
            "symbol" => $symbol
        );
        return $this->retrieveJSON($this->host . $endpoint . "?" . http_build_query($params));
    }

    public function getAveragePrice($symbol, $amount): array
    {
        $endpoint = "/v5/market/orderbook";
        $params = array(
            'category' => 'spot',
            'symbol' => $symbol,
            'limit' => 200
        );
        $orderbook = $this->retrieveJSON($this->host . $endpoint . "?" . http_build_query($params));
        $asks = $orderbook['result']['a'];
        $bids = $orderbook['result']['b'];
        $result = array();
        $total_amount_ask = 0;
        $total_amount_bid = 0;
        $total_value_ask = 0;
        $total_value_bid = 0;
        foreach ($asks as $item) {
            $total_amount_ask = $total_amount_ask + $item[1];
            $total_value_ask = $total_value_ask + $item[0] * $item[1];
            if ($total_amount_ask >= $amount) {
                $total_value_ask = $total_value_ask - ($total_amount_ask - $amount) * $item[0];
                $result['ask'] = round($total_value_ask / ($amount), 2);
                break;
            }
        }
        foreach ($bids as $item) {
            $total_amount_bid = $total_amount_bid + $item[1];
            $total_value_bid = $total_value_bid + $item[0] * $item[1];
            if ($total_amount_bid >= $amount) {
                $total_value_bid = $total_value_bid - ($total_amount_bid - $amount) * $item[0];
                $result['bid'] = round($total_value_bid / ($amount), 2);
                break;
            }
        }
        return $result;
    }


    public function getBalance(): array
    {
        $btc = 0;
        $usdt = 0;
        $result = array();
        $endpoint = "/v5/asset/transfer/query-asset-info";
        $params = array(
            'accountType' => 'SPOT'
        );
        $balances = $this->query($endpoint, $params, "GET");
        if (is_array($balances) && array_key_exists('result', $balances) &&
            is_array($balances['result']) && array_key_exists('spot', $balances['result']) &&
            is_array($balances['result']['spot']) && array_key_exists('assets', $balances['result']['spot'])) {
            $assets = $balances['result']['spot']['assets'];
            foreach ($assets as $asset) {
                if ($asset['coin'] == 'BTC') {
                    $btc = $asset['free'];
                } elseif ($asset['coin'] == 'USDT') {
                    $usdt = $asset['free'];
                }
            }
        }
        $result['btc'] = $btc;
        $result['usdt'] = $usdt;
        return $result;
    }

    public function createOrder($symbol, $side, $amount): array
    {
        //BTCUSDT BUY
        $result = array();
        try {
            $endpoint = "/v5/order/create";
            $method = "POST";
            $params = [
                "category" => "spot",
                "symbol" => $symbol,
                "side" => $side,
                "positionIdx" => 0,
                "orderType" => "Market",
                "qty" => $amount,
                "marketUnit" => "baseCoin",
                "timeInForce" => "IOC"
            ];
            $result = $this->query($endpoint, $params, $method);
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
        return $result;
    }


}