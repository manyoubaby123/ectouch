<?php

use Phinx\Migration\AbstractMigration;

class CreateGoodsCatTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_cat', function (Blueprint $table) {
            $table->integer('goods_id')->unsigned()->default(0);
            $table->integer('cat_id')->unsigned()->default(0);
            $table->primary(['goods_id', 'cat_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods_cat');
    }

}
