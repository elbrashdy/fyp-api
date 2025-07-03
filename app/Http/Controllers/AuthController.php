<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if(!$user || !Hash::check($request->input('password'), $user->password)) {
            return $this->error("Wrong Credentials!", 401);
        }
        $token = $user->createToken('token')->plainTextToken;


        return $this->success([
            'user' => $user,
            'token' => $token
        ], 'Login successfull');
    }


    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();
        return $this->success([], 'Logout successfull');
    }

    /**
     * @throws ValidationException
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->validate($request, [
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        // Check current password
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return $this->error('Password is incorrect!', 401);
        }

        // Update password
        $user->password = Hash::make($request->input('password'));
        $user->save();

        return $this->success([], 'Password changed successfull');
    }
}
