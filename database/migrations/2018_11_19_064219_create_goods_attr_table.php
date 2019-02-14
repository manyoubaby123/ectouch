<?php

use Phinx\Migration\AbstractMigration;

class CreateGoodsAttrTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_attr', function (Blueprint $table) {
            $table->increments('goods_attr_id');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id');
            $table->integer('attr_id')->unsigned()->default(0)->index('attr_id');
            $table->text('attr_value');
            $table->string('attr_price')->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods_attr');
    }

}
