<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class Photo extends Model
{
	protected $hidden = ['deleted_at'];

	public function comments() {
		return $this->hasMany('App\Models\Comment');
	}

	public function likes() {
		return $this->hasMany('App\Models\Like');
	}

	//Raw query to find distance
	public static function getByDistance($lat, $lng,$distance) {
		$results = DB::select(DB::raw('SELECT id, ( 3959 * acos( cos( radians(' . $lat . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $lng . ') ) + sin( radians(' . $lat .') ) * sin( radians(lat) ) ) ) AS distance FROM photos HAVING distance < ' . $distance . ' ORDER BY distance') );
 		return $results;
	}
}
