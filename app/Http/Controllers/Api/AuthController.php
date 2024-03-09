<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Mail\SendOTP;
use App\Models\Coupon;
use App\Models\DeliveryAddress;
use App\Models\UserAdditionalDetail;
use App\Models\Utility; 
use App\Models\Store;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    use ApiResponser;

    // public function __construct(Request $request)
    // {
    //     if (request()->segments()) {
    //         $slug = request()->segments()[1];
    //         $this->store = Store::where('slug',$slug)->first();
    //         $this->APP_THEME = $this->store->theme_id;
    //     }
    // }

    public function register(Request $request,$slug)
    {
        $store = Store::where('slug',$slug)->first();

        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'token' => 'required',
            'device_type' => 'required',
        ];

        $email = $request->email;
        $request->register_type = !empty($request->register_type) ? $request->register_type : 'email';
        $theme_id = !empty($store) ? $store->theme_id : $request->theme_id;

        if( $request->register_type == 'email' ) {
            $rules['email'] = 'required|string|email|unique:users,email';
        } elseif(empty($request->email)) {
            $request->email = $request->first_name.rand(0,1000).'@example.com';
        }

        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return $this->error([
                'message' => $messages->first()
            ]);
        }

        $user                   = new User();
        if(!empty($request->email) && $request->register_type != 'email') {
            if( $request->register_type == 'facebook') {

                if(empty($email) && empty($request->mobile)) {
                    return $this->error(['message' => __('Email or mobile required in facebook login.')]);
                }

                $user_query = User::query();
                if(!empty($email)) {
                    $user = $user_query->where('email', $email)->first();
                }
                if(!empty($email)) {
                    $user = $user_query->where('mobile', $request->mobile)->first();
                }
            } else {
                $user = User::where('email', $request->email)->first();
            }

            if(empty($user)) {
                $user                   = new User();
            }
        }

        $user->first_name       = $request->first_name;
        $user->last_name        = $request->last_name;
        $user->email            = $request->email;
        $user->type             = 'customer';
        $user->mobile           = $request->mobile;
        $user->firebase_token   = $request->token;
        $user->device_type      = $request->device_type;
        $user->register_type    = $request->register_type;
        $user->theme_id         = $theme_id;
        $user->created_by         = $store->created_by;
        $user->store_id         = $store->id;


        if ($request->register_type == 'email') {
            $rules = [
                'password' => 'required|string|min:6',
                'mobile' => 'required',
            ];
            $validator = \Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return $this->error(['message' => $messages->first()]);
            }
            $user->password         = bcrypt($request->password);
			$user->save();
        } else {
            $flag = 0;

            if ($request->register_type == 'google' && !empty($request->google_id) ) {
                $user->google_id       = $request->google_id;
                $flag = 1;
            }
            if ($request->register_type == 'apple' && !empty($request->apple_id) ) {
                $user->apple_id        = $request->apple_id;
                $flag = 1;
            }
            if ($request->register_type == 'facebook' && !empty($request->facebook_id) ) {
                $user->facebook_id     = $request->facebook_id;
                $flag = 1;
            }

            if ($flag == 0) {
                $message = $request->register_type . ' id is missing.';
                return $this->error(['message' => $message]);
            }
			$user->save();
        }

        $UserAdditionalDetail = new UserAdditionalDetail();
        $UserAdditionalDetail->user_id = $user->id;
        $UserAdditionalDetail->theme_id = $theme_id;
        $UserAdditionalDetail->save();

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->token = $token;
        $user->token_type = 'Bearer';

        $user_array = $user->toArray();
        $user_data = User::find($user->id);
        $user_array['image'] = !empty($user_data->profile_image) ? $user_data->profile_image : "themes/style/uploads/require/user.png";
        return $this->success($user_array);
    }

    public function login(Request $request,$slug='')
    {
        $store = Store::where('slug',$slug)->first();
        $theme_id = !empty($store) ? $store->theme_id  : $request->theme_id;

        $rules = [
            'email' => 'required|email',
            'token' => 'required',
            'device_type' => 'required',
        ];

        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return $this->error([
                'message' => $messages->first()
            ]);
        }

        if (!empty($request->password)) {
            $user = Auth::attempt(['email' => $request->email, 'password' => $request->password]);
            if (!$user) {
                return $this->error(['message' => 'Invalid login details']);
            }
            $user = Auth::user();

            $user = User::find(Auth::user()->id);
            $user->firebase_token = $request->token;
            $user->save();

        } elseif (!empty($request->google_id) || !empty($request->facebook_id) || !empty($request->apple_id)) {

            $User_query = User::where('email', $request->email);
            if (!empty($request->google_id)) {
                $User_query->where('google_id', $request->google_id);
            } elseif (!empty($request->facebook_id)) {
                $User_query->where('facebook_id', $request->facebook_id);
            } elseif (!empty($request->apple_id)) {
                $User_query->where('apple_id', $request->apple_id);
            }

            $user = $User_query->first();

            if (!empty($user)) {
                $user->firebase_token = $request->token;
                $user->save();
            } else {
                return $this->error(['message' => 'Invalid login details.']);
            }
        } else {
            return $this->error(['message' => 'Invalid login details']);
        }

        // Auth::loginUsingId(1)

        $user_data = User::find($user->id);
        $DeliveryAddress = DeliveryAddress::where('user_id', $user->id)->where('default_address', 1)->first();

        $user_array['id'] = $user_data->id;
        $user_array['first_name'] = $user_data->first_name;
        $user_array['last_name'] = $user_data->last_name;
        $user_array['image'] = !empty($user_data->profile_image) ? $user_data->profile_image : "themes/style/uploads/require/user.png";
        $user_array['name'] = $user_data->name;
        $user_array['email'] = $user_data->email;
        $user_array['mobile'] = $user_data->mobile;
        $user_array['company_name'] = !empty($DeliveryAddress->company_name) ? $DeliveryAddress->company_name : '';
        $user_array['country_id'] = !empty($DeliveryAddress->country_id) ? $DeliveryAddress->country_id : '';
        $user_array['state_id'] = !empty($DeliveryAddress->state_id) ? $DeliveryAddress->state_id : '';
        $user_array['city'] = !empty($DeliveryAddress->city) ? $DeliveryAddress->city : '';
        $user_array['address'] = !empty($DeliveryAddress->address) ? $DeliveryAddress->address : '';
        $user_array['postcode'] = !empty($DeliveryAddress->postcode) ? $DeliveryAddress->postcode : '';

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;
        $user_array['token'] = $token;
        $user_array['token_type'] = 'Bearer';
        return $this->success($user_array);
    }

    public function logout(Request $request,$slug='')
    {
        $store = Store::where('slug',$slug)->first();
        $theme_id = !empty($store) ? $store->theme_id  : $request->theme_id;

        $user = User::find($request->user_id);
        if (!empty($user)) {
            return $this->success([
                'message' => 'User Logout',
                'logout' => $user->tokens()->delete()
            ]);
        } else {
            return $this->error([
                'message' => 'User not found'
            ]);
        }
    }

    public function fargot_password_send_otp(Request $request,$slug='')
    {

        $store = Store::where('slug',$slug)->first();
        $theme_id = !empty($store) ? $store->theme_id  : $request->theme_id;

        $email = $request->email;
        $has_email = User::where('email', $email)->first();
        if(empty($has_email)) {
            return $this->error([
                'message' => "We can't find a user with that email address."
            ]);
        }

        if($has_email->register_type != 'email') {
            return $this->error([
                'message' => "You can't login because you used social login."
            ]);
        }

        $OTP = Utility::generateNumericOTP(4);
        UserAdditionalDetail::where('user_id', $has_email->id)->update(['password_otp' => $OTP,'password_otp_datetime' => date('Y-m-d H:i:s') ]);

        $settings = Setting::where('theme_id', $theme_id)->where('store_id',$store->id)->pluck('value', 'name')->toArray();

        try {
            config(
                [
                    'mail.driver' => $settings['MAIL_DRIVER'],
                    'mail.host' => $settings['MAIL_HOST'],
                    'mail.port' => $settings['MAIL_PORT'],
                    'mail.encryption' => $settings['MAIL_ENCRYPTION'],
                    'mail.username' => $settings['MAIL_USERNAME'],
                    'mail.password' => $settings['MAIL_PASSWORD'],
                    'mail.from.address' => $settings['MAIL_FROM_ADDRESS'],
                    'mail.from.name' => $settings['MAIL_FROM_NAME'],
                ]
            );

            $email = $has_email->email;
            // $email = 'jehegek554@bongcs.com';
            Mail::to($email)->send(new SendOTP($OTP));
            return $this->success([
                'message' => 'We have emailed your OTP!.',
                'infomation' => 'OTP is valid for 10 minutes.'
            ]);
        } catch (\Throwable $th) {
            return $this->error([
                'message' => 'E-Mail has been not sent due to SMTP configuration.'
            ]);
        }
    }

    public function fargot_password_verify_otp(Request $request,$slug='')
    {

        $store = Store::where('slug',$slug)->first();
        $theme_id = !empty($store) ? $store->theme_id  : $request->theme_id;

        $email = $request->email;
        $request_otp = $request->otp;

        $has_email = User::where('email', $email)->first();
        if(empty($has_email)) {
            return $this->error([
                'message' => "We can't find a user with that email address."
            ]);
        }

        $otp = UserAdditionalDetail::where('user_id', $has_email->id)->first();
        if(empty($otp->password_otp)) {
            return $this->error([
                'message' => "OTP no found."
            ]);
        }

        $expire_time = date('Y-m-d H:i:s', strtotime('+10 minutes', strtotime($otp->password_otp_datetime)));
        $current_time = date('Y-m-d H:i:s');
        if($expire_time < $current_time) {
            return $this->error([
                'message' => "OTP has been expired."
            ]);
        }

        if($request_otp != $otp->password_otp) {
            return $this->error([
                'message' => "OTP has been not matched."
            ]);
        }

        $otp->password_otp = null;
        $otp->password_otp_datetime = null;
        $otp->save();

        return $this->success([
            'message' => "OTP has been successfully matched."
        ]);
    }

    public function fargot_password_save(Request $request,$slug='')
    {
        $store = Store::where('slug',$slug)->first();
        $theme_id = !empty($store) ? $store->theme_id  : $request->theme_id;

        $email      = $request->email;
        $password   = $request->password;

        $rules = [
            'email' => 'required|email',
            'password' => 'required',
        ];

        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return $this->error([
                'message' => $messages->first()
            ]);
        }

        $email = $request->email;
        $has_email = User::where('email', $email)->first();
        if(empty($has_email)) {
            return $this->error([
                'message' => "We can't find a user with that email address."
            ]);
        }

        $has_email->password = bcrypt($request->password);
        $has_email->save();
        return $this->success([
            'message' => "Password changed successfully."
        ]);

    }
}
