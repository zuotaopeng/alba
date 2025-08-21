<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_xrps', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('buy_exchange',20);
            $table->double('buy_rate');
            $table->double('buy_amount');
            $table->string('sell_exchange',20);
            $table->double('sell_rate');
            $table->double('sell_amount');
            $table->dateTime('trade_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_xrps');
    }
};
