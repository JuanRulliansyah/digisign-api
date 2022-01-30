<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Libraries\SSO;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login','register']]);
    }

    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function register(Request $request)
    {
        //validate incoming request 
        $this->validate($request, [
            'username' => 'required|string|unique:users',
            'password' => 'required|confirmed',
        ]);

        try 
        {
            $user = new User;
            $user->name = $request->input('name');
            $user->username= $request->input('username');
            $user->email = $request->input('email');
            $user->password = app('hash')->make($request->input('password'));
            $user->access_group_id = 1;
            $user->save();

            return response()->json( [
                        'entity' => 'users', 
                        'action' => 'create', 
                        'result' => 'success'
            ], 201);

        } 
        catch (\Exception $e) 
        {
            return response()->json( [
                       'entity' => 'users', 
                       'action' => 'create', 
                       'result' => 'failed'
            ], 409);
        }
    }
	
     /**
     * Get a JWT via given credentials.
     *
     * @param  Request  $request
     * @return Response
     */	 
    public function login(Request $request)
    {
          //validate incoming request 
        $this->validate($request, [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Checking
        $SSO = new SSO;
        $SSO_CHECK = $SSO->SSO_CHECK($request->input('username'), $request->input('password'));
        if(!$SSO_CHECK) {
            return response()->json(['message' => 'Username / Password Did not Matched']);
        }


        $credentials = $request->only(['username', 'password']);

        if (! $token = Auth::attempt($credentials)) {			
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Preparing data for
        $result = [
            'refresh' => Auth()->refresh(),
            'access' => $token
        ];

        return response()->json($result);
    }
	
     /**
     * Get user details.
     *
     * @param  Request  $request
     * @return Response
     */	 	
    public function me()
    {
        $user = User::find(1);


        $data = array(
            "test" => $user->getRoleNames()
        );
        
        // return response()->json(auth()->user());
        return response()->json($data);
    }
}