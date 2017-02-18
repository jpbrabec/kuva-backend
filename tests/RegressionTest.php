<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Photo;


class RegressionTest extends TestCase
{
  use DatabaseTransactions;

	//Test uploading a photo with invalid coordinates
	public function testInvalidCoordinateUpload() {
    	$createUser = factory(App\Models\User::class)->create([
    	  'name' => 'TestUser',
    	  'email' => 'test@example.com',
    	]);
    	$response = $this->call('POST','/api/user/auth',['loginfield' => 'test@example.com', 'password' => 'secret']);
    	$token = json_decode($response->getContent(),true)['token'];
    	$user = User::where('email','test@example.com')->first();

      //Try to upload a photo with bad url
      $response = $this->call('POST','api/user/photos/create?token='.$token,[
        'lng' => 'NotANumber',
        'lat' => 'NotANumber'
      ]);
    	$data = json_decode($response->getContent(),true);

      assert(in_array('The lat must be a number.',$data));
      assert(in_array('The lng must be a number.',$data));
    }

    //Test posting a comment which is too long
  	public function testInvalidCommentLength() {
      	$createUser = factory(App\Models\User::class)->create([
      	  'name' => 'TestUser',
      	  'email' => 'test@example.com',
      	]);
      	$response = $this->call('POST','/api/user/auth',['loginfield' => 'test@example.com', 'password' => 'secret']);
      	$token = json_decode($response->getContent(),true)['token'];
      	$user = User::where('email','test@example.com')->first();

        //Create sample photo
        $photo = new Photo;
        $photo->user_id = $user->id;
        $photo->caption = "A Real Dank Photo";
        $photo->lat = "40.424899";
        $photo->lng = "-86.909189";
        $photo->save();

        //Try to upload a comment that is too long
        $response = $this->call('POST','api/user/photos/comment/'.$photo->id.'?token='.$token,[
          'text' => 'This comment is really really really really really '.
          'really really really really really really really really really '.
          'really really really really really really really really really '.
          'really really really really really really really really long.',
        ]);
      	$data = json_decode($response->getContent(),true);
        assert(in_array('The text must be between 1 and 200 characters.',$data));
      }
}
