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
        Schema::table('project_budgets', function (Blueprint $table) {
            if (!Schema::hasColumn('project_budgets', 'ssd_value_amount')) {
                $table->double('ssd_value_amount')->nullable();
                $table->double('usd_value_amount')->nullable();
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
        Schema::table('project_budgets', function (Blueprint $table) {
            //
        });
    }
};