<?php

use Phinx\Migration\AbstractMigration;

class CreateAdsenseTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adsense', function (Blueprint $table) {
            $table->integer('from_ad')->default(0)->index('from_ad');
            $table->string('referer')->default('');
            $table->integer('clicks')->unsigned()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('adsense');
    }

}
