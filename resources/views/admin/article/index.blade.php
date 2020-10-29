@extends('layouts.app')

@section('title', '投稿記事一覧')

{{-- ナビゲーションバー --}}
@section('navbar-left')
<li>
    <a class="nav-link" href="{{ url('/admin/articles') }}">
        みんなの投稿一覧
    </a>
</li>
<li>
    <a class="nav-link" href="{{ url('/admin/users') }}">
        みんなのプロフィール一覧
    </a>
</li>
@endsection

{{-- ここからコンテンツ --}}
@section('content')
<div class="container mt-4">
    {{-- <div class="row">
        <h2 class="h3 m-4">みんなの投稿</h2>
    </div> --}}
    <div class="row">
        {{-- 投稿作成ボタン --}}
        <div class="col-md-3 offset-1">
            <a class="btn btn-primary mt-3 js-modal-open" href="" data-target="modal01">＋投稿作成</a>
        </div>

        {{-- 投稿の検索 --}}
        <div class="col-md-6 offset-md-2">
            <label for="serch">キーワードで投稿を検索</label>
            <form action="{{ action('Admin\ArticleController@index') }}" method="get" id="serch">
                <div class="form-group row">
                    <div class="col-md-4">
                        <input type="text" class="form-controll" name="search_text" value="{{ $search_text }}">
                    </div>
                    <div class="col-md-4">
                        {{ csrf_field() }}
                        <input type="submit" class="btn btn-primary ml-4" value="検索">
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- 投稿一覧 --}}
    {{-- 投稿のバリデーションメッセージを表示する --}}
    @if (count($errors) > 0)
    <p class="row ml-4">投稿に失敗しました..</p>
    @endif

    @error('image_file')
    <ul class="row ml-2 text-danger">
        @foreach ($errors->all() as $e)
        <li>{{ $e }}</li>
        @endforeach
    </ul>
    @enderror

    {{-- 検索で取得した結果をforeachでカード表示する --}}
    <div class="row">
        <div class="col-md-10 mx-auto">
            @foreach ($articles as $post)
            <div class="card post-list mb-2">
                <div class="card-header bg-dark text-white py-1">
                    @isset($post->user_image_path)
                    <img class="float-left prof-image" src="{{ $post->user_image_path }}">
                    @else
                    <img class="float-left prof-image" src="{{ '/images/defaulte_user.jpg' }}">
                    @endisset
                    <span class="float-left pl-2 pt-3">
                        {{ $post->user_name }}
                    </span>
                    <span class="float-right pt-3">
                        {{ $post->created_at->format('Y年m月d日 H:i') }}
                    </span>
                </div>

                <a class="card-body pt-1 pb-1" href="{{ action('Admin\CommentController@show', ['id' => $post->id]) }}"
                    style="text-decoration: none;">
                    <div class="card-text text-dark pt-2 pb-2">
                        {!! nl2br(e($post->body)) !!}
                    </div>
                    <div class="card-image">
                        @isset($post->image_path)
                        <img class="d-block mx-auto" src="{{ $post->image_path }}">
                        @endisset
                    </div>
                    <div class="card-text text-dark">
                        @isset($post->edited_at)
                        <div class="float-right">
                            <br>
                            [{{ $post->edited_at->format('Y年m月d日 H:i') }}編集]
                        </div>
                        @endisset
                    </div>
                </a>

                <div class="card-footer bg-white py-1">
                    {{-- コメントボタン --}}
                    <a class="badge badge-primary"
                        href="{{ action('Admin\CommentController@show', ['id' => $post->id]) }}">
                        コメント {{ $post->comments->count() }}件
                    </a>

                    {{-- いいねボタン(Likeモデルからいいねを取得) --}}
                    @if($like_model->like_exist(Auth::user()->id, $post->id))
                    <p class="favorite-mark">
                        <a class="js-like-toggle loved" href="" data-postid="{{ $post->id }}">
                            <i class="fas fa-heart"></i>
                        </a>
                        <span class="likeCount">{{ $post->likes_count }}</span>
                    </p>
                    @else
                    <p class="favorite-mark">
                        <a class="js-like-toggle" href="" data-postid="{{ $post->id }}">
                            <i class="fas fa-heart"></i>
                        </a>
                        <span class="likesCount">{{ $post->likes_count }}</span>
                    </p>
                    @endif

                    {{-- ログインユーザーと一致する場合または管理ユーザーの場合は編集削除ボタンを表示 --}}
                    @if ($post->user_id == Auth::user()->id || Auth::user()->id == 1)
                    <div class="card-link float-right">
                        <a href="{{ action('Admin\ArticleController@edit', ['id' => $post->id]) }}">編集する</a>
                        <a href="{{ action('Admin\ArticleController@delete', ['id' => $post->id]) }}">削除する</a>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
            <div class="d-flex justify-content-center mb-5">
                {{ $articles->links() }}
            </div>
        </div>
    </div>
</div>


{{-- 投稿のモーダルウィンドウ --}}
<div id="modal01" class="c-modal js-modal">
    <!-- 背景 -->
    <div class="c-modal_bg js-modal-close">
    </div>
    <!-- 本体 -->
    <div class="c-modal_content">
        <div class="c-modal_content_inner">
            <h4>投稿を作成する</h4>
            <form action="{{ action('Admin\ArticleController@create') }}" method="post" enctype="multipart/form-data"
                id="post-form">
                <!-- バリデーションメッセージを表示-->
                @error('body')
                <tr>
                    <td>{{ $message }}</td>
                </tr>
                @enderror
                <!-- 投稿本文 -->
                <div class="form-group row">
                    {{-- <label class="col-md-2" for="body">投稿内容</label> --}}
                    <div class="col-md-10">
                        <textarea id="countUp" name="body" cols="70" rows="8">{{ old('body') }}</textarea>
                        <label class="badge badge-secondary px-3 py-1 float-right">
                            <span id="count1">0</span>
                            <span> / 120</span>
                        </label>
                    </div>
                </div>
                <!-- 画像の投稿 -->
                <div class="form-group row">
                    <label class="col-md-2 h5" for="body">画像</label>
                    <div class="col-md-10">
                        <input type="file" class="form-control-file" name="image_file">
                    </div>
                </div>

                {{ csrf_field() }}
                <input type="submit" class="btn btn-primary" value="投稿する">
            </form>
            <a class="js-modal-close c-modal_close" href=""><span>閉じる</span></a>
        </div>
    </div>
</div>
@endsection