<?php

use Phinx\Migration\AbstractMigration;

class CreateCollectGoodsTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collect_goods', function (Blueprint $table) {
            $table->increments('rec_id');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id');
            $table->integer('add_time')->unsigned()->default(0);
            $table->boolean('is_attention')->default(0)->index('is_attention');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('collect_goods');
    }

}
