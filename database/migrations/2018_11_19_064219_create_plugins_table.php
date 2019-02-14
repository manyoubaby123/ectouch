<?php

use Phinx\Migration\AbstractMigration;

class CreatePluginsTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->string('code', 30)->default('')->primary();
            $table->string('version', 10)->default('');
            $table->string('library')->default('');
            $table->boolean('assign')->default(0);
            $table->integer('install_date')->unsigned()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('plugins');
    }

}
