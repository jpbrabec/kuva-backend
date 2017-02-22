<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Http\Request;
use Auth;
use App\Models\User;
use App\Models\Photo;
use App\Models\Comment;
use App\Models\Like;
use Image;
use JWTAuth;

//Photo Controller
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
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'caption' => 'required|string|max:255',
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
        $name = $photo->id . '.jpg';
        if(!$image->move($destinationPath, $name)) {
            return ['message' => 'Error saving the file.', 'code' => 400];
        }
        $img = Image::make($destinationPath . '/' . $name)->encode('jpg', 75)->save();
        return ['message' => 'success', 'photo_id' => $photo->id];
    }

    public function getPhoto(Request $request, Photo $photo) {
    	$photo = Photo::where('id',$photo->id)->with('comments.user')->with('likes.user')->with('user')->get()->toArray();
    	try {
    		$user = JWTAuth::parseToken()->authenticate();
    		$photo['user_liked'] = Like::where('user_id', $user->id)->first(['liked'])['liked'];
    	} catch (\Exception $e) {
    		return ['message' => 'invalid_photo'];
    	}
      	return $photo;
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
            'text' => 'required|between:1,200',
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
      $photos = (Photo::getByDistance2($request->lat, $request->lng, 200));
      foreach($photos as $photo) {
        $photo->numLikes = $photo->numLikes;
        $photo->numComments = $photo->numComments;
        $photo->likes = $photo->likes;
        $photo->comments = $photo->comments;
        foreach($photo->comments as $comment) {
            $comment->user = User::where('id', $comment->user_id)->get(['name']);
        }
      }

      //TODO- Filter these by popularity/time/etc
      return $photos;
    }

 	public function getProfile(Request $request, User $user) {
 		$photos = Photo::where('user_id', $user->id)->get();
        return ['message' => 'success', 'name' => $user->name, 'photos' => $photos];
 	}

    public function userPhotos() {
        $photos = Photo::where('user_id', Auth::user()->id)->get();
        return ['message' => 'success', 'photos' => $photos];
    }

    /**
     * Create a photo
     *
     * @param  Request  $request
     * @return Response
     */
    public function createProfilePhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        $name = str_random(5) . '.jpg';
        $image = $request->file('photo');
        $destinationPath = storage_path('app/public') . '/uploads/profile';
        if(!$image->move($destinationPath, $name)) {
            return ['message' => 'Error saving the file.', 'code' => 400];
        }
        $img = Image::make($destinationPath . '/' . $name)->encode('jpg', 75)->save();
        Auth::user()->profile_photo = $name;
        Auth::user()->save();
        return ['message' => 'success', 'photo' => $name];
    }
}