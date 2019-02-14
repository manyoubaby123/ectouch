<?php

use Phinx\Migration\AbstractMigration;

class CreateOrderGoodsTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_goods', function (Blueprint $table) {
            $table->increments('rec_id');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id');
            $table->string('goods_name', 120)->default('');
            $table->string('goods_sn', 60)->default('');
            $table->integer('product_id')->unsigned()->default(0);
            $table->integer('goods_number')->unsigned()->default(1);
            $table->decimal('market_price', 10, 2)->default(0.00);
            $table->decimal('goods_price', 10, 2)->default(0.00);
            $table->text('goods_attr');
            $table->integer('send_number')->unsigned()->default(0);
            $table->boolean('is_real')->default(0);
            $table->string('extension_code', 30)->default('');
            $table->integer('parent_id')->unsigned()->default(0);
            $table->integer('is_gift')->unsigned()->default(0);
            $table->string('goods_attr_id')->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('order_goods');
    }

}
