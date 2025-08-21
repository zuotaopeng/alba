<?php

namespace App\Console\Commands;

use App\Library\Common;
use Illuminate\Console\Command;
use PhitFlyer\PhitFlyerClient;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $bitflyer_accesskey = 'WYixupM9U2Cg2NvY2oyd6B';
        $bitflyer_secretkey = '59NWfH50kPGWFTxiophtMmLK1wy6WOY72mAHP9jv+6A=';

        $bitflyer = new PhitFlyerClient($bitflyer_accesskey, $bitflyer_secretkey);
        //残高を更新
        $bitflyer_balances = Common::getBitflyerAllBalance($bitflyer);
        dd($bitflyer_balances);



        return Command::SUCCESS;
    }
}
