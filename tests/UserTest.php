  <?php

  use Illuminate\Foundation\Testing\WithoutMiddleware;
  use Illuminate\Foundation\Testing\DatabaseMigrations;
  use Illuminate\Foundation\Testing\DatabaseTransactions;

  class UserTest extends TestCase
  {
    use DatabaseTransactions;


      public function testRegister()
      {
        //Test registration
        $this->post('/api/user/register',['name' => 'TestUser', 'email' => 'test@example.com', 'password' => 'password123'])
          ->seeJson(['message' => 'success']);

        $this->seeInDatabase('users',['email' => 'test@example.com']);

        //Test registration with invalid data
        $this->post('/api/user/register',['name' => 'TestUser', 'email' => 'test@notOkayEmail', 'password' => 'password123'])
          ->seeJson(['message' => 'validation']);

      }

      public function testLogin()
      {
        //Register a user
        $this->post('/api/user/register',['name' => 'TestUser', 'email' => 'test@example.com', 'password' => 'password123'])
          ->seeJson(['message' => 'success']);

        //Try Valid login
        $this->post('/api/user/auth',['loginfield' => 'test@example.com', 'password' => 'password123'])
          ->seeJson(['message' => 'success']);

        //Try Invalid Login
        $this->post('/api/user/auth',['loginfield' => 'test@example.com', 'password' => 'wrongPassword'])
          ->seeJson(['message' => 'invalid_credentials']);

        //Try Missing Field
        $this->post('/api/user/auth',['loginfield' => 'test@example.com'])
          ->seeJson(['message' => 'validation']);
      }
  }
