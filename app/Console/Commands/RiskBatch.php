<?php

namespace App\Console\Commands;

use App\Library\Bitbank;
use App\Library\Coincheck;
use App\Library\Common;
use App\Library\GmoCoin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhitFlyer\PhitFlyerClient;
use Symfony\Component\Console\Command\Command as CommandAlias;

class RiskBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'risk:batch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sell all coins when the risk is high';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //BTCリスクオン

        return CommandAlias::SUCCESS;
    }
}
