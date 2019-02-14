<?php

use Phinx\Migration\AbstractMigration;

class CreateBonusTypeTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bonus_type', function (Blueprint $table) {
            $table->increments('type_id');
            $table->string('type_name', 60)->default('');
            $table->decimal('type_money', 10, 2)->default(0.00);
            $table->boolean('send_type')->default(0);
            $table->decimal('min_amount', 10, 2)->unsigned()->default(0.00);
            $table->decimal('max_amount', 10, 2)->unsigned()->default(0.00);
            $table->integer('send_start_date')->default(0);
            $table->integer('send_end_date')->default(0);
            $table->integer('use_start_date')->default(0);
            $table->integer('use_end_date')->default(0);
            $table->decimal('min_goods_amount', 10, 2)->unsigned()->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('bonus_type');
    }

}
