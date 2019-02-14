<?php

use Phinx\Migration\AbstractMigration;

class CreateOrderActionTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_action', function (Blueprint $table) {
            $table->increments('action_id');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id');
            $table->string('action_user', 30)->default('');
            $table->boolean('order_status')->default(0);
            $table->boolean('shipping_status')->default(0);
            $table->boolean('pay_status')->default(0);
            $table->boolean('action_place')->default(0);
            $table->string('action_note')->default('');
            $table->integer('log_time')->unsigned()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('order_action');
    }

}
