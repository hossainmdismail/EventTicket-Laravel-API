<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiAuthController extends Controller
{
    function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string',
            'email'     => 'required|email',
            'password'  => 'required|min:8|max:16',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        //Data convert to object
        $data = [
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => bcrypt($request->password),
        ];

        DB::beginTransaction();
        try {
            $user = User::create($data);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            $user = null;
        }

        // Passed data && External error logic
        if ($user != null) {
            $token = $user->createToken('auth_token')->accessToken;

            return response()->json([
                'message' => 'User registered successfully',
                'token'   => $token,
                'status'  => 1,
            ], 200);
        } else {
            $member = User::where('email', $request->email)->first();
            if ($member) {
                return response()->json([
                    'message' => [
                        'message' => 'User Already exists',
                        'status'  => 0,
                    ]
                ], 422);
            }
            return response()->json([
                'message' => 'Internal server error',
                'status'  => 0,
            ], 500);
        }
    }

    function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => 'required|email',
            'password'  => 'required|min:8|max:16',
        ]);

        if ($validator->fails()) { //Form validation
            return response()->json($validator->messages(), 400);
        }

        if (auth()->attempt($validator->validate())) {
            $user  = auth()->user();
            $token = $user->createToken('auth_token')->accessToken;
            return response()->json([
                'message'   => 'Logged in successfully',
                'token'     => $token,
                'user'      => $user,
                'status'    => 1,
            ]);
        }
        return 'error';

        // return response()->json('Login');
    }
}
