<?php

use Phinx\Migration\AbstractMigration;

class CreateUserAddressTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_address', function (Blueprint $table) {
            $table->increments('address_id');
            $table->string('address_name', 50)->default('');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id');
            $table->string('consignee', 60)->default('');
            $table->string('email', 60)->default('');
            $table->integer('country')->default(0);
            $table->integer('province')->default(0);
            $table->integer('city')->default(0);
            $table->integer('district')->default(0);
            $table->string('address', 120)->default('');
            $table->string('zipcode', 60)->default('');
            $table->string('tel', 60)->default('');
            $table->string('mobile', 60)->default('');
            $table->string('sign_building', 120)->default('');
            $table->string('best_time', 120)->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_address');
    }

}
