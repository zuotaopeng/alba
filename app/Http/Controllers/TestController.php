<?php

namespace App\Http\Controllers;

use App\Library\Gate;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function test() {
        $gateio = new Gate("90f690c5aef65e9b29de07f95f4e91f3", "00de179273ff78b3c5d3123e347636ce4479db1e82f10d9a51b7705c2ff79a13");


        var_dump($gateio->get_balance('USDT'));
    }
}
