<?php

use Phinx\Migration\AbstractMigration;

class CreateExchangeGoodsTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchange_goods', function (Blueprint $table) {
            $table->integer('goods_id')->unsigned()->default(0)->primary();
            $table->integer('exchange_integral')->unsigned()->default(0);
            $table->boolean('is_exchange')->default(0);
            $table->boolean('is_hot')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('exchange_goods');
    }

}
