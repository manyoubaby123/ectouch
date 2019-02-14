<?php

use Phinx\Migration\AbstractMigration;

class CreateTopicTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('topic', function (Blueprint $table) {
            $table->increments('topic_id');
            $table->string('title')->default('\'\'');
            $table->text('intro');
            $table->integer('start_time')->default(0);
            $table->integer('end_time')->default(0);
            $table->text('data');
            $table->string('template')->default('\'\'');
            $table->text('css');
            $table->string('topic_img')->nullable();
            $table->string('title_pic')->nullable();
            $table->char('base_style', 6)->nullable();
            $table->text('htmls', 16777215)->nullable();
            $table->string('keywords')->nullable();
            $table->string('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('topic');
    }

}
