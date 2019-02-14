<?php

use Phinx\Migration\AbstractMigration;

class CreateUserRankTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_rank', function (Blueprint $table) {
            $table->boolean('rank_id')->primary();
            $table->string('rank_name', 30)->default('');
            $table->integer('min_points')->unsigned()->default(0);
            $table->integer('max_points')->unsigned()->default(0);
            $table->boolean('discount')->default(0);
            $table->boolean('show_price')->default(1);
            $table->boolean('special_rank')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_rank');
    }

}
