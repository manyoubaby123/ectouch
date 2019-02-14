<?php

use Phinx\Migration\AbstractMigration;

class CreateOrderInfoTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_info', function (Blueprint $table) {
            $table->increments('order_id');
            $table->string('order_sn', 20)->default('')->unique('order_sn');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id');
            $table->boolean('order_status')->default(0)->index('order_status');
            $table->boolean('shipping_status')->default(0)->index('shipping_status');
            $table->boolean('pay_status')->default(0)->index('pay_status');
            $table->string('consignee', 60)->default('');
            $table->integer('country')->unsigned()->default(0);
            $table->integer('province')->unsigned()->default(0);
            $table->integer('city')->unsigned()->default(0);
            $table->integer('district')->unsigned()->default(0);
            $table->string('address')->default('');
            $table->string('zipcode', 60)->default('');
            $table->string('tel', 60)->default('');
            $table->string('mobile', 60)->default('');
            $table->string('email', 60)->default('');
            $table->string('best_time', 120)->default('');
            $table->string('sign_building', 120)->default('');
            $table->string('postscript')->default('');
            $table->boolean('shipping_id')->default(0)->index('shipping_id');
            $table->string('shipping_name', 120)->default('');
            $table->boolean('pay_id')->default(0)->index('pay_id');
            $table->string('pay_name', 120)->default('');
            $table->string('how_oos', 120)->default('');
            $table->string('how_surplus', 120)->default('');
            $table->string('pack_name', 120)->default('');
            $table->string('card_name', 120)->default('');
            $table->string('card_message')->default('');
            $table->string('inv_payee', 120)->default('');
            $table->string('inv_content', 120)->default('');
            $table->decimal('goods_amount', 10, 2)->default(0.00);
            $table->decimal('shipping_fee', 10, 2)->default(0.00);
            $table->decimal('insure_fee', 10, 2)->default(0.00);
            $table->decimal('pay_fee', 10, 2)->default(0.00);
            $table->decimal('pack_fee', 10, 2)->default(0.00);
            $table->decimal('card_fee', 10, 2)->default(0.00);
            $table->decimal('money_paid', 10, 2)->default(0.00);
            $table->decimal('surplus', 10, 2)->default(0.00);
            $table->integer('integral')->unsigned()->default(0);
            $table->decimal('integral_money', 10, 2)->default(0.00);
            $table->decimal('bonus', 10, 2)->default(0.00);
            $table->decimal('order_amount', 10, 2)->default(0.00);
            $table->integer('from_ad')->default(0);
            $table->string('referer')->default('');
            $table->integer('add_time')->unsigned()->default(0);
            $table->integer('confirm_time')->unsigned()->default(0);
            $table->integer('pay_time')->unsigned()->default(0);
            $table->integer('shipping_time')->unsigned()->default(0);
            $table->boolean('pack_id')->default(0);
            $table->boolean('card_id')->default(0);
            $table->integer('bonus_id')->unsigned()->default(0);
            $table->string('invoice_no')->default('');
            $table->string('extension_code', 30)->default('');
            $table->integer('extension_id')->unsigned()->default(0);
            $table->string('to_buyer')->default('');
            $table->string('pay_note')->default('');
            $table->integer('agency_id')->unsigned()->index('agency_id');
            $table->string('inv_type', 60);
            $table->decimal('tax', 10, 2);
            $table->boolean('is_separate')->default(0);
            $table->integer('parent_id')->unsigned()->default(0);
            $table->decimal('discount', 10, 2);
            $table->index(['extension_code', 'extension_id'], 'extension_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('order_info');
    }

}
