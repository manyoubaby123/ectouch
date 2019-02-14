<?php

use Phinx\Migration\AbstractMigration;

class CreateVoteOptionTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vote_option', function (Blueprint $table) {
            $table->increments('option_id');
            $table->integer('vote_id')->unsigned()->default(0)->index('vote_id');
            $table->string('option_name', 250)->default('');
            $table->integer('option_count')->unsigned()->default(0);
            $table->boolean('option_order')->default(100);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('vote_option');
    }

}
