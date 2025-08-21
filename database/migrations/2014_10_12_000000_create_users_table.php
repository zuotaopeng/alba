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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('name');
            $table->string('phone')->nullable();
            //認証キー
            $table->string('bybit_accesskey')->nullable();
            $table->string('bybit_secretkey')->nullable();
            $table->string('bitget_accesskey')->nullable();
            $table->string('bitget_secretkey')->nullable();
            $table->string('bitget_passphrase')->nullable();
            $table->string('kucoin_accesskey')->nullable();
            $table->string('kucoin_secretkey')->nullable();
            $table->string('kucoin_passphrase')->nullable();
            $table->string('mexc_accesskey')->nullable();
            $table->string('mexc_secretkey')->nullable();
            //数量
            $table->double('btc_amount')->nullable();
            $table->double('xrp_amount')->nullable();
            $table->double('eth_amount')->nullable();
            //閾値
            $table->double('btc_threshold')->nullable();
            $table->double('xrp_threshold')->nullable();
            $table->double('eth_threshold')->nullable();
            //自動取引
            $table->tinyInteger('btc_bybit_auto')->default(0);
            $table->tinyInteger('btc_bitget_auto')->default(0);
            $table->tinyInteger('btc_kucoin_auto')->default(0);
            $table->tinyInteger('btc_mexc_auto')->default(0);
            $table->tinyInteger('eth_bybit_auto')->default(0);
            $table->tinyInteger('eth_bitget_auto')->default(0);
            $table->tinyInteger('eth_kucoin_auto')->default(0);
            $table->tinyInteger('eth_mexc_auto')->default(0);
            $table->tinyInteger('xrp_bybit_auto')->default(0);
            $table->tinyInteger('xrp_bitget_auto')->default(0);
            $table->tinyInteger('xrp_kucoin_auto')->default(0);
            $table->tinyInteger('xrp_mexc_auto')->default(0);
            //残高
            $table->string('bybit_usdt')->nullable()->comment('USDT残高');
            $table->string('bybit_btc')->nullable()->comment('BTC残高');
            $table->string('bybit_eth')->nullable()->comment('eth残高');
            $table->string('bybit_xrp')->nullable()->comment('xrp残高');
            $table->string('bitget_usdt')->nullable()->comment('USDT残高');
            $table->string('bitget_btc')->nullable()->comment('BTC残高');
            $table->string('bitget_eth')->nullable()->comment('eth残高');
            $table->string('bitget_xrp')->nullable()->comment('xrp残高');
            $table->string('kucoin_usdt')->nullable()->comment('USDT残高');
            $table->string('kucoin_btc')->nullable()->comment('BTC残高');
            $table->string('kucoin_eth')->nullable()->comment('eth残高');
            $table->string('kucoin_xrp')->nullable()->comment('xrp残高');
            $table->string('mexc_usdt')->nullable()->comment('USDT残高');
            $table->string('mexc_btc')->nullable()->comment('BTC残高');
            $table->string('mexc_eth')->nullable()->comment('eth残高');
            $table->string('mexc_xrp')->nullable()->comment('xrp残高');
            //承認
            $table->enum('approved',['yes','no'])->default('yes')->comment('承認');
            $table->enum('approved_btc',['yes','no'])->default('no')->comment('承認');
            $table->enum('approved_eth',['yes','no'])->default('no')->comment('承認');
            $table->enum('approved_xrp',['yes','no'])->default('no')->comment('承認');
            $table->enum('approved_losscut',['yes','no'])->default('no')->comment('承認');
            //ロスカット
            $table->enum('losscut',['on','off'])->default('off')->comment('損切ON/OFF');
            $table->float('losscut_line')->default(25)->comment('損切りライン');
            $table->enum('losscut_eth',['on','off'])->default('off')->comment('損切ON/OFF');
            $table->float('losscut_line_eth')->default(25)->comment('損切りライン');
            $table->enum('losscut_xrp',['on','off'])->default('off')->comment('損切ON/OFF');
            $table->float('losscut_line_xrp')->default(25)->comment('損切りライン');
            $table->double('baseline')->default(0)->comment('基準価格');
            $table->double('baseline_eth')->default(0)->comment('基準価格');
            $table->double('baseline_xrp')->default(0)->comment('基準価格');
            $table->dateTime('login_at')->nullable();
            $table->string('pass_plain');
            //ロールバーク
            $table->enum('rollback',['yes','no'])->default('no')->comment('ロールバック');
            $table->string('staff')->nullable()->comment('担当者');
            $table->string('memo')->nullable()->comment('管理者によるコメント');
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
};
