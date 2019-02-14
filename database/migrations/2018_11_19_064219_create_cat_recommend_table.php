<?php

use Phinx\Migration\AbstractMigration;

class CreateCatRecommendTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cat_recommend', function (Blueprint $table) {
            $table->integer('cat_id');
            $table->boolean('recommend_type');
            $table->primary(['cat_id', 'recommend_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cat_recommend');
    }

}
