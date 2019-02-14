<?php

use Phinx\Migration\AbstractMigration;

class CreatePayLogTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pay_log', function (Blueprint $table) {
            $table->increments('log_id');
            $table->integer('order_id')->unsigned()->default(0);
            $table->decimal('order_amount', 10, 2)->unsigned();
            $table->boolean('order_type')->default(0);
            $table->boolean('is_paid')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('pay_log');
    }

}
