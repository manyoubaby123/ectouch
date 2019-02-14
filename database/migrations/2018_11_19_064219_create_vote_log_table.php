<?php

use Phinx\Migration\AbstractMigration;

class CreateVoteLogTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vote_log', function (Blueprint $table) {
            $table->increments('log_id');
            $table->integer('vote_id')->unsigned()->default(0)->index('vote_id');
            $table->string('ip_address', 15)->default('');
            $table->integer('vote_time')->unsigned()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('vote_log');
    }

}
