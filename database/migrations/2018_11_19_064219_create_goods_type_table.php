<?php

use Phinx\Migration\AbstractMigration;

class CreateGoodsTypeTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_type', function (Blueprint $table) {
            $table->increments('cat_id');
            $table->string('cat_name', 60)->default('');
            $table->boolean('enabled')->default(1);
            $table->string('attr_group');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods_type');
    }

}
