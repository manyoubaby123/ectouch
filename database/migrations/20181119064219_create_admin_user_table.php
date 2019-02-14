<?php

use Phinx\Migration\AbstractMigration;

class CreateAdminUserTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admin_user', function (Blueprint $table) {
            $table->increments('user_id');
            $table->string('user_name', 60)->default('')->index('user_name');
            $table->string('email', 60)->default('');
            $table->string('password', 32)->default('');
            $table->string('ec_salt', 10)->nullable();
            $table->integer('add_time')->default(0);
            $table->integer('last_login')->default(0);
            $table->string('last_ip', 15)->default('');
            $table->text('action_list');
            $table->text('nav_list');
            $table->string('lang_type', 50)->default('');
            $table->integer('agency_id')->unsigned()->index('agency_id');
            $table->integer('suppliers_id')->unsigned()->nullable()->default(0);
            $table->text('todolist')->nullable();
            $table->integer('role_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('admin_user');
    }

}
