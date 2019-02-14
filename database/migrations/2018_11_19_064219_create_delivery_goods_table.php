<?php

use Phinx\Migration\AbstractMigration;

class CreateDeliveryGoodsTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivery_goods', function (Blueprint $table) {
            $table->increments('rec_id');
            $table->integer('delivery_id')->unsigned()->default(0);
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id');
            $table->integer('product_id')->unsigned()->nullable()->default(0);
            $table->string('product_sn', 60)->nullable();
            $table->string('goods_name', 120)->nullable();
            $table->string('brand_name', 60)->nullable();
            $table->string('goods_sn', 60)->nullable();
            $table->boolean('is_real')->nullable()->default(0);
            $table->string('extension_code', 30)->nullable();
            $table->integer('parent_id')->unsigned()->nullable()->default(0);
            $table->integer('send_number')->unsigned()->nullable()->default(0);
            $table->text('goods_attr')->nullable();
            $table->index(['delivery_id', 'goods_id'], 'delivery_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('delivery_goods');
    }

}
