<?php

use Phinx\Migration\AbstractMigration;

class CreateGoodsActivityTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_activity', function (Blueprint $table) {
            $table->increments('act_id');
            $table->string('act_name');
            $table->text('act_desc');
            $table->boolean('act_type');
            $table->integer('goods_id')->unsigned();
            $table->integer('product_id')->unsigned()->default(0);
            $table->string('goods_name');
            $table->integer('start_time')->unsigned();
            $table->integer('end_time')->unsigned();
            $table->boolean('is_finished');
            $table->text('ext_info');
            $table->index(['act_name', 'act_type', 'goods_id'], 'act_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods_activity');
    }

}
