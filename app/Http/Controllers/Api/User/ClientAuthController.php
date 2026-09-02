<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\UserRequest;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Support\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ClientAuthController extends ApiController
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Handle client registration.
     */
    public function register(UserRequest $request): JsonResponse
    {
        // Force the role to CLIENT
        $request->merge(['role_id' => User::ROLE_CLIENT]);

        // Determine country based on currency or default
        $currency = $request->header('Accept-Currency', 'LBP');
        $request->merge([
            'country_id' => Country::idForCurrency($currency, $request->header('Accept-Country')),
        ]);

        $user = $this->userRepository->add($request);
        $user->role_id = User::ROLE_CLIENT;
        $user = $user->generateActivationCode();
        $user->save();

        if (!$user->token) {
            $token = $user->createToken('API');
            $user->token = $token->plainTextToken;
            $user->save();
        }

        return $this->respondSuccess([
            'token' => $user->token,
            'user' => $user
        ]);
    }

    /**
     * Handle client login.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email'])
            ->where('deleted', 0)
            ->first();

        if (!$user) {
            return $this->respondError(__('api.user_not_found'));
        }

        // Check if the user is a client
        if (Hash::check($credentials['password'], $user->password)) {

            if (!$user->email_verified_at) {
                $user->generateActivationCode()->save();
                return $this->respondError(__('api.please_verify_your_account'));
            }

            if (!$user->token) {
                $token = $user->createToken('API');
                $user->token = $token->plainTextToken;
                $user->save();
            }

            return $this->respondSuccess([
                'token' => $user->token,
                'user' => $user
            ]);
        }

        return $this->respondError(__('api.username_or_password_invalid'));
    }
}
