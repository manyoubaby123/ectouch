<?php

use Phinx\Migration\AbstractMigration;

class CreateBookingGoodsTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('booking_goods', function (Blueprint $table) {
            $table->increments('rec_id');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id');
            $table->string('email', 60)->default('');
            $table->string('link_man', 60)->default('');
            $table->string('tel', 60)->default('');
            $table->integer('goods_id')->unsigned()->default(0);
            $table->string('goods_desc')->default('');
            $table->integer('goods_number')->unsigned()->default(0);
            $table->integer('booking_time')->unsigned()->default(0);
            $table->boolean('is_dispose')->default(0);
            $table->string('dispose_user', 30)->default('');
            $table->integer('dispose_time')->unsigned()->default(0);
            $table->string('dispose_note')->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('booking_goods');
    }

}
