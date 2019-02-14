<?php

use Phinx\Migration\AbstractMigration;

class CreateArticleCatTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article_cat', function (Blueprint $table) {
            $table->integer('cat_id', true);
            $table->string('cat_name')->default('');
            $table->boolean('cat_type')->default(1)->index('cat_type');
            $table->string('keywords')->default('');
            $table->string('cat_desc')->default('');
            $table->boolean('sort_order')->default(50)->index('sort_order');
            $table->boolean('show_in_nav')->default(0);
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('article_cat');
    }

}
