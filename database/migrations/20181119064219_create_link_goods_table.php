<?php

use Phinx\Migration\AbstractMigration;

class CreateLinkGoodsTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('link_goods', function (Blueprint $table) {
            $table->integer('goods_id')->unsigned()->default(0);
            $table->integer('link_goods_id')->unsigned()->default(0);
            $table->boolean('is_double')->default(0);
            $table->boolean('admin_id')->default(0);
            $table->primary(['goods_id', 'link_goods_id', 'admin_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('link_goods');
    }

}
