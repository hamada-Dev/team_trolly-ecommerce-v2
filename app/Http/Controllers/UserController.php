<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Plan;
use App\Models\Store;
use App\Models\Utility;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // if(\Auth::user()->can('Manage User'))
        // {
            $users = User::where('created_by','=',\Auth::user()->creatorId())->get();

            return view('users.index',compact('users'));

        // }
        // else{
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // if (auth()->user()->isAbleTo('Create User'))
        // {
            $user  = \Auth::user();
            $roles = Role::where('created_by', '=', $user->creatorId())->get()->pluck('name', 'id');
            return view('users.create',compact('roles'));
        // }
        // else
        // {
        //     return response()->json(['error' => __('Permission denied.')], 401);
        // }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // if (auth()->user()->isAbleTo('Create User'))
        // {
            $validator = \Validator::make(
                $request->all(),
                [
                    'email' => [
                        'required',
                        Rule::unique('users')->where(function ($query) {
                        return $query->where('created_by', \Auth::user()->id);
                        })
                    ],
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $user = \Auth::user();
            $creator = User::find($user->creatorId());
            $total_users = User::where('type', '!=', 'super admin')->where('type', '!=', 'admin')->where('created_by', '=', $user->creatorId())->count();
            // $plan = Plan::find($creator->plan);
            $plan = '5';

            // if ($total_users < $plan->max_users || $plan->max_users == -1)
            if ($total_users < 5 || $plan->max_users == -1)
            {

                $objUser    = \Auth::user();
                $role_r = Role::find($request->role);

                $user =  new User();
                $user->name =  $request['name'];
                $user->email =  $request['email'];
                $user->type = $role_r->name;
                $user->password = Hash::make($request['password']);
                $user->is_assign_store = $objUser->current_store;
                $user->language = $objUser->default_language ?? 'en';
                $user->default_language = $objUser->default_language ?? 'en';
                $user->created_by = \Auth::user()->creatorId();
                $user->email_verified_at = date("Y-m-d H:i:s");
                $user->current_store = $objUser->current_store;
                $user->plan_id = $objUser->plan;
                $user->save();

                $user->addRole($role_r);
                // webhook
                // if(!empty($user))
                // {
                //     $module = 'New User';
                //     $store = Store::find(getCurrentStore());
                //     $webhook =  Utility::webhook($module, $store->id);

                //     if ($webhook) {
                //         $parameter = json_encode($user);

                //         // 1 parameter is  URL , 2 parameter is data , 3 parameter is method
                //         $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                //         if ($status != true) {
                //             $msgs = 'Webhook call failed.';
                //         }
                //     }
                //     return redirect()->back()->with('success', __('User successfully created.' . (isset($msgs) ? '<br><span class="text-danger">' . $msgs . '</span>' : '')));
                // }
                return redirect()->back()->with('success', 'User successfully updated.');
            } else {
                return redirect()->back()->with('error', __('Your User limit is over, Please upgrade plan'));
            }
        // }
        // else{
        //     return response()->json(['error' => __('Permission denied.')], 401);
        // }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (auth()->user()->isAbleTo('Edit User'))
        {
            $user  = Admin::find($id);
            $roles = Role::where('created_by', '=', \Auth::user()->creatorId())->where('store_id',getCurrentStore())->get()->pluck('name', 'id');
            return view('users.edit', compact('user', 'roles'));
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id, Admin $admin)
    {
        if (auth()->user()->isAbleTo('Edit User'))
        {
            $validator = \Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'email' => ['required',
                                Rule::unique('admins')->where(function ($query)  use ($admin) {
                                return $query->whereNotIn('id',[$admin->id])->where('created_by',  \Auth::user()->creatorId())->where('current_store', \Auth::user()->current_store);
                            })
                    ],
                ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $user = Admin::findOrFail($id);

            $role          = Role::find($request->role);
            $input         = $request->all();
            $input['type'] = $role->name;
            $user->fill($input)->save();

            $user->assignRole($role);
            $roles[] = $request->role;
            $user->roles()->sync($roles);
            return redirect()->back()->with('success', 'User successfully updated.');
        }
        else{
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Admin $user)
    {
        if (auth()->user()->isAbleTo('Delete User'))
        {
            $user->delete();

            return redirect()->back()->with('success', 'User successfully deleted.');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function reset($id)
    {
        if (auth()->user()->isAbleTo('Reset Password'))
        {
            $Id        = \Crypt::decrypt($id);
            $user = Admin::find($Id);
            $employee = Admin::where('id', $Id)->first();

            return view('users.reset', compact('user', 'employee'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function updatePassword(Request $request, $id)
    {
        if (auth()->user()->isAbleTo('Reset Password'))
        {
            $validator = \Validator::make(
                $request->all(),
                [
                    'password' => 'required|confirmed|same:password_confirmation',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $user                 = Admin::where('id', $id)->first();
            $user->forceFill([
                'password' => Hash::make($request->password),
            ])->save();

            return redirect()->back()->with( 'success', __('User Password successfully updated.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function profile()
    {
        $userDetail = \Auth::user();
        return view('users.profile', compact('userDetail'));
    }

    public function editprofile(Request $request)
    {
        $userDetail = \Auth::guard()->user();
        $dir        = Storage::url('uploads/profile/');
        $rule['name'] = 'required';
        $rule['email'] = 'required';

        $validator = \Validator::make($request->all(), $rule);
        if($validator->fails())
        {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        if ($request->hasFile('profile_image')) {
            $fileName = rand(10,100).'_'.time() . "_" . $request->profile_image->getClientOriginalName();
            $path = Utility::upload_file($request,'profile_image',$fileName,$dir,[]);
        }

        $user_id = \Auth::guard()->user()->id;
        $user               = User::Where('id', $user_id)->first();
        if (!empty($request->profile_image)) {
            $user['profile_image'] = str_replace('/storage', '', $path['url']);
        }
        $user->name   = $request->name;
        $user->email        = $request->email;

        $user->save();

        return redirect()->back()->with('success', __('Personal info successfully updated.'));
    }

    public function password_update(Request $request, $slug = '')
    {
        $store = Store::where('slug', $slug)->first();
        if (!empty($store)) {
            $theme_id = $store->theme_id;
        }

        # Validation
        $rule['old_password'] = 'required';
        $rule['new_password'] = 'required|confirmed';

        $validator = \Validator::make($request->all(), $rule);
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        if (!empty($request->type) && ($request->type = 'admin' || $request->type = 'super admin')) {
            #Match The Old Password
            if (!Hash::check($request->old_password, Auth::guard()->user()->password)) {
                return redirect()->back()->with('error', __("Old Password Does not match!"));
            }

            #Update the new Password
            User::whereId(Auth::guard()->user()->id)->update([
                'password' => Hash::make($request->new_password)
            ]);
        } else {
            #Match The Old Password
            if (!Hash::check($request->old_password, auth()->user()->password)) {
                return redirect()->back()->with('error', __("Old Password Does not match!"));
            }

            #Update the new Password
            User::whereId(auth()->user()->id)->update([
                'password' => Hash::make($request->new_password)
            ]);
        }


        return redirect()->back()->with('success', __('Password update succefully.'));
    }
}