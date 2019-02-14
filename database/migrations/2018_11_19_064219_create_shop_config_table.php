<?php

use Phinx\Migration\AbstractMigration;

class CreateShopConfigTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_config', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id');
            $table->string('code', 30)->default('')->unique('code');
            $table->string('type', 10)->default('');
            $table->string('store_range')->default('');
            $table->string('store_dir')->default('');
            $table->text('value');
            $table->boolean('sort_order')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('shop_config');
    }

}
