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
}