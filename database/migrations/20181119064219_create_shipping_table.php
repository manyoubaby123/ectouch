<?php

use Phinx\Migration\AbstractMigration;

class CreateShippingTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping', function (Blueprint $table) {
            $table->boolean('shipping_id')->primary();
            $table->string('shipping_code', 20)->default('');
            $table->string('shipping_name', 120)->default('');
            $table->string('shipping_desc')->default('');
            $table->string('insure', 10)->default('0');
            $table->boolean('support_cod')->default(0);
            $table->boolean('enabled')->default(0);
            $table->text('shipping_print');
            $table->string('print_bg')->nullable();
            $table->text('config_lable')->nullable();
            $table->boolean('print_model')->nullable()->default(0);
            $table->boolean('shipping_order')->default(0);
            $table->index(['shipping_code', 'enabled'], 'shipping_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('shipping');
    }

}
