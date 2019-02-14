<?php

use Phinx\Migration\AbstractMigration;

class CreateEmailListTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_list', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('email', 60);
            $table->boolean('stat')->default(0);
            $table->string('hash', 10);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('email_list');
    }

}
