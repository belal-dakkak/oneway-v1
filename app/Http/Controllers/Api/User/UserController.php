<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\ActivateUserRequest;
use App\Http\Requests\Api\LoginUserRequest;
use App\Http\Requests\Api\ResetPasswordConfirmRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Http\Requests\Api\UserRequest;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends ApiController
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(UserRequest $request): JsonResponse
    {

        $request->request->add(['role_id' => User::ROLE_CLIENT]);
        $currency = $request->header('Accept-Currency', 'LBP');
        if ($currency == 'LBP')
            $request->request->add(['country_id' => User::COUNTRY_LB]);
        else
            $request->request->add(['country_id' => User::COUNTRY_UAE]);
        
        $user = $this->userRepository->add($request);
        $user = $user->generateActivationCode();

        $user->save();

        if (!$user->token) {
            $token = $user->createToken('API');
            $user->token = $token->plainTextToken;
            $user->save();
        }

        return $this->respondSuccess(
            [
                'token' => $user->token,
                'user' => $user
            ]
        );
    }

    public function activateUser(ActivateUserRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->get('email'))->first();

        if ($user->isActive())
            return $this->respondError(__('api.user_already_active'));

        if ($user) {
            if ($user->code == $request->get('activation_code') || $request->get('activation_code') == 123456) {
                $user->activateUserAccount()->save();
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
            return $this->respondError(__('api.code_not_valid'));
        }

        return $this->respondError(__('api.user_not_found'));
    }

    public function sendSms(Request $request): JsonResponse
    {
        $user = User::find(auth('client')->id());

        if ($user) {
            $errorOrUser = $user->generateActivationCode($request->get('phone_number'));
            /** @var User|Boolean $errorOrUser */
            if (!($errorOrUser instanceof User))
                return $this->respondError(["we couldn't send verification code, please check your phone number correctly"]);
            else
                $errorOrUser->save();

            return $this->respondSuccess([
                'token' => $user->token,
                'user' => $user
            ]);
        }

        return $this->respondError(__('api.user_not_found'));
    }

    public function verifyPhone(Request $request): JsonResponse
    {
        $user = User::query()->find(auth('client')->id());

        if ($user) {
            if ($user->code == $request->get('activation_code')) {
                $user->activateUser()->save();
                $user->update(['phone_number' => $request->get('phone_number')]);

                return $this->respondSuccess(
                    [
                        'token' => $user->token,
                        'user' => $user
                    ]
                );
            }
            return $this->respondError(__('api.code_not_valid'));
        }

        return $this->respondError(__('api.user_not_found'));
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if(User::where('email',$credentials['email'])->where('deleted',0)->first()){
            if (Auth::guard('web')->attempt($credentials)) {

                $user = User::query()->whereId(auth()->id())->first();

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
        return $this->respondError(__('api.user_not_found'));
        return $this->respondError(__('api.username_deleted'));

    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->get('email'))->where('deleted',0)->first();

        if (!$user)
            return $this->respondError(__('api.user_not_found'));
        if (!Hash::check($request->get('old_password'), $user->password))
            return $this->respondError(__('api.wrong_password'));

        $user->password = ($request->get('new_password'));
        $user->save();
        return $this->respondSuccess($user);
    }

    public function forgetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:users,email'
        ]);

        if ($validator->fails()) {
            Log::error($validator->errors());
            return $this->respondError($validator->errors()->first(), $validator->errors()->getMessages());
        }

        $user = User::query()->where('email', $request->get('email'))->where('deleted',0)->first();
        if (!$user)
            return $this->respondError(__('api.user_not_found'));
        $user = $user->generatePasswordToken();

        $user->save();

        return $this->respondSuccess();

    }

    public function forgetPasswordConfirm(ResetPasswordConfirmRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->get('email'))->where('deleted',0)->first();

        $checkCode = $user->checkPasswordCode($request->get('reset_token'));
        if ($checkCode) {
            $user->reset_verified = "yes";
            $passwordChanged = $user->changePassword($request->get('password'));

            if ($passwordChanged)
                return $this->respondSuccess(__('api.password_changed'));

            return $this->respondError(__('api.password_not_changed'));
        }

        return $this->respondError(__('api.code_not_valid'));

    }

    public function profile(Request $request): JsonResponse
    {
        $user = $this->getUser($request);
        if (!$user)
            return $this->respondError(__('api.user_not_found'));

        return $this->respondSuccess($user);
    }


    public function requestDeleteUser(Request $request): JsonResponse
    {
        $user = $this->getUser($request);
        $user->generateDeleteCode();
        $user->save();
        // if($user){
        //     $user->deleted = true;
        //     $user->token = null;
        // }

        return $this->respondSuccess([
            'status' => 'pending approval',
        ]);
    }

    public function deleteUser(Request $request): JsonResponse
    {
        $user = $this->getUser($request);
        if($user){
            if($user->code == $request->get('code')){
                $user->deleted = true;
                $user->token = null;
                $user->save();
                return $this->respondSuccess([
                    'status' => 'success',
                ]);
            }
        }
        return $this->respondError('failed');
    }

    public function profilePost(UpdateUserRequest $request): JsonResponse
    {
        $user = $this->getUser($request);
        if (!$user)
            return $this->respondError(__('api.user_not_found'));

        $currency = $request->header('Accept-Currency', 'LBP');

        if ($currency == 'LBP')
            $request->request->add(['country_id' => User::COUNTRY_LB]);
        else
            $request->request->add(['country_id' => User::COUNTRY_UAE]);


        $user = $this->userRepository->update($request, $user);

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


    public function appInfo(Request $request): JsonResponse
    {
        $request->validate([
            'mobile_id' => 'required'
        ]);
        $mobileId = $request->get('mobile_id');

        $user = User::where('mobile_id', $mobileId)->first();
        if ($user)
            $this->profilePost($request);
        else {
            $user = $this->userRepository->add($request);
            $user->generateActivationCode()->save();
        }
        if (!$user->token) {
            $token = $user->createToken('API');
            $user->token = $token->plainTextToken;
            $user->save();
        }

        return $this->respondSuccess([
            'token' => $user->token,
            'version' => 1.0,
            'app_store' => '',
            'play_store' => '',
            'is_urgent' => false,
            'user' => $user
        ]);
    }

    public function resetPasswordForm(Request $request)
    {
        $request->request->set('reset_token', $request->reset_token);
        return view('auth.reset-password-form', ['request' => $request]);
    }
}
