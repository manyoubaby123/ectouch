<?php

use Phinx\Migration\AbstractMigration;

class CreateShippingAreaTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_area', function (Blueprint $table) {
            $table->increments('shipping_area_id');
            $table->string('shipping_area_name', 150)->default('');
            $table->boolean('shipping_id')->default(0)->index('shipping_id');
            $table->text('configure');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('shipping_area');
    }

}
