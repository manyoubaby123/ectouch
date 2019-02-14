<?php

use Phinx\Migration\AbstractMigration;

class CreateAuctionLogTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auction_log', function (Blueprint $table) {
            $table->increments('log_id');
            $table->integer('act_id')->unsigned()->index('act_id');
            $table->integer('bid_user')->unsigned();
            $table->decimal('bid_price', 10, 2)->unsigned();
            $table->integer('bid_time')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('auction_log');
    }

}
