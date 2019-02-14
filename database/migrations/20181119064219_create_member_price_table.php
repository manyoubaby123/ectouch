<?php

use Phinx\Migration\AbstractMigration;

class CreateMemberPriceTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_price', function (Blueprint $table) {
            $table->increments('price_id');
            $table->integer('goods_id')->unsigned()->default(0);
            $table->boolean('user_rank')->default(0);
            $table->decimal('user_price', 10, 2)->default(0.00);
            $table->index(['goods_id', 'user_rank'], 'goods_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('member_price');
    }

}
