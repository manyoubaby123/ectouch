<?php

use Phinx\Migration\AbstractMigration;

class CreateWholesaleTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wholesale', function (Blueprint $table) {
            $table->increments('act_id');
            $table->integer('goods_id')->unsigned()->index('goods_id');
            $table->string('goods_name');
            $table->string('rank_ids');
            $table->text('prices');
            $table->boolean('enabled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ($this->hasTable('wholesale')) {
            $this->dropTable('wholesale');
        }
    }

}
