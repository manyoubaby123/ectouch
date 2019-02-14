<?php

use Phinx\Migration\AbstractMigration;

class CreateVirtualCardTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('virtual_card', function (Blueprint $table) {
            $table->integer('card_id', true);
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id');
            $table->string('card_sn', 60)->default('')->index('car_sn');
            $table->string('card_password', 60)->default('');
            $table->integer('add_date')->default(0);
            $table->integer('end_date')->default(0);
            $table->boolean('is_saled')->default(0)->index('is_saled');
            $table->string('order_sn', 20)->default('');
            $table->string('crc32', 12)->default('0');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('virtual_card');
    }

}
