<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:128'],
            'last_name'  => ['required', 'string', 'min:2', 'max:128'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'   => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $validated['email'],
            'password'   => $validated['password'],
            'role'       => 'user',
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registrácia prebehla úspešne.',
            'user'    => $user,
            'token'   => $token,
        ], Response::HTTP_CREATED);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Nesprávny email alebo heslo.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Prihlásenie bolo úspešné.',
            'user'    => $user,
            'token'   => $token,
        ], Response::HTTP_OK);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user'            => $request->user()->load('profilePhoto'),
            'active_sessions' => $request->user()->tokens()->count(),
        ], Response::HTTP_OK);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Používateľ bol odhlásený z aktuálneho zariadenia.',
        ], Response::HTTP_OK);
    }

    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Používateľ bol odhlásený zo všetkých zariadení.',
        ], Response::HTTP_OK);
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password'  => ['required', 'string'],
            'password'          => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        if (!Hash::check($validated['current_password'], $request->user()->password)) {
            return response()->json([
                'message' => 'Aktuálne heslo je nesprávne.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        return response()->json([
            'message' => 'Heslo bolo úspešne zmenené.',
        ], Response::HTTP_OK);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'first_name'    => ['sometimes', 'string', 'min:2', 'max:128'],
            'last_name'     => ['sometimes', 'string', 'min:2', 'max:128'],
            'premium_until' => ['sometimes', 'nullable', 'date'],
        ]);

        $request->user()->update($validated);

        return response()->json([
            'message' => 'Profil bol úspešne aktualizovaný.',
            'user'    => $request->user()->fresh(),
        ], Response::HTTP_OK);
    }

    public function storeProfilePhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:3072'],
        ]);

        $user = $request->user();
        $file = $request->file('photo');
        $folder = "profile-photos/{$user->id}";
        $storedName = Str::ulid() . '.' . $file->getClientOriginalExtension();
        $path = null;

        DB::beginTransaction();

        try {
            $path = $file->storeAs($folder, $storedName, 'public');

            $attachment = $user->profilePhoto()->create([
                'public_id' => (string) Str::ulid(),
                'collection' => 'profile-photo',
                'visibility' => 'public',
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $storedName,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);

            $oldPhoto = $user->profilePhoto()->where('id', '!=', $attachment->id)->first();

            if ($oldPhoto) {
                Storage::disk('public')->delete($oldPhoto->path);
                $oldPhoto->delete();
            }

            DB::commit();

            return response()->json([
                'message' => 'Profilová fotka bola úspešne nahraná.',
                'url' => Storage::disk('public')->url($path),
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($path) {
                Storage::disk('public')->delete($path);
            }

            return response()->json([
                'message' => 'Nahrávanie profilovej fotky zlyhalo.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroyProfilePhoto(Request $request)
    {
        $user = $request->user();
        $photo = $user->profilePhoto;

        if (!$photo) {
            return response()->json([
                'message' => 'Používateľ nemá profilovú fotku.',
            ], Response::HTTP_NOT_FOUND);
        }

        Storage::disk($photo->disk)->delete($photo->path);
        $photo->delete();

        return response()->json([
            'message' => 'Profilová fotka bola odstránená.',
        ], Response::HTTP_OK);
    }
}
