<?php

use Illuminate\Database\Seeder;
use App\Article;
use App\Comment;

class PostsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // テストデータの作成実行を定義
        factory(Article::Class, 30)
            ->create()
            ->each(function ($post) {
                $comments = factory(App\Comment::class, 2)->make();
                $post->comments()->saveMany($comments);
            });
    }
}
