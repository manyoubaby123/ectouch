<?php

use Phinx\Migration\AbstractMigration;

class CreateGroupGoodsTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_goods', function (Blueprint $table) {
            $table->integer('parent_id')->unsigned()->default(0);
            $table->integer('goods_id')->unsigned()->default(0);
            $table->decimal('goods_price', 10, 2)->unsigned()->default(0.00);
            $table->boolean('admin_id')->default(0);
            $table->primary(['parent_id', 'goods_id', 'admin_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('group_goods');
    }

}
