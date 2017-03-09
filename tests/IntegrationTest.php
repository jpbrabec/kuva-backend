<?php

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Photo;
use App\Models\Report;
use App\Models\Like;

class IntegrationTest extends TestCase {
	use DatabaseTransactions;

	//Test Database -> PhotoController
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

	//Test Database -> PhotoController Reports
	public function testPhotoControllerProfiles() {
			$createUser = factory(App\Models\User::class)->create([
				'name' => 'TestUser',
				'email' => 'test@example.com',
			]);
			$response = $this->call('POST','/api/user/auth',['loginfield' => 'test@example.com', 'password' => 'secret']);
			$token = json_decode($response->getContent(),true)['token'];
			$user = User::where('email','test@example.com')->first();

    	$photo = new Photo;
    	$photo->user_id = $user->id;
    	$photo->caption = "Integration Test Photo 2";
    	$photo->lat = "40.424899";
    	$photo->lng = "-86.909189";
    	$photo->save();

			$report = new Report;
			$report->photo_id = $photo->id;
			$report->message = "This offends me";
			$report->token = "1234ABCD";
			$report->save();

			$this->seeInDatabase('photos',['caption' => 'Integration Test Photo 2']);
			$this->seeInDatabase('users',['email' => 'test@example.com', 'name' => 'TestUser']);
			$this->seeInDatabase('photo_reports',['photo_id' => $photo->id]);
	}

	//Test Database -> PhotoController Activity Feed
	public function testPhotoControllerActivity() {
			$createUser = factory(App\Models\User::class)->create([
				'name' => 'TestUser',
				'email' => 'test@example.com',
			]);
			$response = $this->call('POST','/api/user/auth',['loginfield' => 'test@example.com', 'password' => 'secret']);
			$token = json_decode($response->getContent(),true)['token'];
			$user = User::where('email','test@example.com')->first();

			$photo = new Photo;
			$photo->user_id = $user->id;
			$photo->caption = "Integration Test Photo 2";
			$photo->lat = "40.424899";
			$photo->lng = "-86.909189";
			$photo->save();

			$like = new Like;
			$like->user_id = $user->id;
			$like->photo_id = $photo->id;
			$like->liked = 1;
			$like->save();

			$this->seeInDatabase('photos',['caption' => 'Integration Test Photo 2']);
			$this->seeInDatabase('users',['email' => 'test@example.com', 'name' => 'TestUser']);
			$this->seeInDatabase('likes',['photo_id' => $photo->id]);

			$this->get('api/user/newsfeed?lat=40.424899&lng=-86.909189&token='.$token)
			->seeJson(['liked' => 1, 'photo_id' => $photo->id]);
	}

	//Test Database -> JWTMiddleware and JWTMiddleware -> AuthController
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

	//Test Database -> AuthController
	public function testDatabaseToAuthController() {
		//Try registering a user with auth controller
		$this->post('/api/user/register',['name' => 'TestUser', 'email' => 'test@example.com', 'password' => 'password123'])
			->seeJson(['message' => 'success']);

		//Ensure user appears in the database
		$this->seeInDatabase('users',['email' => 'test@example.com']);
	}

	//Test JWTMiddleware -> PhotoController
	public function testJWTMiddlewareToController() {
		$createUser = factory(App\Models\User::class)->create([
			'name' => 'TestUser',
			'email' => 'test@example.com',
		]);
		$response = $this->call('POST','/api/user/auth',['loginfield' => 'test@example.com', 'password' => 'secret']);
		$token = json_decode($response->getContent(),true)['token'];
		$user = User::where('email','test@example.com')->first();

		//Upload fake photo for testing
		$photo = new Photo;
		$photo->user_id = $user->id;
		$photo->caption = "Integration Test Photo";
		$photo->lat = "40.424899";
		$photo->lng = "-86.909189";
		$photo->save();

		//Now attempt to hit photo controller without a token
		$this->get('api/user/photos/feed?lat=40.424899&lng=-86.909189')
		->seeJson(['error' => 'token_not_provided']);

		//Now attempt to hit photo controller with the real token
		$this->get('api/user/photos/feed?lat=40.424899&lng=-86.909189',['HTTP_Authorization' => 'Bearer: '.$token])
		->seeJson(['distance' => 0, 'id' => $photo->id]);
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
    		'message',
    		'photos' => [
    			[
    				'id',
    				'user_id',
    				'caption',
    				'lat',
    				'lng',
    				'created_at',
    				'updated_at'
    			]
    		]
    	]);
	}
}
