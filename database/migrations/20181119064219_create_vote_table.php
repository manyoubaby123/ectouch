<?php

use Phinx\Migration\AbstractMigration;

class CreateVoteTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vote', function (Blueprint $table) {
            $table->increments('vote_id');
            $table->string('vote_name', 250)->default('');
            $table->integer('start_time')->unsigned()->default(0);
            $table->integer('end_time')->unsigned()->default(0);
            $table->boolean('can_multi')->default(0);
            $table->integer('vote_count')->unsigned()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('vote');
    }

}
