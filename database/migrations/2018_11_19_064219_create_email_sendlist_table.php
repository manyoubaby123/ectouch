<?php

use Phinx\Migration\AbstractMigration;

class CreateEmailSendlistTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_sendlist', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('email', 100);
            $table->integer('template_id');
            $table->text('email_content');
            $table->boolean('error')->default(0);
            $table->boolean('pri');
            $table->integer('last_send');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('email_sendlist');
    }

}
