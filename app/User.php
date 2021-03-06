<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable; // Userをオーバーライドしている
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'introduction', 'user_image_path'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * リレーションの設定
     */

    // Articlesテーブルと紐づける(1対多)
    public function articles()
    {
        return $this->hasMany('App\Article');
    }

    // Commentsテーブルと紐づける(1対多)
    public function comments()
    {
        return $this->hasMany('App\Comment');
    }

    // Likesテーブルと紐づける(1対多)
    public function likes()
    {
        return $this->hasMany('App\Like');
    }

    // Building_intakesテーブルと紐づける(1対多)
    public function building_intakes()
    {
        return $this->hasMany('App\BuildingIntake');
    }
}
