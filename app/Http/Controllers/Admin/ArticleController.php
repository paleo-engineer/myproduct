<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; // 通常のリクエスト
use App\Http\Requests\ArticleRequest; // フォームリクエストを使う
use App\Article; // Articlelモデルを使う
use App\User; // Userモデルを使う
use App\Like; // Likeモデルを使う
use Illuminate\Support\Facades\Auth; // Authファサードを使う
use Carbon\Carbon; // 日付操作ライブラリを使う
use Intervention\Image\Facades\Image; // InterventionImageを使う(画像リサイズ)
use Illuminate\Support\Facades\Storage; // Storageファサードを使う(ユーザー画像を保存,削除)

class ArticleController extends Controller
{
    // createアクション(引数のRequestはArticleRequestに変更)
    public function create(ArticleRequest $request)
    {
        $articles = new Article;
        $form = $request->all();

        // フォームに画像があれば画像を保存する処理を行う
        if (empty($form['image_file'])) {
            $articles->image_path = null;
        } else {
            // 画像ファイルを取得
            $posted_image = $request->file('image_file');

            // 画像をリサイズしてjpgにencodeする(InterventionImageのImageファサードを使用)
            $resized_image = Image::make($posted_image)->fit(640, 360)->encode('jpg');

            // さらに自動回転を行う(ここでEXIFが削除される)
            $resized_image->orientate()->save();

            // 加工した画像からhashを生成し、ファイル名を設定する
            $image_hash = md5($resized_image->__toString());
            $image_name = "{$image_hash}.jpg";

            // 加工した画像を保存する
            Storage::disk('s3')->put('article_images/' . $image_name, $resized_image, 'public');
            // Storage::put('public/image/' . $image_name, $resized_image); // ローカル環境での記述

            // S3のファイルのURLを取得してuser_image_pathカラムに書き込む
            $articles->image_path = Storage::disk('s3')->url('article_images/' . $image_name);
        }

        // ログインユーザー情報を取得する
        $articles->user_id = Auth::user()->id;
        $articles->user_name = Auth::user()->name; // defaulte値として必要(nullableでない)

        // フォームから送信されてきた_tokenとimageを削除
        unset($form['_token']);
        unset($form['image_file']);

        // データベースに保存する
        $articles->fill($form);
        $articles->save();

        return redirect('admin/articles');
    }


    // editアクションを定義
    public function edit(Request $request)
    {
        // idで検索してModelからデータを取得してViewに渡す(無ければ404エラーを返す)
        $articles = Article::find($request->id);
        if (empty($articles)) {
            abort(404);
        }
        return view('admin.article.edit', ['article_form' => $articles]);
    }


    // updateアクションを定義
    public function update(ArticleRequest $request)
    {
        // Modelからデータを取得する(投稿idで検索)
        $articles = Article::find($request->id);
        $form = $request->all();

        // フォームに画像があれば画像を保存する処理を行う
        // (可読性を考え画像を削除する場合の処理を先にした)
        if (strcmp($request->remove, 'true') == 0) {
            // 画像があれば削除する
            if (isset($articles->image_path)) {
                Storage::disk('s3')->delete('article_images/' . $articles->image_path);
            }
            $articles->image_path = null;
        } elseif (isset($form['image_file'])) {

            // 新しい画像ファイルとファイル名を取得
            $posted_image = $request->file('image_file');

            // 画像をリサイズしてjpgにencodeする(InterventionImageのImageファサードを使用)
            $resized_image = Image::make($posted_image)->fit(640, 360)->encode('jpg');

            // さらに自動回転を行う(ここでEXIFが削除される)
            $resized_image->orientate()->save();

            // 加工した画像からhashを生成し、ファイル名を設定する
            $image_hash = md5($resized_image->__toString());
            $image_name = "{$image_hash}.jpg";

            // 現在設定中の画像があれば削除し、加工した新しい画像を保存する
            if (isset($articles->image_path)) {
                Storage::disk('s3')->delete('article_images/' . $articles->image_path);
            }
            Storage::disk('s3')->put('article_images/' . $image_name, $resized_image, 'public');

            // S3の新しい画像ファイルのURLを取得してuser_image_pathカラムに書き込む
            $articles->image_path = Storage::disk('s3')->url('article_images/' . $image_name);
        }

        // フォームの不要なデータを削除する
        unset($form['_token']);
        unset($form['image_file']);
        unset($form['remove']);

        // 編集日時を追加する
        $articles->edited_at = Carbon::now();

        // フォームにデータを上書きして保存する
        $articles->fill($form)->save();

        return redirect('admin/articles');
    }

    // deleteアクションを定義
    public function delete(Request $request)
    {
        // Modelからデータを取得して削除(投稿idで検索)
        $articles = Article::find($request->id);

        // 画像パスがあればS3の画像ファイルを削除し、テーブルのデータを削除する
        if (isset($articles->image_path)) {
            Storage::disk('s3')->delete('user_images/' . $articles->image_path);
        }
        $articles->delete();
        return redirect('admin/articles');
    }

    // indexアクションを定義
    // 部分一致であいまい検索
    public function index(Request $request)
    {
        $data = [];
        $search_text = $request->search_text;

        if ($search_text != '') {
            // 検索されたら検索結果を取得する(likesテーブルとのリレーションの数も取得)
            $articles = Article::where('body', 'like', '%' . $search_text . '%')->withCount('likes')->orderBy('created_at', 'desc')->paginate(7);

            foreach ($articles as $article) {
                $article->user_name = User::find($article->user_id)->name;
                $article->user_image_path = User::find($article->user_id)->user_image_path;
            }
        } else {
            // 検索が無い場合はすべての投稿を取得する(likesテーブルとのリレーションの数も取得)
            $articles = Article::withCount('likes')->orderBy('created_at', 'desc')->paginate(7);

            foreach ($articles as $article) {
                $article->user_name = User::find($article->user_id)->name;
                $article->user_image_path = User::find($article->user_id)->user_image_path;
            }
        }

        // LikeモデルのインスタンスをViewに渡す
        $like_model = new Like;

        // Viewに渡すパラメータ
        $data = [
            'articles' => $articles,
            'search_text' => $search_text,
            'like_model' => $like_model,
        ];

        return view('admin.article.index', $data);
    }

    // ajaxlikeアクションを定義
    public function ajaxlike(Request $request)
    {
        $id = Auth::user()->id;
        $article_id = $request->article_id;
        $like = new Like;
        $article = Article::findOrFail($article_id);

        // Auth::userのlikeがある場合はlikesテーブルのレコードを削除する
        if ($like->like_exist($id, $article_id)) {
            Like::where('article_id', $article_id)->where('user_id', $id)->delete();
        } else {
            // 無ければlikesテーブルに新しいレコードを作成する
            $like = new Like;
            $like->article_id = $request->article_id;
            $like->user_id = Auth::user()->id;
            $like->save();
        }

        // LoadCount()でlikesテーブルとのリレーションの数を取得(〇〇_countとする)
        $articleLikesCount = $article->loadCount('likes')->likes_count;

        // 記事のいいね数をajaxのdataとしてjson形式で返す
        return response()->json($articleLikesCount);
    }
}
