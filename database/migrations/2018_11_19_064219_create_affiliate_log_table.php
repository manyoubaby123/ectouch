<?php

use Phinx\Migration\AbstractMigration;

class CreateAffiliateLogTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('affiliate_log', function (Blueprint $table) {
            $table->integer('log_id', true);
            $table->integer('order_id');
            $table->integer('time');
            $table->integer('user_id');
            $table->string('user_name', 60)->nullable();
            $table->decimal('money', 10, 2)->default(0.00);
            $table->integer('point')->default(0);
            $table->boolean('separate_type')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('affiliate_log');
    }

}
