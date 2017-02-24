<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Photo;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Report;
use App\Models\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PhotoTest extends TestCase
{
  use DatabaseTransactions;

  //Mock a sample user and get a token
  public function getAuthToken()
  {
    //Register a sample user
    $user = factory(App\Models\User::class)->create([
      'name' => 'TestUser',
      'email' => 'test@example.com',
    ]);
    $response = $this->call('POST','/api/user/auth',['loginfield' => 'test@example.com', 'password' => 'secret']);
    return json_decode($response->getContent(),true)['token'];
  }


  public function setUp() {
    parent::setUp();
    $this->token = PhotoTest::getAuthToken();
    $this->user = User::where('email','test@example.com')->first();
    //Create sample photo
    $photo = new Photo;
    $photo->user_id = $this->user->id;
    $photo->caption = "A Real Dank Photo";
    $photo->lat = "40.424899";
    $photo->lng = "-86.909189";
    $photo->save();
    $this->photo = $photo;
    }

    public function testPhotoFeed() {
      $this->seeInDatabase('photos',['caption' => 'A Real Dank Photo']);
      //Verify that you can find the photo from the feed
      $this->actingAs($this->user)
      ->get('api/user/photos/feed?lat=40.424899&lng=-86.909189',['HTTP_Authorization' => 'Bearer: '.$this->token])
      ->seeJson(['distance' => 0, 'id' => $this->photo->id]);
    }

    public function testPhotoComment() {
      $this->actingAs($this->user)
      ->post('api/user/photos/comment/'.$this->photo->id,[
        'text' => 'That is one dank photo',
      ],['HTTP_Authorization' => 'Bearer: '.$this->token])
      ->seeJson(['message' => 'success']);

      $this->seeInDatabase('comments',['text' => 'That is one dank photo']);

    }

    public function testPhotoLike() {
      $this->actingAs($this->user)
      ->post('api/user/photos/like/'.$this->photo->id,[
        'liked' => 1,
      ],['HTTP_Authorization' => 'Bearer: '.$this->token])
      ->seeJson(['message' => 'success']);

      $this->seeInDatabase('likes',['liked' => 1, 'photo_id' => $this->photo->id]);

    }

    public function testPhotoReport() {
      $this->actingAs($this->user)
      ->post('api/user/photos/'.$this->photo->id.'/report',[
        'message' => "This photo is just too incredible for me to handle",
      ],['HTTP_Authorization' => 'Bearer: '.$this->token])
      ->seeJson(['message' => 'success']);

      $this->seeInDatabase('photo_reports',['photo_id' => $this->photo->id]);
    }

    public function testPhotoDelete() {

      $report = new Report;
      $report->photo_id = $this->photo->id;
      $report->message = "Offensive";
      $report->token = "123ABC";
      $report->save();
      $this->seeInDatabase('photo_reports',['photo_id' => $this->photo->id]);

      //Try to delete the offensive photo
      $this->get('api/user/photos/report/'.$report->token)
      ->seeJson(['message' => 'success']);
    }

    public function testUserFeed() {
      //Create sample like & comment
      $like = new Like;
      $like->user_id = $this->user->id;
      $like->photo_id = $this->photo->id;
      $like->liked = 1;
      $like->save();
      $this->seeInDatabase('likes',['liked' => 1, 'photo_id' => $this->photo->id]);

      $comment = new Comment;
      $comment->user_id = $this->user->id;
      $comment->photo_id = $this->photo->id;
      $comment->text = "Comment A";
      $comment->save();
      $comment = new Comment;
      $comment->user_id = $this->user->id;
      $comment->photo_id = $this->photo->id;
      $comment->text = "Comment B";
      $comment->save();
      $this->seeInDatabase('comments',['text' => "Comment A", 'photo_id' => $this->photo->id]);
      $this->seeInDatabase('comments',['text' => "Comment B", 'photo_id' => $this->photo->id]);

      $this->actingAs($this->user)
      ->get('api/user/newsfeed',['HTTP_Authorization' => 'Bearer: '.$this->token])
      ->seeJsonStructure(['comments','likes'])->seeJson(['liked' => 1,'photo_id' => $this->photo->id, 'text' => "Comment A", 'text' => "Comment B"]);

    }

    // public function testPhotoUpload() {
    //   $file = Mockery::mock(UploadedFile::class, [
    //        'getClientOriginalName'      => 'foo',
    //        'getClientOriginalExtension' => 'jpg'
    //    ]);
    //
    //    $file->shouldReceive('move')
    //    ->once();
    //
    //   // Verify that you can upload a photo
    //   $this->actingAs($this->user)
    //   ->post('api/user/photos/create',[
    //     'photo' => $file,
    //     'caption' => 'DankPhoto',
    //     'lat' => '40.424899',
    //     'lng' => '-86.909189'
    //   ],['HTTP_Authorization' => 'Bearer: '.$this->token])
    //   ->seeJson(['distance' => 0, 'id' => $this->photo->id]);
    // }
  }
