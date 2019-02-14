<?php

use Phinx\Migration\AbstractMigration;

class CreateKeywordsTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('keywords', function (Blueprint $table) {
            $table->date('date')->default('1000-01-01');
            $table->string('searchengine', 20)->default('');
            $table->string('keyword', 90)->default('');
            $table->integer('count')->unsigned()->default(0);
            $table->primary(['date', 'searchengine', 'keyword']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('keywords');
    }

}
