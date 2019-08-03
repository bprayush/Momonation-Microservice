<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('momonation')->create('settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('daily_transaction_limit');
            $table->integer('momo_transfer_limit');
            $table->integer('auto_refill_limit');
            $table->integer('initialization_limit');
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
        Schema::dropIfExists('settings');
    }
}
