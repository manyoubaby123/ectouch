<?php

use Phinx\Migration\AbstractMigration;

class CreateUserFeedTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_feed', function (Blueprint $table) {
            $table->increments('feed_id');
            $table->integer('user_id')->unsigned()->default(0);
            $table->integer('value_id')->unsigned()->default(0);
            $table->integer('goods_id')->unsigned()->default(0);
            $table->boolean('feed_type')->default(0);
            $table->boolean('is_feed')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_feed');
    }

}
