<?php

use Phinx\Migration\AbstractMigration;

class CreateRoleTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role', function (Blueprint $table) {
            $table->increments('role_id');
            $table->string('role_name', 60)->default('')->index('user_name');
            $table->text('action_list');
            $table->text('role_describe')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('role');
    }

}
