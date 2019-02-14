<?php

use Phinx\Migration\AbstractMigration;

class CreateRegionTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('region', function (Blueprint $table) {
            $table->increments('region_id');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id');
            $table->string('region_name', 120)->default('');
            $table->boolean('region_type')->default(2)->index('region_type');
            $table->integer('agency_id')->unsigned()->default(0)->index('agency_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('region');
    }

}
