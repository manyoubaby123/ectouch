<?php

use Phinx\Migration\AbstractMigration;

class CreateUsersTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->string('email', 60)->default('')->index('email');
            $table->string('user_name', 60)->default('')->unique('user_name');
            $table->string('password', 32)->default('');
            $table->string('question')->default('');
            $table->string('answer')->default('');
            $table->boolean('sex')->default(0);
            $table->date('birthday')->default('1000-01-01');
            $table->decimal('user_money', 10, 2)->default(0.00);
            $table->decimal('frozen_money', 10, 2)->default(0.00);
            $table->integer('pay_points')->unsigned()->default(0);
            $table->integer('rank_points')->unsigned()->default(0);
            $table->integer('address_id')->unsigned()->default(0);
            $table->integer('reg_time')->unsigned()->default(0);
            $table->integer('last_login')->unsigned()->default(0);
            $table->dateTime('last_time')->default('1000-01-01 00:00:00');
            $table->string('last_ip', 15)->default('');
            $table->integer('visit_count')->unsigned()->default(0);
            $table->boolean('user_rank')->default(0);
            $table->boolean('is_special')->default(0);
            $table->string('ec_salt', 10)->nullable();
            $table->string('salt', 10)->default('0');
            $table->integer('parent_id')->default(0)->index('parent_id');
            $table->boolean('flag')->default(0)->index('flag');
            $table->string('alias', 60);
            $table->string('msn', 60);
            $table->string('qq', 20);
            $table->string('office_phone', 20);
            $table->string('home_phone', 20);
            $table->string('mobile_phone', 20);
            $table->boolean('is_validated')->default(0);
            $table->decimal('credit_line', 10, 2)->unsigned();
            $table->string('passwd_question', 50)->nullable();
            $table->string('passwd_answer')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users');
    }

}
