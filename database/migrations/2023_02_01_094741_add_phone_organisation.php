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
        Schema::table('organisations', function (Blueprint $table) {
            if (!Schema::hasColumn('organisations','contact_person_telephone')) {
                $table->string('contact_person_phone')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('organisations', function (Blueprint $table) {
            //
        });
    }
};
