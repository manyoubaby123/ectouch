<?php

use Phinx\Migration\AbstractMigration;

class CreateSnatchLogTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('snatch_log', function (Blueprint $table) {
            $table->increments('log_id');
            $table->boolean('snatch_id')->default(0)->index('snatch_id');
            $table->integer('user_id')->unsigned()->default(0);
            $table->decimal('bid_price', 10, 2)->default(0.00);
            $table->integer('bid_time')->unsigned()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('snatch_log');
    }

}
