<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Zizaco\Entrust\Traits\EntrustUserTrait;
use JWTAuth;
use App\Models\PasswordReset;
use Carbon\Carbon;

class User extends Authenticatable
{
    use Notifiable;
    use EntrustUserTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function postSignupActions($roles = ['user']) {
        foreach ($roles as $role) {
            $this->attachRole(Role::where('name', $role)->first());
        }
    }

    public function getToken() {
        $roles = $this->roles()->get()->pluck('name');
        return JWTAuth::fromUser($this, ['exp' => strtotime('+1 year'), 'roles' => $roles, 'user_id' => $this->id, 'profile_photo' => $this->profile_photo]);
    }

    public function sendPasswordResetEmail()
    {
        $token = str_random(4);
        $reset = PasswordReset::firstOrNew(['email' => $this->email]);
        $reset->created_at = Carbon::now();
        $reset->token = $token;
        $reset->save();
        // SEND EMAIL
    }
    
    public function comments() {
        return $this->hasMany('App\Models\Comment');
    }
}