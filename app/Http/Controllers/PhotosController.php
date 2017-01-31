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
            'lat' => 'required',
            'lng' => 'required',
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
