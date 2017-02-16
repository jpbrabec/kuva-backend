<?php

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Photo;

class IntegrationTest extends TestCase {
	use DatabaseTransactions;

	public function testPhotoController() {
    	$createUser = factory(App\Models\User::class)->create([
    	  'name' => 'TestUser',
    	  'email' => 'test@example.com',
    	]);
    	$response = $this->call('POST','/api/user/auth',['loginfield' => 'test@example.com', 'password' => 'secret']);
    	$token = json_decode($response->getContent(),true)['token'];
    	$user = User::where('email','test@example.com')->first();

    	$photo = new Photo;
    	$photo->user_id = $user->id;
    	$photo->caption = "Integration Test Photo";
    	$photo->lat = "40.424899";
    	$photo->lng = "-86.909189";
    	$photo->save();

    	$this->seeInDatabase('photos',['caption' => 'Integration Test Photo']);
    	$this->seeInDatabase('users',['email' => 'test@example.com', 'name' => 'TestUser']);
	}

	public function testJWTMiddleware() {
		$createUser = factory(App\Models\User::class)->create([
    	  'name' => 'TestUser',
    	  'email' => 'test@example.com',
    	]);
    	$response = $this->call('POST','/api/user/auth',['loginfield' => 'test@example.com', 'password' => 'secret']);
    	$this->seeInDatabase('users',['email' => 'test@example.com', 'name' => 'TestUser']);
    	$response = $this->call('POST','/api/user/auth',['loginfield' => 'test@example.com', 'password' => 'secret']);
		$this->seeJsonStructure([
			'message',
			'token'
		]);
	}

	public function testJWTandPhotos() {
		$user = factory(App\Models\User::class)->create([
      		'name' => 'TestUser',
      		'email' => 'test@example.com',
    	]);
    	$response = $this->call('POST','/api/user/auth',['loginfield' => 'test@example.com', 'password' => 'secret']);
    	$token = json_decode($response->getContent(),true)['token'];

    	$photo = new Photo;
    	$photo->user_id = $user->id;
    	$photo->caption = "Integration Test Photo";
    	$photo->lat = "40.424899";
    	$photo->lng = "-86.909189";
    	$photo->save();

    	$response = $this->call('GET', '/api/user/photos?token='.$token, [], [], [], []);
    	$this->seeJsonStructure([
    		[
    			'id',
    			'user_id',
    			'caption',
    			'lat',
    			'lng',
    			'created_at',
    			'updated_at'
    		]
    	]);
	}
}
