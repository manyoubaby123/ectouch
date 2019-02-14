<?php

use Phinx\Migration\AbstractMigration;

class CreateVolumePriceTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('volume_price', function (Blueprint $table) {
            $table->boolean('price_type');
            $table->integer('goods_id')->unsigned();
            $table->integer('volume_number')->unsigned()->default(0);
            $table->decimal('volume_price', 10, 2)->default(0.00);
            $table->primary(['price_type', 'goods_id', 'volume_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('volume_price');
    }

}
