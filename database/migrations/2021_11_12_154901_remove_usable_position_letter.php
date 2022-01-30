<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUsablePositionLetter extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('position_letters', function($table) {
            $table->dropColumn('usable');
            $table->enum('status', ['usable', 'pending', 'reject'])->default('pending');
         });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('position_letters', function($table) {
            $table->enum('usable', ['T', 'F'])->default('F');

         });    
    }
}
