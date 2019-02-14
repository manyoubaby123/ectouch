<?php

use Phinx\Migration\AbstractMigration;

class CreateTemplateTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template', function (Blueprint $table) {
            $table->string('filename', 30)->default('');
            $table->string('region', 40)->default('');
            $table->string('library', 40)->default('');
            $table->boolean('sort_order')->default(0);
            $table->integer('id')->unsigned()->default(0);
            $table->boolean('number')->default(5);
            $table->boolean('type')->default(0);
            $table->string('theme', 60)->default('')->index('theme');
            $table->string('remarks', 30)->default('')->index('remarks');
            $table->index(['filename', 'region'], 'filename');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('template');
    }

}
