<?php

use Phinx\Migration\AbstractMigration;

class CreateAdminLogTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admin_log', function (Blueprint $table) {
            $table->increments('log_id');
            $table->integer('log_time')->unsigned()->default(0)->index('log_time');
            $table->boolean('user_id')->default(0)->index('user_id');
            $table->string('log_info')->default('');
            $table->string('ip_address', 15)->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('admin_log');
    }

}
