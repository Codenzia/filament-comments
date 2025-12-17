<?php

namespace Codenzia\FilamentComments\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $search = $request->get('search', '');
        $userModel = config('codenzia-comments.user_model', \App\Models\User::class);
        
        $users = $userModel::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar_url ?? null,
                ];
            });

        return response()->json($users);
    }
}
