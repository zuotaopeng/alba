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
        Schema::create('risk_mgt_eths', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->double('base_price')->nullable();
            $table->enum('side',['sell','short'])->nullable();
            $table->double('amount')->nullable();
            $table->string('trade_id')->nullable();
            $table->enum('status',['open','closed'])->nullable();
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
        Schema::dropIfExists('risk_mgt_eths');
    }
};
