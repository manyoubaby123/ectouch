<?php

use Phinx\Migration\AbstractMigration;

class CreateSearchengineTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('searchengine', function (Blueprint $table) {
            $table->date('date')->default('1000-01-01');
            $table->string('searchengine', 20)->default('');
            $table->integer('count')->unsigned()->default(0);
            $table->primary(['date', 'searchengine']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('searchengine');
    }

}
