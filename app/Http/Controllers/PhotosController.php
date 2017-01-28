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
        $photo = new Photo;
        $photo->user_id = Auth::user()->id;
        $photo->caption = str_random(40); // replace later
        $photo->lat = "lat";
        $photo->lng = "lng";
        $photo->save();
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
}