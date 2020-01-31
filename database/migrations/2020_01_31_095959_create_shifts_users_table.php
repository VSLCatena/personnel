<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shifts_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('shift_id'); //bardienst van 22:00-laat (uitzit)
            $table->integer('user_id'); //persoon X
            $table->integer('updated_by'); //naam barcolid
            $table->timestamps();
			
			#foreign references
			$table->foreign('shift_id')->references('id')->on('shifts');
			$table->foreign('user_id')->references('id')->on('users');
			$table->foreign('updated_by')->references('id')->on('users');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shifts_users');
    }
}
