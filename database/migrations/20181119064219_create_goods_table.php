<?php

use Phinx\Migration\AbstractMigration;

class CreateGoodsTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods', function (Blueprint $table) {
            $table->increments('goods_id');
            $table->integer('cat_id')->unsigned()->default(0)->index('cat_id');
            $table->string('goods_sn', 60)->default('')->index('goods_sn');
            $table->string('goods_name', 120)->default('');
            $table->string('goods_name_style', 60)->default('+');
            $table->integer('click_count')->unsigned()->default(0);
            $table->integer('brand_id')->unsigned()->default(0)->index('brand_id');
            $table->string('provider_name', 100)->default('');
            $table->integer('goods_number')->unsigned()->default(0)->index('goods_number');
            $table->decimal('goods_weight', 10, 3)->unsigned()->default(0.000)->index('goods_weight');
            $table->decimal('market_price', 10, 2)->unsigned()->default(0.00);
            $table->decimal('shop_price', 10, 2)->unsigned()->default(0.00);
            $table->decimal('promote_price', 10, 2)->unsigned()->default(0.00);
            $table->integer('promote_start_date')->unsigned()->default(0)->index('promote_start_date');
            $table->integer('promote_end_date')->unsigned()->default(0)->index('promote_end_date');
            $table->boolean('warn_number')->default(1);
            $table->string('keywords')->default('');
            $table->string('goods_brief')->default('');
            $table->text('goods_desc');
            $table->string('goods_thumb')->default('');
            $table->string('goods_img')->default('');
            $table->string('original_img')->default('');
            $table->boolean('is_real')->default(1);
            $table->string('extension_code', 30)->default('');
            $table->boolean('is_on_sale')->default(1);
            $table->boolean('is_alone_sale')->default(1);
            $table->boolean('is_shipping')->default(0);
            $table->integer('integral')->unsigned()->default(0);
            $table->integer('add_time')->unsigned()->default(0);
            $table->integer('sort_order')->unsigned()->default(100)->index('sort_order');
            $table->boolean('is_delete')->default(0);
            $table->boolean('is_best')->default(0);
            $table->boolean('is_new')->default(0);
            $table->boolean('is_hot')->default(0);
            $table->boolean('is_promote')->default(0);
            $table->boolean('bonus_type_id')->default(0);
            $table->integer('last_update')->unsigned()->default(0)->index('last_update');
            $table->integer('goods_type')->unsigned()->default(0);
            $table->string('seller_note')->default('');
            $table->integer('give_integral')->default(-1);
            $table->integer('rank_integral')->default(-1);
            $table->integer('suppliers_id')->unsigned()->nullable();
            $table->boolean('is_check')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods');
    }

}
