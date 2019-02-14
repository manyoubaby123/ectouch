<?php

use Phinx\Migration\AbstractMigration;

class CreateRegExtendInfoTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reg_extend_info', function (Blueprint $table) {
            $table->increments('Id');
            $table->integer('user_id')->unsigned();
            $table->integer('reg_field_id')->unsigned();
            $table->text('content');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('reg_extend_info');
    }

}
