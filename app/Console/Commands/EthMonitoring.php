<?php

namespace App\Console\Commands;

use App\Library\Bitbank;
use App\Library\GmoCoin;
use App\Models\Ordereth;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class EthMonitoring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eth:monitoring {--delay= : Number of seconds to delay command}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ETH Arbitrage';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = DB::table('users')
            ->where('approved_eth', '=', 'yes')
            ->where('eth_threshold', '>', 0)
            ->where('eth_amount', '>', 0)
            ->where(DB::raw("(eth_gmo_auto + eth_bitbank_auto)"), '>', 1)
            ->offset((config('consts.server_id')-1) * 10)
            ->limit(10)
            ->get();
        Log::info('ETH monitoring start users cnt:'.count($users));
        foreach ($users as $user) {
            $ask_array = array();
            $bid_array = array();
        }
        Log::info('ETH monitoring end');
        return CommandAlias::SUCCESS;
    }
}