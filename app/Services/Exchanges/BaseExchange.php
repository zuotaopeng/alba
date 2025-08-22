<?php
// ================================================
// File: app/Services/Exchanges/BaseExchange.php
// Shared base class for ccxt-powered exchange services
// ================================================

namespace App\Services\Exchanges;

use ccxt; // composer require ccxt/ccxt

abstract class BaseExchange
{
    protected $client;

    public function __construct(protected Credentials $creds) {}

    abstract protected function createClient();

    public function getClient()
    {
        return $this->client;
    }

    protected function client()
    {
        if ($this->client === null) {
            $this->client = $this->createClient();
        }
        return $this->client;
    }

    /**
     * Fetch ticker for a symbol (e.g., 'BTC/USCT').
     */
    public function ticker(string $symbol)
    {
        return $this->client()->fetch_ticker($symbol);
    }

    /**
     * Fetch order book (board)
     * @param int $limit default 50
     */
    public function orderBook(string $symbol, int $limit = 50)
    {
        return $this->client()->fetch_order_book($symbol, $limit);
    }

    /**
     * Compute average fill price for a given side and base-amount using the current order book.
     * Returns [avgPrice, filled, cost, details]
     *
     * @param string $side 'buy' or 'sell'
     */
    public function averageFillPrice(string $symbol, string $side, float $amount, int $depth = 200): array
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('amount must be > 0');
        }
        $book = $this->client()->fetch_order_book($symbol, $depth);
        $levels = ($side === 'buy') ? ($book['asks'] ?? []) : ($book['bids'] ?? []);
        if (empty($levels)) {
            throw new \RuntimeException('order book is empty');
        }
        $remaining = $amount;
        $notional = 0.0;
        $consumed = [];
        foreach ($levels as $lvl) {
            [$price, $qty] = $lvl; // [price, size]
            if ($qty <= 0) { continue; }
            $take = min($remaining, (float)$qty);
            $notional += $take * (float)$price;
            $consumed[] = ['price' => (float)$price, 'qty' => (float)$take];
            $remaining -= $take;
            if ($remaining <= 1e-12) break;
        }
        $filled = $amount - max(0.0, $remaining);
        if ($filled <= 0) {
            throw new \RuntimeException('insufficient liquidity for requested amount');
        }
        $avg = $notional / $filled;
        return [
            'avgPrice' => $avg,
            'filled'   => $filled,
            'cost'     => $notional,
            'details'  => $consumed,
        ];
    }

    /**
     * Place a limit buy order
     */
    public function limitBuy(string $symbol, float $amount, float $price, array $params = [])
    {
        [$amount, $price] = $this->precision($symbol, $amount, $price);
        return $this->client()->create_order($symbol, 'limit', 'buy', $amount, $price, $params);
    }

    /**
     * Place a limit sell order
     */
    public function limitSell(string $symbol, float $amount, float $price, array $params = [])
    {
        [$amount, $price] = $this->precision($symbol, $amount, $price);
        return $this->client()->create_order($symbol, 'limit', 'sell', $amount, $price, $params);
    }

    /**
     * Market buy by base amount. If an exchange requires quote-based market buys,
     * we estimate amount using current asks average and place amount-based order.
     */
    public function marketBuy(string $symbol, float $amount, array $params = [])
    {
        $amount = $this->amountPrecision($symbol, $amount);
        return $this->client()->create_order($symbol, 'market', 'buy', $amount, null, $params);
    }

    /**
     * Market sell by base amount.
     */
    public function marketSell(string $symbol, float $amount, array $params = [])
    {
        $amount = $this->amountPrecision($symbol, $amount);
        return $this->client()->create_order($symbol, 'market', 'sell', $amount, null, $params);
    }

    /**
     * Account balance
     */
    public function balance()
    {
        return $this->client()->fetch_balance();
    }

    /**
     * Fetch a single order by id
     */
    public function fetchOrder(string $id, ?string $symbol = null, array $params = [])
    {
        return $this->client()->fetch_order($id, $symbol, $params);
    }

    /**
     * Cancel order by id
     */
    public function cancelOrder(string $id, ?string $symbol = null, array $params = [])
    {
        return $this->client()->cancel_order($id, $symbol, $params);
    }

    /**
     * Fetch order history
     */
    public function fetchOrders(?string $symbol = null, ?int $since = null, int $limit = 50, array $params = [])
    {
        return $this->client()->fetch_orders($symbol, $since, $limit, $params);
    }

    // ---------- helpers ----------

    protected function precision(string $symbol, float $amount, float $price): array
    {
        $client = $this->client();
        $amount = $client->amount_to_precision($symbol, $amount);
        $price  = $client->price_to_precision($symbol, $price);
        return [ (float)$amount, (float)$price ];
    }

    public function amountPrecision(string $symbol, float $amount): float {
        $client = $this->client();
        return (float)$client->amount_to_precision($symbol, $amount);
    }

}