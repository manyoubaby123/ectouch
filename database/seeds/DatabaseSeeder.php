<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(ShopConfigTableSeeder::class);
        $this->call(RegionTableSeeder::class);
        $this->call(TemplateTableSeeder::class);
        $this->call(MailTemplatesTableSeeder::class);
        $this->call(RegFieldsTableSeeder::class);
        $this->call(ArticleCatTableSeeder::class);
        $this->call(ArticleTableSeeder::class);
        $this->call(AdminActionTableSeeder::class);
        $this->call(UsersTableSeeder::class);
    }
}
