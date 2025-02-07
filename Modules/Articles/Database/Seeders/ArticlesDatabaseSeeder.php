<?php

namespace Modules\Articles\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Articles\Entities\Article;

class ArticlesDatabaseSeeder extends Seeder
{
    public function run()
    {
        Article::create([
            'title' => 'First Article',
            'content' => 'This is the content of the first article.'
        ]);

        Article::create([
            'title' => 'Second Article',
            'content' => 'This is the content of the second article.'
        ]);

        Article::create([
            'title' => 'Third Article',
            'content' => 'This is the content of the third article.'
        ]);
    }
}
