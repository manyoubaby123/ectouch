<?php

use Phinx\Migration\AbstractMigration;

class CreateCommentTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comment', function (Blueprint $table) {
            $table->increments('comment_id');
            $table->boolean('comment_type')->default(0);
            $table->integer('id_value')->unsigned()->default(0)->index('id_value');
            $table->string('email', 60)->default('');
            $table->string('user_name', 60)->default('');
            $table->text('content');
            $table->boolean('comment_rank')->default(0);
            $table->integer('add_time')->unsigned()->default(0);
            $table->string('ip_address', 15)->default('');
            $table->boolean('status')->default(0);
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id');
            $table->integer('user_id')->unsigned()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('comment');
    }

}
