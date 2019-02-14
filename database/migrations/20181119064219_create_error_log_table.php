<?php

use Phinx\Migration\AbstractMigration;

class CreateErrorLogTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('error_log', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('info');
            $table->string('file', 100);
            $table->integer('time')->index('time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('error_log');
    }

}
