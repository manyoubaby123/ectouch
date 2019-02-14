<?php

use Phinx\Migration\AbstractMigration;

class CreateBackGoodsTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('back_goods', function (Blueprint $table) {
            $table->increments('rec_id');
            $table->integer('back_id')->unsigned()->nullable()->default(0)->index('back_id');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id');
            $table->integer('product_id')->unsigned()->default(0);
            $table->string('product_sn', 60)->nullable();
            $table->string('goods_name', 120)->nullable();
            $table->string('brand_name', 60)->nullable();
            $table->string('goods_sn', 60)->nullable();
            $table->boolean('is_real')->nullable()->default(0);
            $table->integer('send_number')->unsigned()->nullable()->default(0);
            $table->text('goods_attr')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('back_goods');
    }

}
