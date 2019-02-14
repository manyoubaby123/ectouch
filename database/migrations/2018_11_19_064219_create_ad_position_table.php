<?php

use Phinx\Migration\AbstractMigration;

class CreateAdPositionTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ad_position', function (Blueprint $table) {
            $table->boolean('position_id')->primary();
            $table->string('position_name', 60)->default('');
            $table->integer('ad_width')->unsigned()->default(0);
            $table->integer('ad_height')->unsigned()->default(0);
            $table->string('position_desc')->default('');
            $table->text('position_style');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('ad_position');
    }

}
