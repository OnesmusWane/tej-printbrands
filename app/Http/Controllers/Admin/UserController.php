<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::whereIn('role', ['super_admin', 'admin', 'manager', 'staff'])->latest();
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%"));
        }
        return response()->json($query->get(['id', 'name', 'email', 'phone', 'role', 'permissions', 'created_at']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'email'         => ['required', 'email', 'unique:users,email'],
            'phone'         => ['nullable', 'string', 'max:40'],
            'role'          => ['required', 'in:super_admin,admin,manager,staff'],
            'password'      => ['required', 'min:8'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);
        $user = User::create(array_merge($data, [
            'is_admin' => true,
            'password' => bcrypt($data['password']),
        ]));
        return response()->json($user->only(['id', 'name', 'email', 'phone', 'role', 'permissions', 'created_at']), 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name'          => ['sometimes', 'string', 'max:120'],
            'email'         => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'phone'         => ['nullable', 'string', 'max:40'],
            'role'          => ['sometimes', 'in:super_admin,admin,manager,staff'],
            'password'      => ['nullable', 'min:8'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);
        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);
        return response()->json($user->fresh()->only(['id', 'name', 'email', 'phone', 'role', 'permissions', 'created_at']));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        abort_if($user->id === $request->user()->id, 403, 'Cannot delete your own account.');
        $user->delete();
        return response()->json(null, 204);
    }
}
