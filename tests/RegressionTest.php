<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;

class RegressionTest extends TestCase
{
  use DatabaseTransactions;

	//Test Database -> PhotoController
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
}
