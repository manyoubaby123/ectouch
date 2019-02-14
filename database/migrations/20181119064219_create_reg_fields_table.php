<?php

use Phinx\Migration\AbstractMigration;

class CreateRegFieldsTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reg_fields', function (Blueprint $table) {
            $table->boolean('id')->primary();
            $table->string('reg_field_name', 60);
            $table->boolean('dis_order')->default(100);
            $table->boolean('display')->default(1);
            $table->boolean('type')->default(0);
            $table->boolean('is_need')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('reg_fields');
    }

}
