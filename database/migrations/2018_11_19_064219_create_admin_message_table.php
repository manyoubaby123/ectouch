<?php

use Phinx\Migration\AbstractMigration;

class CreateAdminMessageTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admin_message', function (Blueprint $table) {
            $table->increments('message_id');
            $table->boolean('sender_id')->default(0);
            $table->boolean('receiver_id')->default(0)->index('receiver_id');
            $table->integer('sent_time')->unsigned()->default(0);
            $table->integer('read_time')->unsigned()->default(0);
            $table->boolean('readed')->default(0);
            $table->boolean('deleted')->default(0);
            $table->string('title', 150)->default('');
            $table->text('message');
            $table->index(['sender_id', 'receiver_id'], 'sender_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('admin_message');
    }

}
