<?php

use Phinx\Migration\AbstractMigration;

class CreateAreaRegionTable extends AbstractMigration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('area_region', function(Blueprint $table)
		{
			$table->integer('shipping_area_id')->unsigned()->default(0);
			$table->integer('region_id')->unsigned()->default(0);
			$table->primary(['shipping_area_id','region_id']);
		});
	}
	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('area_region');
	}

}
