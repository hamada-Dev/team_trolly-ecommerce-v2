<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Store;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(Request $request)
    {
        if(!file_exists(storage_path() . "/installed"))
        {
            header('location:install');
            die;
        }
    }
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'store_name' => ['required', 'string', 'max:255'],
        ]);
        $superAdmin = User::where('type','super admin')->first();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'profile_image' => 'uploads/profile/avatar.png',
            'type' => 'admin',
            'email_verified_at' => date('Y-m-d H:i:s'),
            'password' => Hash::make($request->password),
            'mobile' => '',
            'default_language' => $superAdmin->default_language ?? 'en',
            'created_by' => 1,
            'theme_id' => 'grocery',
        ]);

        $slug = User::slugs($request->store_name);

        $store = Store::create([
                'name' => $request->store_name,
                'email' => $request->email,
                'theme_id' => $user->theme_id,
                'slug' => $slug,
                'created_by' => $user->id,
                'default_language' => $superAdmin->default_language ?? 'en'
            ]);

        $user->current_store = $store->id;
        $user->save();
        event(new Registered($user));
        $role_r = Role::where('name', 'admin')->first();
        $user->addRole($role_r);

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
