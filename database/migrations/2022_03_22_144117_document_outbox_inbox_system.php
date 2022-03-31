<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DocumentOutboxInboxSystem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('documents', function($table) {
            $table->string('ref_number');
            $table->string('kd_tema');
            $table->timestamp('document_date')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('subject');
            $table->text('message');
            $table->unique('ref_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
