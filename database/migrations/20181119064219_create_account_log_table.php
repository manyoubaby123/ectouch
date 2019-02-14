<?php

use Phinx\Migration\AbstractMigration;

class CreateAccountLogTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_log', function (Blueprint $table) {
            $table->increments('log_id');
            $table->integer('user_id')->unsigned()->index('user_id');
            $table->decimal('user_money', 10, 2);
            $table->decimal('frozen_money', 10, 2);
            $table->integer('rank_points');
            $table->integer('pay_points');
            $table->integer('change_time')->unsigned();
            $table->string('change_desc');
            $table->boolean('change_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('account_log');
    }

}
