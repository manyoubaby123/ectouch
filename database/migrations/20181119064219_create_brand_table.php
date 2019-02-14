<?php

use Phinx\Migration\AbstractMigration;

class CreateBrandTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('brand', function (Blueprint $table) {
            $table->increments('brand_id');
            $table->string('brand_name', 60)->default('');
            $table->string('brand_logo', 80)->default('');
            $table->text('brand_desc');
            $table->string('site_url')->default('');
            $table->boolean('sort_order')->default(50);
            $table->boolean('is_show')->default(1)->index('is_show');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('brand');
    }

}
