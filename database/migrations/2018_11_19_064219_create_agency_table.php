<?php

use Phinx\Migration\AbstractMigration;

class CreateAgencyTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agency', function (Blueprint $table) {
            $table->increments('agency_id');
            $table->string('agency_name')->index('agency_name');
            $table->text('agency_desc');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('agency');
    }

}
