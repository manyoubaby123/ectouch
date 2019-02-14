<?php

use Phinx\Migration\AbstractMigration;

class CreateFavourableActivityTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('favourable_activity', function (Blueprint $table) {
            $table->increments('act_id');
            $table->string('act_name')->index('act_name');
            $table->integer('start_time')->unsigned();
            $table->integer('end_time')->unsigned();
            $table->string('user_rank');
            $table->boolean('act_range');
            $table->string('act_range_ext');
            $table->decimal('min_amount', 10, 2)->unsigned();
            $table->decimal('max_amount', 10, 2)->unsigned();
            $table->boolean('act_type');
            $table->decimal('act_type_ext', 10, 2)->unsigned();
            $table->text('gift');
            $table->boolean('sort_order')->default(50);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('favourable_activity');
    }

}
