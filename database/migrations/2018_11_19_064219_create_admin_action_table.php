<?php

use Phinx\Migration\AbstractMigration;

class CreateAdminActionTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admin_action', function (Blueprint $table) {
            $table->boolean('action_id')->primary();
            $table->boolean('parent_id')->default(0)->index('parent_id');
            $table->string('action_code', 20)->default('');
            $table->string('relevance', 20)->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('admin_action');
    }

}
