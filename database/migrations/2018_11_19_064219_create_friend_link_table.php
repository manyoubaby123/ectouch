<?php

use Phinx\Migration\AbstractMigration;

class CreateFriendLinkTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('friend_link', function (Blueprint $table) {
            $table->increments('link_id');
            $table->string('link_name')->default('');
            $table->string('link_url')->default('');
            $table->string('link_logo')->default('');
            $table->boolean('show_order')->default(50)->index('show_order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('friend_link');
    }

}
