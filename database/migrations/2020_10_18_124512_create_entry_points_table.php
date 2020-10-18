<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntryPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entry_points', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            $table->float('total_amount');
            $table->string('reference');
            $table->string('payment_reference');
            $table->string('payment_method');
            $table->string('platform');
            $table->integer('transaction_count');
            $table->string('status');
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
        Schema::dropIfExists('entry_points');
    }
}
