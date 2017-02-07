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
use Log;

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
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lng' => 'required',
            'caption' => 'string|max:255',
            'photo' => 'required|image',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        $photo = new Photo;
        $photo->user_id = Auth::user()->id;
        $photo->caption = $request->caption;
        $photo->lat = $request->lat;
        $photo->lng = $request->lng;
        $photo->save();

        $image = $request->file('photo');
        $destinationPath = storage_path('app/public') . '/uploads';
        $name =  $photo->id . '.' . $image->getClientOriginalExtension();
        if(!$image->move($destinationPath, $name)) {
            return ['message' => 'Error saving the file.', 'code' => 400];
        }
        return ['message' => 'success'];
    }

    public function delete(Request $request, Photo $photo) {
        if($photo->user_id != Auth::user()->id) {
            return ['message' => 'authentication'];
        }
        $photo->delete();
        return ['message' => 'success'];
    }

    public function comment(Request $request, Photo $photo) {
        $validator = Validator::make($request->all(), [
            'text' => 'required',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        $comment = new Comment;
        $comment->text = $request->text;
        $comment->photo_id = $photo->id;
        $comment->user_id = Auth::user()->id;
        $comment->save();
        return ['message' => 'success'];
    }

    public function like(Request $request, Photo $photo) {
        $validator = Validator::make($request->all(), [
            'liked' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        $like = Like::firstOrNew(['user_id' => Auth::user()->id, 'photo_id' => $photo->id]);
        $like->liked = $request->liked;
        $like->photo_id = $photo->id;
        $like->user_id = Auth::user()->id;
        $like->save();
        return ['message' => 'success'];
    }

    public function feed(Request $request) {
      $validator = Validator::make($request->all(), [
          'lat' => 'required|numeric',
          'lng' => 'required|numeric',
      ]);

      if ($validator->fails()) {
          return $validator->errors()->all();
      }

      //TODO- Validate lat/lng more carefully since they go into a raw query
      $photosInRange = Photo::getByDistance($request->lat, $request->lng, 200); //TODO- What radius makes sense

      //TODO- Filter these by popularity/time/etc
      return $photosInRange;
    }

    public function userPhotos() {
        $photos = Photo::where('user_id', Auth::user()->id)->get();
        return $photos;
    }
}
