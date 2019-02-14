<?php

use Phinx\Migration\AbstractMigration;

class CreateAdTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ad', function (Blueprint $table) {
            $table->increments('ad_id');
            $table->integer('position_id')->unsigned()->default(0)->index('position_id');
            $table->boolean('media_type')->default(0);
            $table->string('ad_name', 60)->default('');
            $table->string('ad_link')->default('');
            $table->text('ad_code');
            $table->integer('start_time')->default(0);
            $table->integer('end_time')->default(0);
            $table->string('link_man', 60)->default('');
            $table->string('link_email', 60)->default('');
            $table->string('link_phone', 60)->default('');
            $table->integer('click_count')->unsigned()->default(0);
            $table->boolean('enabled')->default(1)->index('enabled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('ad');
    }

}
