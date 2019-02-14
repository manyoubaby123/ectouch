<?php

use Phinx\Migration\AbstractMigration;

class CreateMailTemplatesTable extends AbstractMigration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mail_templates', function (Blueprint $table) {
            $table->boolean('template_id')->primary();
            $table->string('template_code', 30)->default('')->unique('template_code');
            $table->boolean('is_html')->default(0);
            $table->string('template_subject', 200)->default('');
            $table->text('template_content');
            $table->integer('last_modify')->unsigned()->default(0);
            $table->integer('last_send')->unsigned()->default(0);
            $table->string('type', 10)->index('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('mail_templates');
    }

}
