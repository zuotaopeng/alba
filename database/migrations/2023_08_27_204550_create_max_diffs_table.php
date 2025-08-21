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
        Schema::create('max_diffs', function (Blueprint $table) {
            $table->id();
            $table->date('check_day');
            $table->string('coin',10);
            $table->string('buy_exchange',20);
            $table->string('sell_exchange',20);
            $table->double('max_diff');
            $table->dateTime('check_time');
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
        Schema::dropIfExists('max_diffs');
    }
};
