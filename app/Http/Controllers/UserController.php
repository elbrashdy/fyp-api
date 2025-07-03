<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('perPage', 10);

        $users = User::orderBy('created_at', 'desc')
            ->paginate($perPage);
        return $this->success($users, 'Users List!');
    }


    public function store(Request $request): JsonResponse
    {

        $this->validate($request, [
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed',
        ]);

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        return $this->success($user, 'Register successfull');
    }
}
