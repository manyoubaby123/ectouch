<?php

use Phinx\Migration\AbstractMigration;

class CreatePaymentTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment', function (Blueprint $table) {
            $table->boolean('pay_id')->primary();
            $table->string('pay_code', 20)->default('')->unique('pay_code');
            $table->string('pay_name', 120)->default('');
            $table->string('pay_fee', 10)->default('0');
            $table->text('pay_desc');
            $table->boolean('pay_order')->default(0);
            $table->text('pay_config');
            $table->boolean('enabled')->default(0);
            $table->boolean('is_cod')->default(0);
            $table->boolean('is_online')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('payment');
    }

}
