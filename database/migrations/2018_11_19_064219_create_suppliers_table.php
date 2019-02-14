<?php

use Phinx\Migration\AbstractMigration;

class CreateSuppliersTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->increments('suppliers_id');
            $table->string('suppliers_name')->nullable();
            $table->text('suppliers_desc', 16777215)->nullable();
            $table->boolean('is_check')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('suppliers');
    }

}
