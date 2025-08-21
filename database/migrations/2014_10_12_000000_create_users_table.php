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
            $table->string('bitflyer_accesskey')->nullable();
            $table->string('bitflyer_secretkey')->nullable();
            $table->string('bitbank_accesskey')->nullable();
            $table->string('bitbank_secretkey')->nullable();
            $table->string('coincheck_accesskey')->nullable();
            $table->string('coincheck_secretkey')->nullable();
            $table->string('gmo_accesskey')->nullable();
            $table->string('gmo_secretkey')->nullable();
            $table->string('binance_accesskey')->nullable();
            $table->string('binance_secretkey')->nullable();
            $table->string('gate_accesskey')->nullable();
            $table->string('gate_secretkey')->nullable();
            $table->string('kucoin_accesskey')->nullable();
            $table->string('kucoin_secretkey')->nullable();
            $table->string('kucoin_passphrase')->nullable();
            $table->string('mexc_accesskey')->nullable();
            $table->string('mexc_secretkey')->nullable();
            $table->string('bitget_accesskey')->nullable();
            $table->string('bitget_secretkey')->nullable();
            $table->string('bitget_passphrase')->nullable();
            //数量
            $table->double('btc_amount')->nullable();
            $table->double('xrp_amount')->nullable();
            $table->double('eth_amount')->nullable();
            $table->double('bch_amount')->nullable();
            $table->double('ltc_amount')->nullable();
            //閾値
            $table->double('btc_threshold')->nullable();
            $table->double('xrp_threshold')->nullable();
            $table->double('eth_threshold')->nullable();
            $table->double('bch_threshold')->nullable();
            $table->double('ltc_threshold')->nullable();
            //自動取引
            $table->tinyInteger('btc_bitflyer_auto')->default(0);
            $table->tinyInteger('btc_bitbank_auto')->default(0);
            $table->tinyInteger('btc_coincheck_auto')->default(0);
            $table->tinyInteger('btc_gmo_auto')->default(0);
            $table->tinyInteger('btc_binance_auto')->default(0);
            $table->tinyInteger('btc_gate_auto')->default(0);
            $table->tinyInteger('btc_kucoin_auto')->default(0);
            $table->tinyInteger('btc_mexc_auto')->default(0);
            $table->tinyInteger('btc_bitget_auto')->default(0);
            $table->tinyInteger('eth_gmo_auto')->default(0);
            $table->tinyInteger('eth_bitbank_auto')->default(0);
            $table->tinyInteger('xrp_gmo_auto')->default(0);
            $table->tinyInteger('xrp_bitbank_auto')->default(0);
            $table->tinyInteger('bch_gmo_auto')->default(0);
            $table->tinyInteger('bch_bitbank_auto')->default(0);
            $table->tinyInteger('ltc_gmo_auto')->default(0);
            $table->tinyInteger('ltc_bitbank_auto')->default(0);
            //残高
            $table->string('gmo_jpy')->nullable()->comment('日本円残高');
            $table->string('gmo_btc')->nullable()->comment('BTC残高');
            $table->string('gmo_eth')->nullable()->comment('ETH残高');
            $table->string('gmo_xrp')->nullable()->comment('XRP残高');
            $table->string('gmo_ltc')->nullable()->comment('XRP残高');
            $table->string('gmo_bch')->nullable()->comment('XRP残高');
            $table->string('bitflyer_jpy')->nullable()->comment('日本円残高');
            $table->string('bitflyer_btc')->nullable()->comment('BTC残高');
            $table->string('bitflyer_bch')->nullable()->comment('XRP残高');
            $table->string('bitbank_jpy')->nullable()->comment('日本円残高');
            $table->string('bitbank_btc')->nullable()->comment('BTC残高');
            $table->string('bitbank_eth')->nullable()->comment('ETH残高');
            $table->string('bitbank_xrp')->nullable()->comment('XRP残高');
            $table->string('bitbank_ltc')->nullable()->comment('XRP残高');
            $table->string('bitbank_bch')->nullable()->comment('XRP残高');
            $table->string('coincheck_jpy')->nullable()->comment('日本円残高');
            $table->string('coincheck_btc')->nullable()->comment('BTC残高');
            $table->string('binance_usdt')->nullable()->comment('USDT残高');
            $table->string('binance_btc')->nullable()->comment('BTC残高');
            $table->string('gate_usdt')->nullable()->comment('USDT残高');
            $table->string('gate_btc')->nullable()->comment('BTC残高');
            $table->string('kucoin_usdt')->nullable()->comment('USDT残高');
            $table->string('kucoin_btc')->nullable()->comment('BTC残高');
            $table->string('mexc_usdt')->nullable()->comment('USDT残高');
            $table->string('mexc_btc')->nullable()->comment('BTC残高');
            $table->string('bitget_usdt')->nullable()->comment('USDT残高');
            $table->string('bitget_btc')->nullable()->comment('BTC残高');
            //承認
            $table->enum('approved',['yes','no'])->default('yes')->comment('承認');
            $table->enum('approved_btc',['yes','no'])->default('no')->comment('承認');
            $table->enum('approved_eth',['yes','no'])->default('no')->comment('承認');
            $table->enum('approved_xrp',['yes','no'])->default('no')->comment('承認');
            $table->enum('approved_ltc',['yes','no'])->default('no')->comment('承認');
            $table->enum('approved_bch',['yes','no'])->default('no')->comment('承認');
            $table->enum('approved_oversea',['yes','no'])->default('no')->comment('承認');
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
