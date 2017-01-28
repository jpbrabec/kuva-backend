<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\User;
use Auth;
use JWTAuth;
use Hash;
use Carbon\Carbon;
use App\Models\Photo;
use App\Models\Comment;
use App\Models\Like;
use DB;

class PhotosController extends Controller
{

    /**
     * Create a photo
     *
     * @param  Request  $request
     * @return Response
     */
    public function create(Request $request)
    {    
        $validator = \Validator::make($request->all(), [
            'photo' => 'required|image',
        ]);
        if ($validator->fails()) {
            return $validator->errors()->all();
        }
        $photo = new Photo;
        $photo->user_id = Auth::user()->id;
        $photo->caption = str_random(40); // replace later
        $photo->lat = $request->lat;
        $photo->lng = $request->lng;
        $photo->save();

        $image = $request->file('photo');
        $destinationPath = storage_path('app/public') . '/uploads';
        $name =  $photo->id . '.' . $image->getClientOriginalExtension();
        if(!$image->move($destinationPath, $name)) {
            return $this->errors(['message' => 'Error saving the file.', 'code' => 400]);
        }
        return ['message' => 'success'];
    }

    public function delete(Request $request, Photo $photo) {
        $photo->delete();
        return ['message' => 'success'];
    }

    public function comment(Request $request, Photo $photo) {
        $comment = new Comment;
        $comment->text = $request->text;
        $comment->photo_id = $photo->id;
        $comment->user_id = Auth::user()->id;
        $comment->save();
        return ['message' => 'success'];
    }

    public function like(Request $request, Photo $photo) {
        $like = Like::firstOrNew(['user_id' => Auth::user()->id, 'photo_id' => $photo->id]);
        $like->liked = $request->liked;
        $like->photo_id = $photo->id;
        $like->user_id = Auth::user()->id;
        $like->save();
        return ['message' => 'success'];        
    }

    public function feed() {
        var_dump($this->getByDistance(40.423,86.921,50));
    }

    public static function getByDistance($latitude, $longitude, $radius) {
        //http://stackoverflow.com/questions/34010916/how-to-show-stores-with-nearest-long-lat-values-using-search-function-laravel
        // $photos = Photo::selectRaw('( 6371 * acos( cos( radians(?) ) *
        //                        cos( radians( lat ) )
        //                        * cos( radians( lng ) - radians(?)
        //                        ) + sin( radians(?) ) *
        //                        sin( radians( lat ) ) )
        //                      ) AS distance', [$latitude, $longitude, $latitude])
        //     ->whereRaw("distance < ?", [$radius])
        //     ->get();
        // $results = DB::select(DB::raw('SELECT id, ( 3959 * acos( cos( radians(' . $latitude . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude .') ) * sin( radians(lat) ) ) ) AS distance FROM photos WHERE distance < ' . $radius . ' ORDER BY distance') );
        return $photos;
    }
}