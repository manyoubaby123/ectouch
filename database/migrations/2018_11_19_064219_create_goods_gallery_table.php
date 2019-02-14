<?php

use Phinx\Migration\AbstractMigration;

class CreateGoodsGalleryTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods_gallery', function (Blueprint $table) {
            $table->increments('img_id');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id');
            $table->string('img_url')->default('');
            $table->string('img_desc')->default('');
            $table->string('thumb_url')->default('');
            $table->string('img_original')->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods_gallery');
    }

}
