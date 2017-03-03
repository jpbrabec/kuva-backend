<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PasswordReset;
use Auth;
use Hash;
use Carbon\Carbon;

class AuthController extends Controller
{

    /**
     * Create a user
     *
     * @param  Request  $request
     * @return Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:users|max:127',
            'password' => 'required|min:6',
            'email'    => 'required|email|unique:users',
        ]);
        if ($validator->fails()) {
            return ['message' => 'validation', 'errors' => $validator->errors()];
        }

        $user = new User;
        $user->name = $request->name;
        $user->password = Hash::make($request->password);
        $user->email = $request->email;
        $user->save();
        $user->postSignupActions();
        $token = $user->getToken();
        return ['message' => 'success', 'token' => $token];
    }

    /**
     * Authenticate a user
     *
     * @param  Request  $request
     * @return Response
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->only('loginfield', 'password');
        $validator = Validator::make($credentials, [
            'loginfield' => 'required|max:127',
            'password'   => 'required',
        ]);
        if ($validator->fails()) {
            return ['message' => 'validation', 'errors' => $validator->errors()];
        }
        $loginfield = $request->loginfield;
        $field = filter_var($loginfield, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
        if (Auth::attempt([$field => $loginfield, 'password' => $request->password])) {
            $token = Auth::user()->getToken();
            return ['message' => 'success', 'token' => $token];
        }
        return response()->json(['message' => 'invalid_credentials'], 401);
    }

    public function sendPasswordReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);
        if ($validator->fails()) {
            return ['message' => 'error', 'errors' => $validator->errors()->all()];
        }
        $user = User::where('email', $request->email)->first();
        $user->sendPasswordResetEmail();

        return ['message' => 'success'];
    }

    public function performPasswordReset(Request $request)
    {
        $token = $request->token;
        $password = $request->password;

        $reset = PasswordReset::where('token', $token)->first();
        if(count($reset) < 1) {
          return ['message' => 'invalid_token'];
        }
        
        if (Carbon::parse($reset->created_at)->addHour(48)->lte(Carbon::now())) {
            return ['message' => 'expired'];
        }

        $user = User::where('email', $reset->email)->first();
        $user->password = Hash::make($password);
        $user->save();

        $reset->delete();

        return ['message' => 'success'];
    }
}
