<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use App\Models\Like;

class Photo extends Model
{
	protected $hidden = ['deleted_at'];

	public function user() {
		return $this->belongsTo('App\Models\User');
	}

	public function comments() {
		return $this->hasMany('App\Models\Comment');
	}

	public function likes() {
		return $this->hasMany('App\Models\Like');
	}

	public function getAllCommentsAttribute() {
		return \App\Models\Comment::where('photo_id', $this->id);
	}

	public function getNumLikesAttribute() {
        $likes = Like::where('photo_id',$this->id)->where('liked', 1)->get();
        return $likes->count();
    }

	public function getNumCommentsAttribute() {
        $likes = Comment::where('photo_id',$this->id)->get();
        return $likes->count();
    }

	//Raw query to find distance
	public static function getByDistance($lat, $lng,$distance) {
		$results = DB::select(DB::raw('SELECT id, ( 3959 * acos( cos( radians(' . $lat . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $lng . ') ) + sin( radians(' . $lat .') ) * sin( radians(lat) ) ) ) AS distance FROM photos HAVING distance < ' . $distance . ' ORDER BY distance') );
 		return $results;
	}
	public static function getByDistance2($latitude, $longitude, $radius) {
		$products = Photo::select('photos.*')
            ->join('users', function($join)
            {
                $join->on('photos.user_id', '=', 'users.id');
            })
            ->selectRaw('( 6371 * acos( cos( radians(?) ) *
                               cos( radians( lat ) )
                               * cos( radians( lng ) - radians(?)
                               ) + sin( radians(?) ) *
                               sin( radians( lat ) ) )
                             ) AS distance', [$latitude, $longitude, $latitude])
            ->havingRaw("distance < ?", [$radius])
            ->get();
           return $products;
	}
}
