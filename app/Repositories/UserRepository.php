<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserRepository
{

    public function add(Request $request): User
    {
        $user = null;
        if ($request->get('email')){
            $user = User::where('email',$request->get('email'))->where('deleted',1)->first();
        }
        if(!$user){
            $user = new User($request->all());
        }

        $country = auth()->check() ? auth()->user()->country_id : User::COUNTRY_LB;

        if($request->get('country_id')) {
            if (is_array($request->get('country_id'))) {
                $country = $request->get('country_id')['value'];
            } else {
                $country = $request->get('country_id');
            }
        }
        $user->country_id = (int)$country;
        $user->deleted = 0;
        if (!$request->get('email')){
            generateEmail:
            $email = generateRandomString(5, true). '@'. generateRandomString(4) . '.com';
            if (User::query()->where('email', $email)->exists())
                goto generateEmail;
            $user->email = $email;
        }
        $user->save();
        return $user;
    }

    public function update(Request $request, User $user)
    {
        $user->update($request->except(['password','country_id']));
        if ($request->get('country_id'))
            // $user->update(['country_id' => $request->country_id['value']]);
            $user->update(['country_id' => $request->country_id]);
            if ($request->get('password'))
            $user->update(['password' => $request->get('password')]);

        return $user;
    }

    public function getUsers(Request $request): LengthAwarePaginator
    {
        if (auth()->check()) {
            $countryId = auth()->user()->country_id;
        } else {
            $countryId = User::COUNTRY_LB;
        }
        $users = User::query()->with(['wallet'])->where('country_id', $countryId);

        if ($roles = $request->get('role'))
            $users->whereIn('role_id', $roles);

        if ($type = $request->get('type'))
            $users->where('role_id', $type);


        if ($search = $request->get('search'))
            $users->where(function ($query) use ($search){
                $query->where('name', 'LIKE', "%$search%")
                    ->orWhere('email', 'LIKE', "%$search%");
            });

        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(User::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $users->orderBy($field, $direction);
            }else{
                $users->orderByDesc('id');
            }
        }else{
            $users->orderByDesc('id');
        }

        return $users->paginate(10);
    }

}
