<?php

use Phinx\Migration\AbstractMigration;

class CreateAutoManageTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auto_manage', function (Blueprint $table) {
            $table->integer('item_id');
            $table->string('type', 10);
            $table->integer('starttime');
            $table->integer('endtime');
            $table->primary(['item_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('auto_manage');
    }

}
