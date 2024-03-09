<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CartController;
use App\Models\AppSetting;
use App\Models\{ActivityLog, City, Country, DeliveryAddress, OrderNote, Product, ProductVariant, Store};
use App\Models\Tax;
use App\Models\MainCategory;
use App\Models\SubCategory;
use App\Models\ {Utility, Wishlist,FlashSale};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponser;
use Session;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use App\Models\TaxOption;
use App\Models\TaxMethod;
use App\Models\OrderBillingDetail;
use App\Models\OrderTaxDetail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cookie;

class ApiController extends Controller
{
    use ApiResponser;

    public function cart_list( Request $request, $slug = '' ) 
    {
        $store = Store::where( 'slug', $slug)->first();
        if ( $store ) {
            $store_id = $store->id;
            $slug = $store->slug;
            $theme_id = $store->theme_id;
        } else {
            $store_id = auth()->user()->current_store;
            $theme_id = $slug;
        }

        $shipping_price = ( int )$request['shipping_final_price'] ?? 0;
        
        $coupon_amount = 0;

        $Carts = Cart::where( 'customer_id', $request->customer_id )->where( 'theme_id', $theme_id )->where( 'store_id', $store->id )->orderBy( 'id', 'desc' )->get();
        $cart_array = [];
        $final_price = $original_price = 0;
        $discount_price = $coupon_price = $tax_price = 0;
        $after_discount_final_price = 0;
        $cart_total_qty = 0;
        $cart_final_price = 0;
        $total_orignal_price = 0;
        $tax_id = null;
        $shipping_original_price = 0;
        $cart_array[ 'product_list' ] = [];
        if (isset($request->billing_info) ) {
            $other_info = is_string($request->billing_info) ? (array) json_decode($request->billing_info) : ($request->billing_info ?? '');
            $country = !empty( $other_info[ 'delivery_country' ] ) ? $other_info[ 'delivery_country' ] :'';
            $state_id = !empty( $other_info[ 'delivery_state' ] ) ? $other_info[ 'delivery_state' ] : '';
            $city_id = !empty( $other_info[ 'delivery_city' ] ) ? $other_info[ 'delivery_city' ] : '';
        } else {
            $country = isset($request['countryId']) ? $request['countryId'] : null;
            $state_id =isset($request['stateId']) ? $request['stateId'] : null;
            $city_id = isset($request['cityId']) ? $request['cityId'] : null;
        }
        foreach ( $Carts as $key => $value ) {
            $cart_product_data = Product::find( $value->product_id);
            if ( empty( $value->variant_id ) && $value->variant_id == 0 ) {
                $per_product_discount_price = !empty( $value->product_data->discount_price ) ? $value->product_data->discount_price : 0;
                $product_discount_price = $per_product_discount_price * $value->qty;

                $final_price = !empty( $value->product_data->sale_price ) ? $value->product_data->sale_price : 0;
               
                $final_price = $final_price * $value->qty;

                $product_orignal_price = !empty( $value->product_data->original_price ) ? $value->product_data->original_price : 0;
                $total_product_orignal_price = $product_orignal_price * $value->qty;
            } else {
                $ProductVariant = ProductVariant::find( $value->variant_id );

                $per_product_discount_price = !empty( $ProductVariant->discount_price ) ? $ProductVariant->discount_price : 0;
                $product_discount_price = $ProductVariant->discount_price * $value->qty;

                $final_price = !empty( $ProductVariant->final_price ) ? $ProductVariant->final_price : 0;
                $final_price = $final_price * $value->qty;

                $product_orignal_price = !empty( $ProductVariant->original_price ) ? $ProductVariant->original_price : 0;
                $total_product_orignal_price = $product_orignal_price * $value->qty;
            }

            $cart_array[ 'product_list' ][ $key ][ 'cart_id' ] = $value->id;
            $cart_array[ 'product_list' ][ $key ][ 'cart_created' ] = $value->created_at;
            $cart_array[ 'product_list' ][ $key ][ 'product_id' ] = $value->product_id;
            $cart_array[ 'product_list' ][ $key ][ 'image' ] = !empty( $value->product_data->cover_image_path ) ? $value->product_data->cover_image_path : ' ';
            $cart_array[ 'product_list' ][ $key ][ 'name' ] = !empty( $value->product_data->name ) ? $value->product_data->name : ' ';
            $cart_array[ 'product_list' ][ $key ][ 'orignal_price' ] = SetNumber( $product_orignal_price );
            $cart_array[ 'product_list' ][ $key ][ 'total_orignal_price' ] = SetNumber( $total_product_orignal_price );
            $cart_array[ 'product_list' ][ $key ][ 'per_product_discount_price' ] = SetNumber( $per_product_discount_price );
            $cart_array[ 'product_list' ][ $key ][ 'discount_price' ] = SetNumber( $product_discount_price );
            $cart_array[ 'product_list' ][ $key ][ 'final_price' ] = SetNumber( $final_price );
            $cart_array[ 'product_list' ][ $key ][ 'qty' ] = $value->qty;
            $cart_array[ 'product_list' ][ $key ][ 'variant_id' ] = $value->variant_id;
            $cart_array[ 'product_list' ][ $key ][ 'variant_name' ] = !empty( $value->variant_data->variant ) ? $value->variant_data->variant : '';
            $cart_array[ 'product_list' ][ $key ][ 'return' ] = 0;
            $cart_array[ 'product_list' ][ $key ][ 'shipping_price' ] = SetNumber( $shipping_price );

            $discount_price += $product_discount_price;
            $cart_total_qty += $value->qty;
            $cart_final_price += $final_price;
            $original_price += $total_product_orignal_price;
            $shipping_original_price += $shipping_price;

            if (isset($request['coupon_code'])) {                    
                $coupon = Coupon::where('coupon_code', $request['coupon_code'])->first();
                if ($coupon) {
                    $coupon_apply_price = Cart::getCouponTotalAmount($coupon, $final_price, $cart_product_data->id, $cart_product_data->maincategory_id);
                  
                    $coupon_price += $final_price - $coupon_apply_price;
                    $final_price = $coupon_apply_price;                       
                }                    
            }

            if ($cart_product_data->tax_id) {
                $tax_id = $cart_product_data->tax_id;
                $tax_price += Cart::getProductTaxAmount($cart_product_data->tax_id,$final_price, $store->id, $theme_id, $city_id, $state_id, $country, true);
                
            } else {
                $tax_price += 0;
            }
        }

        
        $after_discount_final_price = $cart_final_price;

        $product_discount_price = ( float )number_format( ( float )$discount_price, 2 );
        $cart_array[ 'product_discount_price' ] = $product_discount_price;
        $after_discount_final_price = ( float )$after_discount_final_price;
       

        $cart_array[ 'sub_total' ] = $after_discount_final_price + $shipping_price;
      
        $tax_option = TaxOption::where( 'store_id', $store_id )
        ->where( 'theme_id', $theme_id )
        ->pluck( 'value', 'name' )->toArray();

        if ( $coupon_price == '' ) {
            $final_total = $cart_final_price + $shipping_price + $tax_price;
        } else {
           
            $final_total = $cart_final_price - $coupon_price + $shipping_price + $tax_price;
        }
        //dd( $final_total +$tax_price );
        $cart_array[ 'tax_price' ] = SetNumber( $tax_price );
        $cart_array[ 'total_tax_price' ] = SetNumber( $tax_price );
        $cart_array[ 'tax_id' ] = $tax_id;
        $cart_array[ 'cart_total_product' ] = count( $Carts );
        $cart_array[ 'cart_total_qty' ] = $cart_total_qty;
        $cart_array[ 'original_price' ] = SetNumber( $original_price );
        // $final_price = $final_total + $tax_price;
        // $final_price = $after_discount_final_price + $tax_price;
        $cart_array[ 'total_final_price' ] = SetNumber( $cart_final_price );
        $cart_array[ 'final_price' ] = SetNumber( $cart_final_price );
        $cart_array[ 'total_sub_price' ] = SetNumber( $final_total );
        $cart_array[ 'sub_total' ] = $after_discount_final_price;
        $cart_array[ 'total_coupon_price' ] = $coupon_price;
        $cart_array[ 'shipping_original_price' ] = $shipping_price;
        $cart_array['coupon_code'] =  $request['coupon_code'] ?? null;
        // $cart_array[ 'final_price' ] = SetNumber( $final_price );
        if ( !empty( $cart_array ) ) {
            return $this->success( $cart_array );
        } else {
            return $this->error( [ 'message' => 'Cart is empty.' ] );
        }
    }

    public function featured_products( Request $request, $slug = '' ) 
    {
        if ( $slug == '' ) {
            $slug = request()->segments()[ 0 ];
            $store = Store::where( 'slug', $slug )->first();
        } else {
            $store = Store::where( 'slug', $slug )->first();
        }

        $theme_id = !empty( $store ) ? $store->theme_id  : $request->theme_id;

        // $theme_id = !empty( $request->theme_id ) ? $request->theme_id : $this->APP_THEME;
        $Subcategory = Utility::ThemeSubcategory( $theme_id );
        if ( $slug == 'admin' ) {
            if ( $Subcategory == 0 ) {
                $SubCategory = MainCategory::where( 'theme_id', $theme_id )->where( 'store_id', getCurrentStore() )->limit( 3 )->get();
            } else {
                $SubCategory = SubCategory::where( 'theme_id', $theme_id )->where( 'store_id', getCurrentStore() )->limit( 3 )->get();
            }
        } else {
            if ( $Subcategory == 0 ) {
                $SubCategory = MainCategory::where( 'theme_id', $theme_id )->where( 'store_id', $store->id )->limit( 3 )->get();
            } else {
                $SubCategory = SubCategory::where( 'theme_id', $theme_id )->where( 'store_id', $store->id )->limit( 3 )->get();
            }
        }
        $data = $SubCategory;
        if ( !empty( $data ) ) {
            return $this->success( $data );
        } else {
            return $this->error( [ 'message' => 'Product category found.' ] );
        }
    }

    public function addtocart( Request $request, $slug = '' ) 
    {
        $slug = !empty( $slug ) ? $slug : '';
        $store = Store::where( 'slug', $slug )->first();
        // $theme_id = $store->theme_id;
        $theme_id = !empty( $store ) ? $store->theme_id  : $request->theme_id;
        // $theme_id = !empty( $request->theme_id ) ? $request->theme_id : $this->APP_THEME;
        // $settings = Setting::where( 'theme_id', $theme_id )->where( 'store_id', getCurrentStore() )->pluck( 'value', 'name' )->toArray();
        $settings = Utility::Seting();
        $rules = [
            'customer_id' => 'required',
            'product_id' => 'required',
            'variant_id' => 'nullable',
            'qty' => 'required',
        ];

        $validator = \Validator::make( $request->all(), $rules );
        if ( $validator->fails() ) {
            $messages = $validator->getMessageBag();
            return $this->error( [
                'message' => $messages->first()
            ] );
        }

        $final_price = 0;
        $product = Product::find( $request->product_id );
        if ( !empty( $request->attribute_id ) || $request->attribute_id != 0 ) {
            $ProductVariant = ProductVariant::where( 'id', $request->attribute_id )
            ->where( 'product_id', $request->product_id )
            ->first();

            $variationOptions = explode( ',', $ProductVariant->variation_option );
            $option = in_array( 'manage_stock', $variationOptions );
            if ( $option  == true ) {
                if ( empty( $ProductVariant ) ) {
                    return $this->error( [ 'message' => 'Product not found.' ] );
                } else {
                    if ( $ProductVariant->stock < $settings[ 'out_of_stock_threshold' ] && $ProductVariant->stock_order_status == 'not_allow' ) {
                        return $this->error( [ 'message' => 'Product has out of stock.' ] );
                    }
                }
            }

            $final_price = $ProductVariant->final_price * $request->qty;
        } else {
            if ( !empty( $product ) ) {
                if ( $product->variant_product == 1 ) {
                    $product_stock_datas = ProductVariant::find( $request->variant_id );
                    $product->setAttribute( 'variantId', $request->variant_id );
                    $var_stock = !empty( $product_stock_datas->stock ) ? $product_stock_datas->stock : $product->product_stock;

                    if ( empty( $request->variant_id ) || $request->variant_id == 0 ) {
                        {
                            return $this->error( [ 'message' => 'Please Select a variant in a product.' ] );
                        }
                    } else if ( $var_stock <= $settings[ 'out_of_stock_threshold' ] && $product->stock_order_status == 'not_allow' ) {
                        return $this->error( [ 'message' => 'Product has out of stock.' ] );
                    } else {
                        $product_stock_data = ProductVariant::find( $request->variant_id );
                        if ( $product_stock_data->stock_status == 'out_of_stock' ) {
                            return $this->error( [ 'message' => 'Product has out of stock.' ] );
                        }
                    }
                } else {
                    if ( $product->product_stock <= $settings[ 'out_of_stock_threshold' ] && $product->stock_order_status == 'not_allow' ) {
                        return $this->error( [ 'message' => 'Product has out of stock.' ] );
                    }
                }
                $final_price = floatval( $product->final_price ) * floatval( $request->qty );
            } else {
                return $this->error( [ 'message' => 'Product not found.' ] );
            }
        }

        $qty = $request->qty;
        $cart = Cart::where( 'customer_id', $request->customer_id )
        ->where( 'product_id', $request->product_id )
        ->where( 'variant_id', $request->variant_id )
        ->where( 'theme_id', $theme_id )
        ->where( 'store_id', $store->id )
        ->first();

        // activity log
        $ActivityLog = new ActivityLog();
        $ActivityLog->customer_id = $request->customer_id;
        $ActivityLog->log_type = 'add to cart';
        $ActivityLog->remark = json_encode(
            [
                'product' => $request->product_id,
                'variant' => $request->variant_id,
            ]
        );
        $ActivityLog->theme_id = $theme_id;
        $ActivityLog->store_id = $store->id;
        $ActivityLog->save();

        $cart_count = Cart::where( 'customer_id', $request->customer_id )->where( 'theme_id', $theme_id )->where( 'store_id', $store->id )->count();

        if ( empty( $cart ) ) {
            $cart = new Cart();
        } else {
            return $this->error( [ 'message' => $product->name . ' already in cart.', 'count' => $cart_count ] );
            $final_price += $cart->price;
            $qty = $cart->qty + $request->qty;
        }

        $cart->customer_id = $request->customer_id;
        $cart->product_id = $request->product_id;
        $cart->variant_id = !empty( $request->variant_id ) ? $request->variant_id : 0;
        $cart->qty = $qty;
        $cart->price = $final_price;

        if ( !empty( $cart ) ) {
            $cart->theme_id = $theme_id;
        }
        $cart->store_id = $store->id;
        $cart->save();

        $cart_count = Cart::where( 'customer_id', $request->customer_id )->where( 'theme_id', $theme_id )->where( 'store_id', $store->id )->count();
        if ( !empty( $cart_count ) ) {
            return $this->success( [ 'message' => $product->name . ' add successfully.', 'count' => $cart_count ] );
        } else {
            return $this->error( [ 'message' => 'Cart is empty.', 'count' => $cart_count ] );
        }
    }

    public function cart_qty( Request $request, $slug = '' ) 
    {
        $slug = !empty( $slug ) ? $slug : '';
        $store = Store::where( 'slug', $slug )->first();
        // $theme_id = $store->theme_id;
        $theme_id = !empty( $store ) ? $store->theme_id  : $request->theme_id;
        // $theme_id = !empty( $request->theme_id ) ? $request->theme_id : $this->APP_THEME;

        $rules = [
            'customer_id' => 'required',
            'product_id' => 'required',
            'variant_id' => 'required',
            'quantity_type' => 'required|in:increase,decrease,remove',
        ];

        $validator = \Validator::make( $request->all(), $rules );
        if ( $validator->fails() ) {
            $messages = $validator->getMessageBag();
            return $this->error( [
                'message' => $messages->first()
            ] );
        }

        $final_price = 0;
        if ( !empty( $request->variant_id ) || $request->variant_id != 0 ) {
            $ProductVariant = ProductVariant::find( $request->variant_id );
            $final_price = $ProductVariant->final_price;
        } else {
            $product = Product::find( $request->product_id );
            if ( !empty( $product ) ) {
                if ( $product->variant_product == 1 ) {
                    if ( empty( $request->variant_id ) || $request->variant_id == 0 ) {
                        return $this->error( [
                            'message' => 'Please Select a variant in a product.'
                        ] );
                    }
                }
                $final_price = $product->final_price;
            }
        }

        $cart = Cart::where( 'customer_id', $request->customer_id )
        ->where( 'product_id', $request->product_id )
        ->where( 'variant_id', $request->variant_id )
        ->where( 'theme_id', $theme_id )
        ->where( 'store_id', $store->id )
        ->first();

        $cart_count = Cart::where( 'customer_id', $request->customer_id )->where( 'theme_id', $theme_id )->where( 'store_id', $store->id )->count();
        // dd( $cart,  $product->product_stock, $cart_count, $request->all() );

        if ( empty( $cart ) ) {
            return $this->error( [ 'message' => 'Product not found.' ], 'fail', 200, 0, $cart_count );
        } else {
            if ( $request->quantity_type == 'increase' ) {
                if ( !empty( $request->variant_id ) || $request->variant_id != 0 ) {
                    if ( $cart->qty >= $ProductVariant->stock ) {
                        return $this->error( [ 'message' => 'can not increase product quantity.' ], 'fail', 200, 0, $cart_count );
                    } else {
                        $cart->price += $final_price;
                        $cart->qty += 1;
                    }
                } else {
                    if ( $cart->qty >= $product->product_stock ) {
                        return $this->error( [ 'message' => 'can not increase product quantity.' ], 'fail', 200, 0, $cart_count );
                    } else {
                        $cart->price += $final_price;
                        $cart->qty += 1;
                    }
                }
            }
            if ( $request->quantity_type == 'decrease' ) {
                if ( $cart->qty == 1 ) {
                    return $this->error( [ 'message' => 'can not decrease product quantity.' ], 'fail', 200, 0, $cart_count );
                }
                if ( $cart->qty > 0 ) {
                    $cart->price -= $final_price;
                    $cart->qty -= 1;
                }
            }
            $cart->save();

            if ( $request->quantity_type == 'remove' ) {
                $cart->delete();
            }
            $cart_count = Cart::where( 'customer_id', $request->customer_id )->where( 'theme_id', $theme_id )->where( 'store_id', $store->id )->count();
            return $this->success( [ 'message' => 'Cart successfully updated.' ], 'successfull', 200, $cart_count );
        }
    }

    public function wishlist( Request $request, $slug = '' ) 
    {

        $slug = !empty( $slug ) ? $slug : '';
        $store = Store::where( 'slug', $slug )->first();
        $theme_id = !empty( $store ) ? $store->theme_id  : $request->theme_id;

        // $theme_id = $store->theme_id;
        // $theme_id = !empty( $request->theme_id ) ? $request->theme_id : $this->APP_THEME;
        $rules = [
            'customer_id' => 'required',
            'product_id' => 'required',
            // 'variant_id' => 'required',
            'wishlist_type' => 'required|in:add,remove',
        ];

        $validator = \Validator::make( $request->all(), $rules );
        if ( $validator->fails() ) {
            $messages = $validator->getMessageBag();
            return $this->error( [
                'message' => $messages->first()
            ] );
        }

        $Product = Product::find( $request->product_id );
        if ( empty( $Product ) ) {
            return $this->error( [ 'message' => 'Product not found.' ] );
        }

        if ( $request->wishlist_type == 'add' ) {
            $Wishlist = Wishlist::where( 'customer_id', $request->customer_id )->where( 'product_id', $request->product_id )->where( 'store_id', $request->store_id )->exists();
            if ( $Wishlist ) {
                return $this->error( [ 'message' => 'Product already added in Wishlist.' ] );
            }

            $Wishlist = new Wishlist();
            $Wishlist->customer_id = $request->customer_id;
            $Wishlist->product_id = $request->product_id;
            $Wishlist->variant_id = 0;
            $Wishlist->status = 1;
            $Wishlist->theme_id = $theme_id;
            $Wishlist->store_id = $store->id;
            $Wishlist->save();

            // activity log
            $ActivityLog = new ActivityLog();
            $ActivityLog->customer_id = $request->customer_id;
            $ActivityLog->log_type = 'add wishlist';
            $ActivityLog->remark = json_encode(
                [ 'product' => $request->product_id ]
            );
            $ActivityLog->theme_id = $theme_id;
            $ActivityLog->store_id = $store->id;
            $ActivityLog->save();

            $Wishlist_count = Wishlist::where( 'customer_id', $request->customer_id )->where( 'theme_id', $theme_id )->where( 'store_id', $store->id )->count();

            if ( !empty( $Wishlist_count ) ) {
                return $this->success( [ 'message' => $Product->name . ' add successfully.', 'count' => $Wishlist_count ] );
            } else {
                return $this->error( [ 'message' => 'wishlist is empty.', 'count' => $Wishlist_count ] );
            }
            return $this->success( [ 'message' => 'Added successfully to wishlist' ] );
        } elseif ( $request->wishlist_type == 'remove' ) {
            Wishlist::where( 'customer_id', $request->customer_id )->where( 'product_id', $request->product_id )->where( 'store_id', $store->id )->delete();

            // activity log
            $ActivityLog = new ActivityLog();
            $ActivityLog->customer_id = $request->customer_id;
            $ActivityLog->log_type = 'delete wishlist';
            $ActivityLog->remark = json_encode(
                [ 'product' => $request->product_id ]
            );
            $ActivityLog->theme_id = $theme_id;
            $ActivityLog->store_id = $store->id;
            $ActivityLog->save();

            $Wishlist_count = Wishlist::where( 'customer_id', $request->customer_id )->where( 'theme_id', $theme_id )->where( 'store_id', $store->id )->count();

            return $this->success( [ 'message' => $Product->name . 'Removed successfully to wishlist.', 'count' => $Wishlist_count ] );
        } else {
            return $this->error( [ 'message' => 'Product not found.' ] );
        }
    }

    public function wishlist_list( Request $request, $slug = '' ) 
    {
        $slug = !empty( $slug ) ? $slug : '';
        $store = Store::where( 'slug', $slug )->first();
        $theme_id = !empty( $store ) ? $store->theme_id  : $request->theme_id;

        // $theme_id = $store->theme_id;
        // $theme_id = !empty( $request->theme_id ) ? $request->theme_id : $this->APP_THEME;
        $rules = [
            'customer_id' => 'required',
        ];

        $validator = \Validator::make( $request->all(), $rules );
        if ( $validator->fails() ) {
            $messages = $validator->getMessageBag();
            return $this->error( [
                'message' => $messages->first()
            ] );
        }

        $Wishlist = Wishlist::with( 'ProductData' )->where( 'customer_id', $request->customer_id )->where( 'theme_id', $theme_id )->where( 'store_id', $store->id )->paginate( 10 );

        if ( !empty( $Wishlist ) ) {
            return $this->success( $Wishlist );
        } else {
            return $this->error( [ 'message' => 'Wishlist is empty.' ] );
        }
    }

    public function address_list( Request $request, $slug = '' ) 
    {
        $store = Store::where( 'slug', $slug )->first();
        $theme_id = !empty( $store ) ? $store->theme_id  : $request->theme_id;

        $rules = [
            'customer_id' => 'required'
        ];

        $validator = \Validator::make( $request->all(), $rules );
        if ( $validator->fails() ) {
            $messages = $validator->getMessageBag();
            return $this->error( [
                'message' => $messages->first()
            ] );
        }

        $DeliveryAddress = DeliveryAddress::where( 'customer_id', $request->customer_id )->paginate( 1000 );

        if ( !empty( $DeliveryAddress ) ) {
            return $this->success( $DeliveryAddress );
        } else {
            return $this->error( [ 'message' => 'User not found.' ] );
        }
    }

    public function payment_list( Request $request, $slug = '' ) 
    {
        $slug = !empty( $slug ) ? $slug : '';
        $store = Store::where( 'slug', $slug )->first();
        $theme_id = !empty( $store ) ? $store->theme_id  : $request->theme_id;
        $storage = 'storage/';
        $Setting_array = [];

        // COD
        $is_cod_enabled = Utility::GetValueByName( 'is_cod_enabled', $theme_id );
        $cod_info = Utility::GetValueByName( 'cod_info', $theme_id );
        $cod_image = Utility::GetValueByName( 'cod_image', $theme_id );
        if ( empty( $cod_image ) ) {
            $cod_images = asset( Storage::url( 'uploads/cod.png' ) );
        }
        $Setting_array[ 0 ][ 'status' ] = ( !empty( $is_cod_enabled ) && $is_cod_enabled == 'on' ) ? 'on' : 'off';
        $Setting_array[ 0 ][ 'name_string' ] = 'COD';
        $Setting_array[ 0 ][ 'name' ] = 'cod';
        if ( !empty( $cod_images ) ) {
            $Setting_array[ 0 ][ 'image' ] = $cod_images;
        } else {
            $Setting_array[ 0 ][ 'image' ] = $cod_image;
        }
        $Setting_array[ 0 ][ 'detail' ] = $cod_info;

        // Bank Transfer
        $bank_transfer_info = Utility::GetValueByName( 'bank_transfer', $theme_id );
        $is_bank_transfer_enabled = Utility::GetValueByName( 'is_bank_transfer_enabled', $theme_id );
        $bank_transfer_image = Utility::GetValueByName( 'bank_transfer_image', $theme_id );
        if ( empty( $bank_transfer_image ) ) {
            $bank_transfer_images = asset( Storage::url( 'uploads/bank.png' ) );
        }
        $Setting_array[ 1 ][ 'status' ] = ( !empty( $is_bank_transfer_enabled ) && $is_bank_transfer_enabled == 'on' ) ? 'on' : 'off';
        $Setting_array[ 1 ][ 'name_string' ] = 'Bank Transfer';
        $Setting_array[ 1 ][ 'name' ] = 'bank_transfer';
        if ( !empty( $bank_transfer_images ) ) {
            $Setting_array[ 1 ][ 'image' ] = $bank_transfer_images;
        } else {
            $Setting_array[ 1 ][ 'image' ] = $bank_transfer_image;
        }

        $Setting_array[ 1 ][ 'detail' ] = !empty( $bank_transfer_info ) ? $bank_transfer_info : '';

        $Setting_array[ 2 ][ 'status' ] = 'off';
        $Setting_array[ 2 ][ 'name_string' ] = 'other_payment';
        $Setting_array[ 2 ][ 'name' ] = 'Other Payment';
        $Setting_array[ 2 ][ 'image' ] = '';
        $Setting_array[ 2 ][ 'detail' ] = '';

        // Stripe ( Creadit card )
        $is_Stripe_enabled = Utility::GetValueByName( 'is_stripe_enabled', $theme_id );
        $publishable_key = Utility::GetValueByName( 'publishable_key', $theme_id );
        $stripe_secret = Utility::GetValueByName( 'stripe_secret', $theme_id );
        $Stripe_image = Utility::GetValueByName( 'stripe_image', $theme_id );
        if ( empty( $Stripe_image ) ) {
            $Stripe_image = asset( Storage::url( 'upload/stripe.png' ) );
        }
        $stripe_unfo = Utility::GetValueByName( 'stripe_unfo', $theme_id );

        $Setting_array[ 3 ][ 'status' ] = !empty( $is_Stripe_enabled ) ? $is_Stripe_enabled : 'off';
        $Setting_array[ 3 ][ 'name_string' ] = 'Stripe';
        $Setting_array[ 3 ][ 'name' ] = 'stripe';
        $Setting_array[ 3 ][ 'detail' ] = $stripe_unfo;
        $Setting_array[ 3 ][ 'image' ] = $Stripe_image;
        $Setting_array[ 3 ][ 'stripe_publishable_key' ] = $publishable_key;
        $Setting_array[ 3 ][ 'stripe_secret_key' ] = $stripe_secret;

        // Paystack
        $is_paystack_enabled = Utility::GetValueByName( 'is_paystack_enabled', $theme_id );
        $paystack_public_key = Utility::GetValueByName( 'paystack_public_key', $theme_id );
        $paystack_secret = Utility::GetValueByName( 'paystack_secret', $theme_id );
        $paystack_image = Utility::GetValueByName( 'paystack_image', $theme_id );
        if ( empty( $paystack_image ) ) {
            $paystack_image = asset( Storage::url( 'upload/stripe.png' ) );
        }
        $paystack_unfo = Utility::GetValueByName( 'paystack_unfo', $theme_id );

        $Setting_array[ 4 ][ 'status' ] = !empty( $is_paystack_enabled ) ? $is_paystack_enabled : 'off';
        $Setting_array[ 4 ][ 'name_string' ] = 'paystack';
        $Setting_array[ 4 ][ 'name' ] = 'paystack';
        $Setting_array[ 4 ][ 'detail' ] = $paystack_unfo;
        $Setting_array[ 4 ][ 'image' ] = $paystack_image;
        $Setting_array[ 4 ][ 'paystack_public_key' ] = $paystack_public_key;
        $Setting_array[ 4 ][ 'paystack_secret' ] = $paystack_secret;

        // Mercado Pago
        $is_mercado_enabled = Utility::GetValueByName( 'is_mercado_enabled', $theme_id );
        $mercado_mode = Utility::GetValueByName( 'mercado_mode', $theme_id );
        $mercado_access_token = Utility::GetValueByName( 'mercado_access_token', $theme_id );
        $mercado_image = Utility::GetValueByName( 'mercado_image', $theme_id );
        if ( empty( $mercado_image ) ) {
            $mercado_image = asset( Storage::url( 'upload/stripe.png' ) );
        }
        $mercado_unfo = Utility::GetValueByName( 'mercado_unfo', $theme_id );

        $Setting_array[ 5 ][ 'status' ] = !empty( $is_mercado_enabled ) ? $is_mercado_enabled : 'off';
        $Setting_array[ 5 ][ 'name_string' ] = 'mercado';
        $Setting_array[ 5 ][ 'name' ] = 'mercado';
        $Setting_array[ 5 ][ 'detail' ] = $mercado_unfo;
        $Setting_array[ 5 ][ 'image' ] = $mercado_image;
        $Setting_array[ 5 ][ 'mercado_mode' ] = $mercado_mode;
        $Setting_array[ 5 ][ 'mercado_access_token' ] = $mercado_access_token;

        // Skrill
        $is_skrill_enabled = Utility::GetValueByName( 'is_skrill_enabled', $theme_id );
        $skrill_email = Utility::GetValueByName( 'skrill_email', $theme_id );
        $skrill_image = Utility::GetValueByName( 'skrill_image', $theme_id );
        if ( empty( $skrill_image ) ) {
            $skrill_image = asset( Storage::url( 'upload/stripe.png' ) );
        }
        $skrill_unfo = Utility::GetValueByName( 'skrill_unfo', $theme_id );

        $Setting_array[ 6 ][ 'status' ] = !empty( $is_skrill_enabled ) ? $is_skrill_enabled : 'off';
        $Setting_array[ 6 ][ 'name_string' ] = 'skrill';
        $Setting_array[ 6 ][ 'name' ] = 'skrill';
        $Setting_array[ 6 ][ 'detail' ] = $skrill_unfo;
        $Setting_array[ 6 ][ 'image' ] = $skrill_image;
        $Setting_array[ 6 ][ 'skrill_email' ] = $skrill_email;

        // PaymentWall
        $is_paymentwall_enabled = Utility::GetValueByName( 'is_paymentwall_enabled', $theme_id );
        $paymentwall_public_key = Utility::GetValueByName( 'paymentwall_public_key', $theme_id );
        $paymentwall_private_key = Utility::GetValueByName( 'paymentwall_private_key', $theme_id );
        $paymentwall_image = Utility::GetValueByName( 'paymentwall_image', $theme_id );
        if ( empty( $paymentwall_image ) ) {
            $paymentwall_image = asset( Storage::url( 'upload/stripe.png' ) );
        }
        $paymentwall_unfo = Utility::GetValueByName( 'paymentwall_unfo', $theme_id );

        $Setting_array[ 7 ][ 'status' ] = !empty( $is_paymentwall_enabled ) ? $is_paymentwall_enabled : 'off';
        $Setting_array[ 7 ][ 'name_string' ] = 'paymentwall';
        $Setting_array[ 7 ][ 'name' ] = 'paymentwall';
        $Setting_array[ 7 ][ 'detail' ] = $paymentwall_unfo;
        $Setting_array[ 7 ][ 'image' ] = $paymentwall_image;
        $Setting_array[ 7 ][ 'paymentwall_public_key' ] = $paymentwall_public_key;
        $Setting_array[ 7 ][ 'paymentwall_private_key' ] = $paymentwall_private_key;

        // Razorpay
        $is_razorpay_enabled = \App\Models\Utility::GetValueByName( 'is_razorpay_enabled', $theme_id );
        $razorpay_public_key = \App\Models\Utility::GetValueByName( 'razorpay_public_key', $theme_id );
        $razorpay_secret_key = \App\Models\Utility::GetValueByName( 'razorpay_secret_key', $theme_id );
        $razorpay_image = \App\Models\Utility::GetValueByName( 'razorpay_image', $theme_id );

        if ( empty( $razorpay_image ) ) {
            $razorpay_image = asset( Storage::url( 'upload/stripe.png' ) );
        }
        $razorpay_unfo = Utility::GetValueByName( 'razorpay_unfo', $theme_id );

        $Setting_array[ 8 ][ 'status' ] = !empty( $is_razorpay_enabled ) ? $is_razorpay_enabled : 'off';
        $Setting_array[ 8 ][ 'name_string' ] = 'Razorpay';
        $Setting_array[ 8 ][ 'name' ] = 'Razorpay';
        $Setting_array[ 8 ][ 'detail' ] = $razorpay_unfo;
        $Setting_array[ 8 ][ 'image' ] = $razorpay_image;
        $Setting_array[ 8 ][ 'razorpay_public_key' ] = $razorpay_public_key;
        $Setting_array[ 8 ][ 'razorpay_secret_key' ] = $razorpay_secret_key;

        //paypal
        $is_paypal_enabled = Utility::GetValueByName( 'is_paypal_enabled', $theme_id );
        $paypal_secret = Utility::GetValueByName( 'paypal_secret', $theme_id );
        $paypal_client_id = Utility::GetValueByName( 'paypal_client_id', $theme_id );
        $paypal_mode = Utility::GetValueByName( 'paypal_mode', $theme_id );
        $paypal_description = Utility::GetValueByName( 'paypal_unfo', $theme_id );
        $paypal_image = Utility::GetValueByName( 'paypal_image', $theme_id );

        if ( empty( $paypal_image ) ) {
            $paypal_image = asset( Storage::url( 'upload/stripe.png' ) );
        }

        $Setting_array[ 9 ][ 'status' ] = !empty( $is_paypal_enabled ) ? $is_paypal_enabled : 'off';
        $Setting_array[ 9 ][ 'name_string' ] = 'Paypal';
        $Setting_array[ 9 ][ 'name' ] = 'paypal';
        $Setting_array[ 9 ][ 'detail' ] = $paypal_description;
        $Setting_array[ 9 ][ 'image' ] = $paypal_image;
        $Setting_array[ 9 ][ 'paypal_secret' ] = $paypal_secret;
        $Setting_array[ 9 ][ 'paypal_client_id' ] = $paypal_client_id;
        $Setting_array[ 9 ][ 'paypal_mode' ] = $paypal_mode;

        //flutterwave
        $is_flutterwave_enabled = \App\Models\Utility::GetValueByName( 'is_flutterwave_enabled', $theme_id );
        $public_key = \App\Models\Utility::GetValueByName( 'public_key', $theme_id );
        $flutterwave_secret = \App\Models\Utility::GetValueByName( 'flutterwave_secret', $theme_id );
        $flutterwave_description = Utility::GetValueByName( 'flutterwave_unfo', $theme_id );
        $flutterwave_image = \App\Models\Utility::GetValueByName( 'flutterwave_image', $theme_id );

        if ( empty( $flutterwave_image ) ) {
            $flutterwave_image = asset( Storage::url( 'upload/stripe.png' ) );
        }

        $Setting_array[ 10 ][ 'status' ] = !empty( $is_flutterwave_enabled ) ? $is_flutterwave_enabled : 'off';
        $Setting_array[ 10 ][ 'name_string' ] = 'Flutterwave';
        $Setting_array[ 10 ][ 'name' ] = 'flutterwave';
        $Setting_array[ 10 ][ 'detail' ] = $flutterwave_description;
        $Setting_array[ 10 ][ 'image' ] = $flutterwave_image;
        $Setting_array[ 10 ][ 'public_key' ] = $public_key;
        $Setting_array[ 10 ][ 'flutterwave_secret' ] = $flutterwave_secret;
        $Setting_array[ 10 ][ 'flutterwave_image' ] = $flutterwave_image;

        //paytm
        $is_paytm_enabled = Utility::GetValueByName( 'is_paytm_enabled', $theme_id );
        $paytm_merchant_id = Utility::GetValueByName( 'paytm_merchant_id', $theme_id );
        $paytm_merchant_key = Utility::GetValueByName( 'paytm_merchant_key', $theme_id );
        $paytm_industry_type = Utility::GetValueByName( 'paytm_industry_type', $theme_id );
        $paytm_mode = Utility::GetValueByName( 'paytm_mode', $theme_id );
        $payptm_description = Utility::GetValueByName( 'paytm_unfo', $theme_id );
        $paytm_image = Utility::GetValueByName( 'paytm_image', $theme_id );

        if ( empty( $paytm_image ) ) {
            $paytm_image = asset( Storage::url( 'upload/stripe.png' ) );
        }

        $Setting_array[ 11 ][ 'status' ] = !empty( $is_paytm_enabled ) ? $is_paytm_enabled : 'off';
        $Setting_array[ 11 ][ 'name_string' ] = 'Paytm';
        $Setting_array[ 11 ][ 'name' ] = 'paytm';
        $Setting_array[ 11 ][ 'detail' ] = $payptm_description;
        $Setting_array[ 11 ][ 'image' ] = $paytm_image;
        $Setting_array[ 11 ][ 'paytm_merchant_id' ] = $paytm_merchant_id;
        $Setting_array[ 11 ][ 'paytm_merchant_key' ] = $paytm_merchant_key;
        $Setting_array[ 11 ][ 'paytm_industry_type' ] = $paytm_industry_type;
        $Setting_array[ 11 ][ 'paytm_mode' ] = $paytm_mode;

        //mollie
        $is_mollie_enabled = Utility::GetValueByName( 'is_mollie_enabled', $theme_id );
        $mollie_api_key = Utility::GetValueByName( 'mollie_api_key', $theme_id );
        $mollie_profile_id = Utility::GetValueByName( 'mollie_profile_id', $theme_id );
        $mollie_partner_id = Utility::GetValueByName( 'mollie_partner_id', $theme_id );
        $mollie_unfo = Utility::GetValueByName( 'mollie_unfo', $theme_id );
        $mollie_image = Utility::GetValueByName( 'mollie_image', $theme_id );

        if ( empty( $mollie_image ) ) {
            $mollie_image = asset( Storage::url( 'upload/stripe.png' ) );
        }

        $Setting_array[ 12 ][ 'status' ] = !empty( $is_mollie_enabled ) ? $is_mollie_enabled : 'off';
        $Setting_array[ 12 ][ 'name_string' ] = 'mollie';
        $Setting_array[ 12 ][ 'name' ] = 'mollie';
        $Setting_array[ 12 ][ 'detail' ] = $mollie_unfo;
        $Setting_array[ 12 ][ 'image' ] = $mollie_image;
        $Setting_array[ 12 ][ 'mollie_api_key' ] = $mollie_api_key;
        $Setting_array[ 12 ][ 'mollie_profile_id' ] = $mollie_profile_id;
        $Setting_array[ 12 ][ 'mollie_partner_id' ] = $mollie_partner_id;

        //coingate
        $is_coingate_enabled = Utility::GetValueByName( 'is_coingate_enabled', $theme_id );
        $coingate_mode = Utility::GetValueByName( 'coingate_mode', $theme_id );
        $coingate_auth_token = Utility::GetValueByName( 'coingate_auth_token', $theme_id );
        $coingate_image = Utility::GetValueByName( 'coingate_image', $theme_id );
        $coingate_unfo = Utility::GetValueByName( 'coingate_unfo', $theme_id );

        if ( empty( $coingate_image ) ) {
            $coingate_image = asset( Storage::url( 'upload/stripe.png' ) );
        }

        $Setting_array[ 13 ][ 'status' ] = !empty( $is_coingate_enabled ) ? $is_coingate_enabled : 'off';
        $Setting_array[ 13 ][ 'name_string' ] = 'coingate';
        $Setting_array[ 13 ][ 'name' ] = 'coingate';
        $Setting_array[ 13 ][ 'detail' ] = $coingate_unfo;
        $Setting_array[ 13 ][ 'image' ] = $coingate_image;
        $Setting_array[ 13 ][ 'coingate_mode' ] = $coingate_mode;
        $Setting_array[ 13 ][ 'coingate_auth_token' ] = $coingate_auth_token;

        //sspay
        $is_sspay_enabled = Utility::GetValueByName( 'is_sspay_enabled', $theme_id );
        $categoryCode = Utility::GetValueByName( 'sspay_category_code', $theme_id );
        $secretKey = Utility::GetValueByName( 'is_sspay_enabled', $theme_id );
        $sspay_image = Utility::GetValueByName( 'sspay_image', $theme_id );
        $sspay_unfo = Utility::GetValueByName( 'sspay_unfo', $theme_id );

        if ( empty( $sspay_image ) ) {
            $sspay_image = asset( Storage::url( 'upload/sspay.png' ) );
        }

        $Setting_array[ 14 ][ 'status' ] = !empty( $is_sspay_enabled ) ? $is_sspay_enabled : 'off';
        $Setting_array[ 14 ][ 'name_string' ] = 'Sspay';
        $Setting_array[ 14 ][ 'name' ] = 'Sspay';
        $Setting_array[ 14 ][ 'detail' ] = $sspay_unfo;
        $Setting_array[ 14 ][ 'image' ] = $sspay_image;
        $Setting_array[ 14 ][ 'categoryCode' ] = $categoryCode;
        $Setting_array[ 14 ][ 'secretKey' ] = $secretKey;

        //toyyibpay
        $is_toyyibpay_enabled = Utility::GetValueByName( 'is_toyyibpay_enabled', $theme_id );
        $categoryCode = Utility::GetValueByName( 'toyyibpay_category_code', $theme_id );
        $secretKey = Utility::GetValueByName( 'is_toyyibpay_enabled', $theme_id );
        $toyyibpay_image = Utility::GetValueByName( 'toyyibpay_image', $theme_id );
        $toyyibpay_unfo = Utility::GetValueByName( 'toyyibpay_unfo', $theme_id );

        if ( empty( $toyyibpay_image ) ) {
            $toyyibpay_image = asset( Storage::url( 'upload/toyyibpay.png' ) );
        }

        $Setting_array[ 15 ][ 'status' ] = !empty( $is_toyyibpay_enabled ) ? $is_toyyibpay_enabled : 'off';
        $Setting_array[ 15 ][ 'name_string' ] = 'toyyibpay';
        $Setting_array[ 15 ][ 'name' ] = 'toyyibpay';
        $Setting_array[ 15 ][ 'detail' ] = $toyyibpay_unfo;
        $Setting_array[ 15 ][ 'image' ] = $toyyibpay_image;
        $Setting_array[ 15 ][ 'categoryCode' ] = $categoryCode;
        $Setting_array[ 15 ][ 'secretKey' ] = $secretKey;

        //paytabs
        $is_paytabs_enabled = Utility::GetValueByName( 'is_paytabs_enabled', $theme_id );
        $Profile_id = Utility::GetValueByName( 'paytabs_profile_id', $theme_id );
        $Serverkey = Utility::GetValueByName( 'paytabs_server_key', $theme_id );
        $Region = Utility::GetValueByName( 'paytabs_region', $theme_id );
        $paytabs_image = Utility::GetValueByName( 'paytabs_image', $theme_id );
        $paytabs_unfo = Utility::GetValueByName( 'paytabs_unfo', $theme_id );

        if ( empty( $paytabs_image ) ) {
            $paytabs_image = asset( Storage::url( 'upload/paytabs.png' ) );
        }

        $Setting_array[ 16 ][ 'status' ] = !empty( $is_paytabs_enabled ) ? $is_paytabs_enabled : 'off';
        $Setting_array[ 16 ][ 'name_string' ] = 'Paytabs';
        $Setting_array[ 16 ][ 'name' ] = 'Paytabs';
        $Setting_array[ 16 ][ 'detail' ] = $paytabs_unfo;
        $Setting_array[ 16 ][ 'image' ] = $paytabs_image;
        $Setting_array[ 16 ][ 'paytabs_profile_id' ] = $Profile_id;
        $Setting_array[ 16 ][ 'paytabs_server_key' ] = $Serverkey;
        $Setting_array[ 16 ][ 'paytabs_region' ] = $Region;

        //Iyzipay
        $is_iyzipay_enabled = Utility::GetValueByName( 'is_iyzipay_enabled', $theme_id );
        $iyzipay_mode = Utility::GetValueByName( 'iyzipay_mode', $theme_id );
        $iyzipay_secret_key = Utility::GetValueByName( 'iyzipay_secret_key', $theme_id );
        $iyzipay_private_key = Utility::GetValueByName( 'iyzipay_private_key', $theme_id );
        $iyzipay_image = Utility::GetValueByName( 'iyzipay_image', $theme_id );
        $iyzipay_unfo = Utility::GetValueByName( 'iyzipay_unfo', $theme_id );

        if ( empty( $iyzipay_image ) ) {
            $iyzipay_image = asset( Storage::url( 'upload/stripe.png' ) );
        }

        $Setting_array[ 17 ][ 'status' ] = !empty( $is_iyzipay_enabled ) ? $is_iyzipay_enabled : 'off';
        $Setting_array[ 17 ][ 'name_string' ] = 'iyzipay';
        $Setting_array[ 17 ][ 'name' ] = 'iyzipay';
        $Setting_array[ 17 ][ 'detail' ] = $iyzipay_unfo;
        $Setting_array[ 17 ][ 'image' ] = $iyzipay_image;
        $Setting_array[ 17 ][ 'iyzipay_mode' ] = $iyzipay_mode;
        $Setting_array[ 17 ][ 'iyzipay_secret_key' ] = $iyzipay_secret_key;
        $Setting_array[ 17 ][ 'iyzipay_private_key' ] = $iyzipay_private_key;

        //payfast
        $is_payfast_enabled = Utility::GetValueByName( 'is_payfast_enabled', $theme_id );
        $payfast_mode = Utility::GetValueByName( 'payfast_mode', $theme_id );
        $payfast_merchant_id = Utility::GetValueByName( 'payfast_merchant_id', $theme_id );
        $payfast_salt_passphrase = Utility::GetValueByName( 'payfast_salt_passphrase', $theme_id );
        $payfast_merchant_key = Utility::GetValueByName( 'payfast_merchant_key', $theme_id );
        $payfast_image = Utility::GetValueByName( 'payfast_image', $theme_id );
        $payfast_unfo = Utility::GetValueByName( 'payfast_unfo', $theme_id );

        if ( empty( $payfast_image ) ) {
            $payfast_image = asset( Storage::url( 'upload/stripe.png' ) );
        }

        $Setting_array[ 18 ][ 'status' ] = !empty( $is_payfast_enabled ) ? $is_payfast_enabled : 'off';
        $Setting_array[ 18 ][ 'name_string' ] = 'payfast';
        $Setting_array[ 18 ][ 'name' ] = 'payfast';
        $Setting_array[ 18 ][ 'detail' ] = $payfast_unfo;
        $Setting_array[ 18 ][ 'image' ] = $payfast_image;
        $Setting_array[ 18 ][ 'payfast_mode' ] = $payfast_mode;
        $Setting_array[ 18 ][ 'payfast_merchant_id' ] = $payfast_merchant_id;
        $Setting_array[ 18 ][ 'payfast_salt_passphrase' ] = $payfast_salt_passphrase;
        $Setting_array[ 18 ][ 'payfast_merchant_key' ] = $payfast_merchant_key;

        //Benefit
        $is_benefit_enabled = Utility::GetValueByName( 'is_benefit_enabled', $theme_id );
        $benefit_mode = Utility::GetValueByName( 'benefit_mode', $theme_id );
        $benefit_secret_key = Utility::GetValueByName( 'benefit_secret_key', $theme_id );
        $benefit_private_key = Utility::GetValueByName( 'benefit_private_key', $theme_id );
        $benefit_image = Utility::GetValueByName( 'benefit_image', $theme_id );
        $benefit_unfo = Utility::GetValueByName( 'benefit_unfo', $theme_id );

        if ( empty( $benefit_image ) ) {
            $benefit_image = asset( Storage::url( 'upload/stripe.png' ) );
        }

        $Setting_array[ 19 ][ 'status' ] = !empty( $is_benefit_enabled ) ? $is_benefit_enabled : 'off';
        $Setting_array[ 19 ][ 'name_string' ] = 'benefit';
        $Setting_array[ 19 ][ 'name' ] = 'benefit';
        $Setting_array[ 19 ][ 'detail' ] = $benefit_unfo;
        $Setting_array[ 19 ][ 'image' ] = $benefit_image;
        $Setting_array[ 19 ][ 'benefit_mode' ] = $benefit_mode;
        $Setting_array[ 19 ][ 'benefit_secret_key' ] = $benefit_secret_key;
        $Setting_array[ 19 ][ 'benefit_private_key' ] = $benefit_private_key;

        //Cashfree
        $is_cashfree_enabled = Utility::GetValueByName( 'is_cashfree_enabled', $theme_id );
        $cashfree_secret_key = Utility::GetValueByName( 'cashfree_secret_key', $theme_id );
        $cashfree_key = Utility::GetValueByName( 'cashfree_key', $theme_id );
        $cashfree_image = Utility::GetValueByName( 'cashfree_image', $theme_id );
        $cashfree_unfo = Utility::GetValueByName( 'cashfree_unfo', $theme_id );

        if ( empty( $cashfree_image ) ) {
            $cashfree_image = asset( Storage::url( 'upload/stripe.png' ) );
        }

        $Setting_array[ 20 ][ 'status' ] = !empty( $is_cashfree_enabled ) ? $is_cashfree_enabled : 'off';
        $Setting_array[ 20 ][ 'name_string' ] = 'cashfree';
        $Setting_array[ 20 ][ 'name' ] = 'cashfree';
        $Setting_array[ 20 ][ 'detail' ] = $cashfree_unfo;
        $Setting_array[ 20 ][ 'image' ] = $cashfree_image;
        $Setting_array[ 20 ][ 'cashfree_secret_key' ] = $cashfree_secret_key;
        $Setting_array[ 20 ][ 'cashfree_key' ] = $cashfree_key;

        //Aamarpay
        $is_aamarpay_enabled = Utility::GetValueByName( 'is_aamarpay_enabled', $theme_id );
        $aamarpay_signature_key = Utility::GetValueByName( 'aamarpay_signature_key', $theme_id );
        $aamarpay_description = Utility::GetValueByName( 'aamarpay_description', $theme_id );
        $aamarpay_store_id = Utility::GetValueByName( 'aamarpay_store_id', $theme_id );
        $aamarpay_image = Utility::GetValueByName( 'aamarpay_image', $theme_id );
        $aamarpay_unfo = Utility::GetValueByName( 'aamarpay_unfo', $theme_id );

        if ( empty( $aamarpay_image ) ) {
            $aamarpay_image = asset( Storage::url( 'upload/stripe.png' ) );
        }

        $Setting_array[ 21 ][ 'status' ] = !empty( $is_aamarpay_enabled ) ? $is_aamarpay_enabled : 'off';
        $Setting_array[ 21 ][ 'name_string' ] = 'aamarpay';
        $Setting_array[ 21 ][ 'name' ] = 'aamarpay';
        $Setting_array[ 21 ][ 'detail' ] = $aamarpay_unfo;
        $Setting_array[ 21 ][ 'image' ] = $aamarpay_image;
        $Setting_array[ 21 ][ 'aamarpay_signature_key' ] = $aamarpay_signature_key;
        $Setting_array[ 21 ][ 'aamarpay_description' ] = $aamarpay_description;
        $Setting_array[ 21 ][ 'aamarpay_store_id' ] = $aamarpay_store_id;

        //Telegram
        $is_telegram_enabled = Utility::GetValueByName( 'is_telegram_enabled', $theme_id );
        $telegram_access_token = Utility::GetValueByName( 'telegram_access_token', $theme_id );
        $telegram_chat_id = Utility::GetValueByName( 'telegram_chat_id', $theme_id );
        $telegram_image = Utility::GetValueByName( 'telegram_image', $theme_id );
        $telegram_unfo = Utility::GetValueByName( 'telegram_unfo', $theme_id );

        if ( empty( $telegram_image ) ) {
            $telegram_image = asset( Storage::url( 'upload/stripe.png' ) );
        }

        $Setting_array[ 22 ][ 'status' ] = !empty( $is_telegram_enabled ) ? $is_telegram_enabled : 'off';
        $Setting_array[ 22 ][ 'name_string' ] = 'telegram';
        $Setting_array[ 22 ][ 'name' ] = 'telegram';
        $Setting_array[ 22 ][ 'detail' ] = $telegram_unfo;
        $Setting_array[ 22 ][ 'image' ] = $telegram_image;
        $Setting_array[ 22 ][ 'telegram_access_token' ] = $telegram_access_token;
        $Setting_array[ 22 ][ 'telegram_chat_id' ] = $telegram_chat_id;

        //Whatsapp
        $is_whatsapp_enabled = Utility::GetValueByName( 'is_whatsapp_enabled', $theme_id );
        $whatsapp_number = Utility::GetValueByName( 'whatsapp_number', $theme_id );
        $whatsapp_image = Utility::GetValueByName( 'whatsapp_image', $theme_id );
        $whatsapp_unfo = Utility::GetValueByName( 'whatsapp_unfo', $theme_id );

        if ( empty( $whatsapp_image ) ) {
            $whatsapp_image = asset( Storage::url( 'upload/stripe.png' ) );
        }

        $Setting_array[ 23 ][ 'status' ] = !empty( $is_whatsapp_enabled ) ? $is_whatsapp_enabled : 'off';
        $Setting_array[ 23 ][ 'name_string' ] = 'whatsapp';
        $Setting_array[ 23 ][ 'name' ] = 'whatsapp';
        $Setting_array[ 23 ][ 'detail' ] = $whatsapp_unfo;
        $Setting_array[ 23 ][ 'image' ] = $whatsapp_image;
        $Setting_array[ 23 ][ 'whatsapp_number' ] = $whatsapp_number;

        //Pay TR
        $is_paytr_enabled = Utility::GetValueByName( 'is_paytr_enabled', $theme_id );
        $paytr_merchant_id = Utility::GetValueByName( 'paytr_merchant_id', $theme_id );
        $paytr_merchant_key = Utility::GetValueByName( 'paytr_merchant_key', $theme_id );
        $paytr_salt_key = Utility::GetValueByName( 'paytr_salt_key', $theme_id );
        $paytr_image = Utility::GetValueByName( 'paytr_image', $theme_id );
        $paytr_unfo = Utility::GetValueByName( 'paytr_unfo', $theme_id );

        if ( empty( $paytr_image ) ) {
            $paytr_image = asset( Storage::url( 'upload/stripe.png' ) );
        }

        $Setting_array[ 24 ][ 'status' ] = !empty( $is_paytr_enabled ) ? $is_paytr_enabled : 'off';
        $Setting_array[ 24 ][ 'name_string' ] = 'paytr';
        $Setting_array[ 24 ][ 'name' ] = 'paytr';
        $Setting_array[ 24 ][ 'detail' ] = $paytr_unfo;
        $Setting_array[ 24 ][ 'image' ] = $paytr_image;
        $Setting_array[ 24 ][ 'paytr_merchant_id' ] = $paytr_merchant_id;
        $Setting_array[ 24 ][ 'paytr_merchant_key' ] = $paytr_merchant_key;
        $Setting_array[ 24 ][ 'paytr_salt_key' ] = $paytr_salt_key;

        //Yookassa
        $is_yookassa_enabled = Utility::GetValueByName( 'is_yookassa_enabled', $theme_id );
        $yookassa_shop_id_key = Utility::GetValueByName( 'yookassa_shop_id_key', $theme_id );
        $yookassa_secret_key = Utility::GetValueByName( 'yookassa_secret_key', $theme_id );
        $yookassa_image = Utility::GetValueByName( 'yookassa_image', $theme_id );
        $yookassa_unfo = Utility::GetValueByName( 'yookassa_unfo', $theme_id );

        if ( empty( $yookassa_image ) ) {
            $yookassa_image = asset( Storage::url( 'upload/stripe.png' ) );
        }

        $Setting_array[ 25 ][ 'status' ] = !empty( $is_yookassa_enabled ) ? $is_yookassa_enabled : 'off';
        $Setting_array[ 25 ][ 'name_string' ] = 'yookassa';
        $Setting_array[ 25 ][ 'name' ] = 'yookassa';
        $Setting_array[ 25 ][ 'detail' ] = $yookassa_unfo;
        $Setting_array[ 25 ][ 'image' ] = $yookassa_image;
        $Setting_array[ 25 ][ 'yookassa_shop_id_key' ] = $yookassa_shop_id_key;
        $Setting_array[ 25 ][ 'yookassa_secret_key' ] = $yookassa_secret_key;

        //Xendit
        $is_Xendit_enabled = Utility::GetValueByName( 'is_Xendit_enabled', $theme_id );
        $Xendit_api_key = Utility::GetValueByName( 'Xendit_api_key', $theme_id );
        $Xendit_token_key = Utility::GetValueByName( 'Xendit_token_key', $theme_id );
        $Xendit_image = Utility::GetValueByName( 'Xendit_image', $theme_id );
        $Xendit_unfo = Utility::GetValueByName( 'Xendit_unfo', $theme_id );

        if ( empty( $Xendit_image ) ) {
            $Xendit_image = asset( Storage::url( 'upload/stripe.png' ) );
        }
        $Setting_array[ 26 ][ 'status' ] = !empty( $is_Xendit_enabled ) ? $is_Xendit_enabled : 'off';
        $Setting_array[ 26 ][ 'name_string' ] = 'Xendit';
        $Setting_array[ 26 ][ 'name' ] = 'Xendit';
        $Setting_array[ 26 ][ 'detail' ] = $Xendit_unfo;
        $Setting_array[ 26 ][ 'image' ] = $Xendit_image;
        $Setting_array[ 26 ][ 'Xendit_api_key' ] = $Xendit_api_key;
        $Setting_array[ 26 ][ 'Xendit_token_key' ] = $Xendit_token_key;

        //Midtrans
        $is_midtrans_enabled = Utility::GetValueByName( 'is_midtrans_enabled', $theme_id );
        $midtrans_secret_key = Utility::GetValueByName( 'midtrans_secret_key', $theme_id );
        $midtrans_image = Utility::GetValueByName( 'midtrans_image', $theme_id );
        $midtrans_unfo = Utility::GetValueByName( 'midtrans_unfo', $theme_id );

        if ( empty( $midtrans_image ) ) {
            $midtrans_image = asset( Storage::url( 'upload/stripe.png' ) );
        }

        $Setting_array[ 27 ][ 'status' ] = !empty( $is_midtrans_enabled ) ? $is_midtrans_enabled : 'off';
        $Setting_array[ 27 ][ 'name_string' ] = 'midtrans';
        $Setting_array[ 27 ][ 'name' ] = 'midtrans';
        $Setting_array[ 27 ][ 'detail' ] = $midtrans_unfo;
        $Setting_array[ 27 ][ 'image' ] = $midtrans_image;
        $Setting_array[ 27 ][ 'midtrans_secret_key' ] = $midtrans_secret_key;

        if ( !empty( $Setting_array ) ) {
            return $this->success( $Setting_array );
        } else {
            return $this->error( [ 'message' => 'Payment not found.' ] );
        }
    }

    public function place_order( Request $request, $slug = '' ) 
    {
        $slug = !empty( $slug ) ? $slug : '';
        $store = Store::where( 'slug', $slug )->first();
        $theme_id = !empty( $store ) ? $store->theme_id  : $request->theme_id;
        $settings = Utility::Seting();
        $user = User::where( 'type', 'admin' )->first();
        if ( $user->type == 'admin' ) {
            $plan = Plan::find( $user->plan_id );
        }
        $rules = [
            'customer_id' => 'required',
            'billing_info' => 'required',
            'payment_type' => 'required',
        ];

        $validator = \Validator::make( $request->all(), $rules );

        if ( $validator->fails() ) {
            $messages = $validator->getMessageBag();
            return $this->error( [
                'message' => $messages->first()
            ] );
        }

        $cartlist_final_price = 0;
        $final_price = 0;

        if ( !empty( $request->customer_id ) ) {
            $cart_list[ 'customer_id' ]   = $request->customer_id;
            $request->request->add( $cart_list );
            $cartlist_response = $this->cart_list( $request, $slug );
            $cartlist = ( array )$cartlist_response->getData()->data;
            if ( empty( $cartlist[ 'product_list' ] ) ) {
                return $this->error( [ 'message' => 'Cart is empty.' ] );
            }

            $cartlist_final_price = !empty( $cartlist[ 'final_price' ] ) ? $cartlist[ 'final_price' ] : 0;
            $final_sub_total_price = !empty( $cartlist[ 'total_sub_price' ] ) ? $cartlist[ 'total_sub_price' ] : 0;
            $final_price = $cartlist[ 'total_final_price' ];
            $taxes = !empty( $cartlist[ 'tax_info' ] ) ? $cartlist[ 'tax_info' ] : '';
            $billing = is_string($request->billing_info) ? (array) json_decode($request->billing_info) : $request->billing_info; 
            $taxes = !empty( $cartlist[ 'tax_info' ] ) ? $cartlist[ 'tax_info' ] : '';
            $products = $cartlist[ 'product_list' ];
        } else {
            return $this->error( [ 'message' => 'User not found.' ] );
        }

        $coupon_price = 0;
        // coupon api call
        if ( !empty( $request->coupon_info ) ) {
            $coupon_data = $request->coupon_info;
            $apply_coupon = [
                'coupon_code' => $coupon_data[ 'coupon_code' ],
                'sub_total' => $cartlist_final_price
            ];
            $request->request->add( $apply_coupon );
            $apply_coupon_response = $this->apply_coupon( $request, $slug );

            $apply_coupon = ( array )$apply_coupon_response->getData()->data;
            $order_array[ 'coupon' ][ 'message' ] = $apply_coupon[ 'message' ];
            $order_array[ 'coupon' ][ 'status' ] = false;
            if ( !empty( $apply_coupon[ 'final_price' ] ) ) {
                $cartlist_final_price = $apply_coupon[ 'final_price' ];
                $coupon_price = $apply_coupon[ 'amount' ];
                $order_array[ 'coupon' ][ 'status' ] = true;
            }
        }

        $delivery_price = 0;
        if ( $plan->shipping_method == 'on' ) {
            if ( !empty( $request->method_id ) ) {
                $del_charge = new CartController();
                $delivery_charge = $del_charge->get_shipping_method( $request, $slug );
                $content = $delivery_charge->getContent();
                $data = json_decode( $content, true );
                $delivery_price = $data['shipping_final_price'];
                $tax_price = $data[ 'final_tax_price' ];
            } else {
                return $this->error( [ 'message' => 'Shipping Method not found' ] );
            }
        } else {
            $tax_price = 0;
            if ( !empty( $taxes ) ) {
                foreach ( $taxes as $key => $tax ) {
                    $tax_price += $tax->tax_price;
                }
            }
        }

        // Order stock decrease start
        $prodduct_id_array = [];
        if ( !empty( $products ) ) {
            foreach ( $products as $key => $product ) {
                $prodduct_id_array[] = $product->product_id;

                $product_id = $product->product_id;
                $variant_id = $product->variant_id;
                $qtyy = !empty( $product->qty ) ? $product->qty : 0;

                $Product = Product::where( 'id', $product_id )->first();
                $datas = Product::find( $product_id );

                if ( $settings[ 'stock_management' ] == 'on' ) {
                    if ( !empty( $product_id ) && !empty( $variant_id ) && $product_id != 0 && $variant_id != 0 ) {
                        $ProductStock = ProductVariant::where( 'id', $variant_id )->where( 'product_id', $product_id )->first();
                        $variationOptions = explode( ',', $ProductStock->variation_option );
                        $option = in_array( 'manage_stock', $variationOptions );
                        if ( !empty( $ProductStock ) ) {
                            if ( $option == true ) {
                                $remain_stock = $ProductStock->stock - $qtyy;
                                $ProductStock->stock = $remain_stock;
                                $ProductStock->save();

                                if ( $ProductStock->stock <= $ProductStock->low_stock_threshold ) {
                                    if ( !empty( json_decode( $settings[ 'notification' ] ) ) && in_array( 'enable_low_stock', json_decode( $settings[ 'notification' ] ) ) ) {
                                        if ( isset( $settings[ 'twilio_setting_enabled' ] ) && $settings[ 'twilio_setting_enabled' ] == 'on' ) {
                                            Utility::variant_low_stock_threshold( $product, $ProductStock, $theme_id, $settings );
                                        }
                                    }
                                }
                                if ( $ProductStock->stock <= $settings[ 'out_of_stock_threshold' ] ) {
                                    if ( !empty( json_decode( $settings[ 'notification' ] ) ) && in_array( 'enable_out_of_stock', json_decode( $settings[ 'notification' ] ) ) ) {
                                        if ( isset( $settings[ 'twilio_setting_enabled' ] ) && $settings[ 'twilio_setting_enabled' ] == 'on' ) {
                                            Utility::variant_out_of_stock( $product, $ProductStock, $theme_id, $settings );
                                        }
                                    }
                                }
                            } else {
                                $remain_stock = $datas->product_stock - $qtyy;
                                $datas->product_stock = $remain_stock;
                                $datas->save();
                                if ( $datas->product_stock <= $datas->low_stock_threshold ) {
                                    if ( !empty( json_decode( $settings[ 'notification' ] ) ) && in_array( 'enable_low_stock', json_decode( $settings[ 'notification' ] ) ) ) {
                                        if ( isset( $settings[ 'twilio_setting_enabled' ] ) && $settings[ 'twilio_setting_enabled' ] == 'on' ) {
                                            Utility::variant_low_stock_threshold( $product, $datas, $theme_id, $settings );
                                        }
                                    }
                                }
                                if ( $datas->product_stock <= $settings[ 'out_of_stock_threshold' ] ) {
                                    if ( !empty( json_decode( $settings[ 'notification' ] ) ) && in_array( 'enable_out_of_stock', json_decode( $settings[ 'notification' ] ) ) ) {
                                        if ( isset( $settings[ 'twilio_setting_enabled' ] ) && $settings[ 'twilio_setting_enabled' ] == 'on' ) {
                                            Utility::variant_out_of_stock( $product, $datas, $theme_id, $settings );
                                        }
                                    }
                                }
                                if ( $datas->product_stock <= $settings[ 'out_of_stock_threshold' ] && $datas->stock_order_status == 'notify_customer' ) {
                                    //Stock Mail
                                    $order_email = $billing[ 'email' ];
                                    $owner = User::find( $store->created_by );
                                    $ProductId    = '';

                                    try {
                                        $dArr = [
                                            'item_variable' => $Product->id,
                                            'product_name' => $Product->name,
                                            'customer_name' => $billing[ 'firstname' ],
                                        ];

                                        // Send Email
                                        $resp = Utility::sendEmailTemplate( 'Stock Status', $order_email, $dArr, $owner, $store, $ProductId );
                                    } catch ( \Exception $e ) {
                                        $smtp_error = __( 'E-Mail has been not sent due to SMTP configuration' );
                                    }
                                    try {
                                        $mobile_no = $request[ 'billing_info' ][ 'billing_user_telephone' ];
                                        $customer_name = $request[ 'billing_info' ][ 'firstname' ];
                                        $msg =   __( "Dear,$customer_name .Hi,We are excited to inform you that the product you have been waiting for is now back in stock.Product Name: :$Product->name. " );
                                        $resp  = Utility::SendMsgs( 'Stock Status', $mobile_no, $msg );
                                    } catch ( \Exception $e ) {
                                        $smtp_error = __( 'Invalid OAuth access token - Cannot parse access token' );
                                    }
                                }
                            }
                        } else {
                            return $this->error( [ 'message' => 'Product not found .' ] );
                        }
                    } elseif ( !empty( $product_id ) && $product_id != 0 ) {

                        if ( !empty( $Product ) ) {
                            $remain_stock = $Product->product_stock - $qtyy;
                            $Product->product_stock = $remain_stock;
                            $Product->save();
                            if ( $Product->product_stock <= $Product->low_stock_threshold ) {
                                if ( !empty( json_decode( $settings[ 'notification' ] ) ) && in_array( 'enable_low_stock', json_decode( $settings[ 'notification' ] ) ) ) {
                                    if ( isset( $settings[ 'twilio_setting_enabled' ] ) && $settings[ 'twilio_setting_enabled' ] == 'on' ) {
                                        Utility::low_stock_threshold( $Product, $theme_id, $settings );
                                    }
                                }
                            }

                            if ( $Product->product_stock <= $settings[ 'out_of_stock_threshold' ] ) {
                                if ( !empty( json_decode( $settings[ 'notification' ] ) ) && in_array( 'enable_out_of_stock', json_decode( $settings[ 'notification' ] ) ) ) {
                                    if ( isset( $settings[ 'twilio_setting_enabled' ] ) && $settings[ 'twilio_setting_enabled' ] == 'on' ) {
                                        Utility::out_of_stock( $Product, $theme_id, $settings );
                                    }
                                }
                            }

                            if ( $Product->product_stock <= $settings[ 'out_of_stock_threshold' ] && $Product->stock_order_status == 'notify_customer' ) {
                                //Stock Mail
                                $order_email = $request[ 'billing_info' ][ 'email' ];
                                $owner = Admin::find( $store->created_by );
                                // $owner_email = $owner->email;
                                $ProductId    = '';

                                try {
                                    $dArr = [
                                        'item_variable' => $Product->id,
                                        'product_name' => $Product->name,
                                        'customer_name' => $request[ 'billing_info' ][ 'firstname' ],
                                    ];

                                    // Send Email
                                    $resp = Utility::sendEmailTemplate( 'Stock Status', $order_email, $dArr, $owner, $store, $ProductId );
                                } catch ( \Exception $e ) {
                                    $smtp_error = __( 'E-Mail has been not sent due to SMTP configuration' );
                                }

                                try {
                                    $mobile_no = $request[ 'billing_info' ][ 'billing_user_telephone' ];
                                    $customer_name = $request[ 'billing_info' ][ 'firstname' ];
                                    $msg =   __( "Dear,$customer_name .Hi,We are excited to inform you that the product you have been waiting for is now back in stock.Product Name: :$Product->name. " );

                                    $resp  = Utility::SendMsgs( 'Stock Status', $mobile_no, $msg );
                                } catch ( \Exception $e ) {
                                    $smtp_error = __( 'Invalid OAuth access token - Cannot parse access token' );
                                }
                            }
                        } else {
                            return $this->error( [ 'message' => 'Product not found .' ] );
                        }
                    } else {
                        return $this->error( [ 'message' => 'Please fill proper product json field .' ] );
                    }
                }
                // remove from cart
                // Cart::where( 'customer_id', $request->customer_id )->where( 'product_id', $product_id )->where( 'variant_id', $variant_id )->where( 'theme_id', $theme_id )->where( 'store_id', $store->id )->delete();
            }
        }
        // Order stock decrease end

        if ( !empty( $prodduct_id_array ) ) {
            $prodduct_id_array = $prodduct_id_array = array_unique( $prodduct_id_array );
            $prodduct_id_array = implode( ',', $prodduct_id_array );
        } else {
            $prodduct_id_array = '';
        }

        $product_reward_point = 1;

        // add in  Order table  start
        $order = new Order();
        $order->product_order_id = $request->customer_id . date( 'YmdHis' );
        $order->order_date = date( 'Y-m-d H:i:s' );
        $order->customer_id = $request->customer_id;
        $order->product_id = $prodduct_id_array;
        $order->product_json = json_encode( $products );
        $order->product_price = $final_sub_total_price;
        $order->coupon_price = $coupon_price;
        $order->delivery_price = $delivery_price;
        $order->tax_price = $tax_price;
        if ( !\Auth::guard( 'customers' )->user() ) {
            if ( $plan->shipping_method == 'on' ) {
                $order->final_price = $data[ 'shipping_total_price' ];
            } else {
                $order->final_price = $final_price + $tax_price;
            }
        } else {
            if ( $plan->shipping_method == 'on' ) {
                $order->final_price = $data[ 'shipping_total_price' ] + $tax_price;
            } else {
                $order->final_price = $final_price + $tax_price;
            }
        }
        $order->payment_comment = $request->payment_comment;
        $order->payment_type = $request->payment_type;
        $order->payment_status = 'Unpaid';
        $order->delivery_id =  $requests_data[ 'method_id' ] ?? 0;
        $order->delivery_comment = $request->delivery_comment;
        $order->delivered_status = 0;
        $order->reward_points = SetNumber( $product_reward_point );
        $order->additional_note = $request->additional_note;
        $order->theme_id = $theme_id;
        $order->store_id = $store->id;
        $order->save();

        // Utility::paymentWebhook( $order );
        // add in  Order table end

        // add in  Order Billing Detail table start
        $billing_city_id = 0;
        if ( !empty( $billing[ 'billing_city' ] ) ) {
            $cityy = City::where( 'name', $billing[ 'billing_city' ] )->first();
            if ( !empty( $cityy ) ) {
                $billing_city_id = $cityy->id;
            } else {
                $new_billing_city = new City();
                $new_billing_city->name = $billing[ 'billing_city' ];
                $new_billing_city->state_id = $billing[ 'billing_state' ];
                $new_billing_city->country_id = $billing[ 'billing_country' ];
                $new_billing_city->save();
                $billing_city_id = $new_billing_city->id;
            }
        }

        $delivery_city_id = 0;
        if ( !empty( $billing[ 'delivery_city' ] ) ) {
            $d_cityy = City::where( 'name', $billing[ 'delivery_city' ] )->first();
            if ( !empty( $d_cityy ) ) {
                $delivery_city_id = $d_cityy->id;
            } else {
                $new_delivery_city = new City();
                $new_delivery_city->name = $billing[ 'delivery_city' ];
                $new_delivery_city->state_id = $billing[ 'delivery_state' ];
                $new_delivery_city->country_id = $billing[ 'delivery_country' ];
                $new_delivery_city->save();
                $delivery_city_id = $new_delivery_city->id;
            }
        }
        if ( is_string( $request->billing_info ) ) {
            $other_info = json_decode( $request->billing_info );
        } else {
            $other_info = is_array( $request->billing_info ) ? ( object ) $request->billing_info : $request->billing_info;
        }

        $OrderBillingDetail = new OrderBillingDetail();
        $OrderBillingDetail->order_id = $order->id;
        $OrderBillingDetail->product_order_id = $order->product_order_id;
        $OrderBillingDetail->first_name = $other_info->firstname;
        $OrderBillingDetail->last_name = $other_info->lastname;
        $OrderBillingDetail->email = $other_info->email;
        $OrderBillingDetail->telephone = $other_info->billing_user_telephone;
        $OrderBillingDetail->address = $other_info->billing_address;
        $OrderBillingDetail->postcode = $other_info->billing_postecode;
        $OrderBillingDetail->country = $other_info->billing_country;
        $OrderBillingDetail->state = $other_info->billing_state;
        $OrderBillingDetail->city = $other_info->billing_city;
        $OrderBillingDetail->theme_id = $theme_id;
        $OrderBillingDetail->delivery_address = $other_info->delivery_address;
        $OrderBillingDetail->delivery_city = $other_info->delivery_city;
        $OrderBillingDetail->delivery_postcode = $other_info->delivery_postcode;
        $OrderBillingDetail->delivery_country = $other_info->delivery_country;
        $OrderBillingDetail->delivery_state = $other_info->delivery_state;
        $OrderBillingDetail->save();
        // add in Order Billing Detail table end

        // add in Order Coupon Detail table start
        if ( !empty( $request->coupon_info ) ) {
            // coupon stock decrease start
            // $coupon_data = json_decode( $request->coupon_info, true );
            $coupon_data = $request->coupon_info;
            $Coupon = Coupon::find( $coupon_data[ 'coupon_id' ] );
            // $Coupon->coupon_limit = $Coupon->coupon_limit-1;
            // $Coupon->save();
            // coupon stock decrease end

            // Order Coupon history
            $OrderCouponDetail = new OrderCouponDetail();
            $OrderCouponDetail->order_id = $order->id;
            $OrderCouponDetail->product_order_id = $order->product_order_id;
            $OrderCouponDetail->coupon_id = $coupon_data[ 'coupon_id' ];
            $OrderCouponDetail->coupon_name = $coupon_data[ 'coupon_name' ];
            $OrderCouponDetail->coupon_code = $coupon_data[ 'coupon_code' ];
            $OrderCouponDetail->coupon_discount_type = $coupon_data[ 'coupon_discount_type' ];
            $OrderCouponDetail->coupon_discount_number = $coupon_data[ 'coupon_discount_number' ];
            $OrderCouponDetail->coupon_discount_amount = $coupon_data[ 'coupon_discount_amount' ];
            $OrderCouponDetail->coupon_final_amount = $coupon_data[ 'coupon_final_amount' ];
            $OrderCouponDetail->theme_id = $theme_id;
            $OrderCouponDetail->save();

            // Coupon history
            $UserCoupon = new UserCoupon();
            $UserCoupon->user_id = $request->user_id;
            $UserCoupon->coupon_id = $Coupon->id;
            $UserCoupon->amount = $coupon_data[ 'coupon_discount_amount' ];
            $UserCoupon->order_id = $order->id;
            $UserCoupon->date_used = now();
            $UserCoupon->theme_id = $theme_id;
            $UserCoupon->save();

            $discount_string = '-' . $coupon_data[ 'coupon_discount_amount' ];
            $CURRENCY = Utility::GetValueByName( 'CURRENCY' );
            $CURRENCY_NAME = Utility::GetValueByName( 'CURRENCY_NAME' );
            if ( $coupon_data[ 'coupon_discount_type' ] == 'flat' ) {
                $discount_string .= $CURRENCY;
            } else {
                $discount_string .= '%';
            }

            $discount_string .= ' ' . __( 'for all products' );
            $order_array[ 'coupon' ][ 'code' ] = $coupon_data[ 'coupon_code' ];
            $order_array[ 'coupon' ][ 'discount_string' ] = $discount_string;
            $order_array[ 'coupon' ][ 'price' ] = SetNumber( $coupon_data[ 'coupon_final_amount' ] );
        }
        // add in Order Coupon Detail table end

        // add in Order Tax Detail table start
        if ( !empty( $taxes ) ) {
            foreach ( $taxes as $key => $tax ) {
                $OrderTaxDetail = new OrderTaxDetail();
                $OrderTaxDetail->order_id = $order->id;
                $OrderTaxDetail->product_order_id = $order->product_order_id;
                $OrderTaxDetail->tax_id = $tax->id;
                $OrderTaxDetail->tax_name = $tax->tax_name;
                $OrderTaxDetail->tax_discount_type = $tax->tax_type;
                $OrderTaxDetail->tax_discount_amount = !empty( $tax->tax_amount ) ? $tax->tax_amount : 0;
                $OrderTaxDetail->tax_final_amount = $tax->tax_price;
                $OrderTaxDetail->theme_id = $theme_id;
                $OrderTaxDetail->save();

                $order_array[ 'tax' ][ $key ][ 'tax_string' ] = $tax->tax_string;
                $order_array[ 'tax' ][ $key ][ 'tax_price' ] = $tax->tax_price;
            }
        }

        //activity log
        ActivityLog::order_entry( [ 'customer_id' => $order->customer_id, 'order_id' => $order->product_order_id, 'order_date' => $order->order_date, 'products' => $order->product_id, 'final_price' => $order->final_price, 'payment_type' => $order->payment_type, 'theme_id' => $order->theme_id, 'store_id' => $order->store_id ] );

        //Order Mail
        $order_email = $OrderBillingDetail->email;
        $owner = User::find( $store->created_by );
        $owner_email = $owner->email;
        $order_id    = Crypt::encrypt( $order->id );

        try {
            $dArr = [
                'order_id' => $order->product_order_id,
            ];

            // Send Email
            $resp = Utility::sendEmailTemplate( 'Order Created', $order_email, $dArr, $owner, $store, $order_id );
            $resp1 = Utility::sendEmailTemplate( 'Order Created For Owner', $owner_email, $dArr, $owner, $store, $order_id );
        } catch ( \Exception $e ) {
            $smtp_error = __( 'E-Mail has been not sent due to SMTP configuration' );
        }

        foreach ( $products as $product ) {
            $product_data = Product::find( $product->product_id );

            if ( $product_data ) {
                if ( $product_data->variant_product == 0 ) {
                    if ( $product_data->track_stock == 1 ) {
                        OrderNote::order_note_data( [
                            'user_id' => !empty( $request->user_id ) ? $request->user_id : '0',
                            'order_id' => $order->id,
                            'product_name' => !empty( $product_data->name ) ? $product_data->name : '',
                            'variant_product' => $product_data->variant_product,
                            'product_stock' => !empty( $product_data->product_stock ) ? $product_data->product_stock : '',
                            'status' => 'Stock Manage',
                            'theme_id' => $order->theme_id,
                            'store_id' => $order->store_id,
                        ] );
                    }
                } else {

                    $variant_data = ProductVariant::find( $product->variant_id );
                    $variationOptions = explode( ',', $variant_data->variation_option );
                    $option = in_array( 'manage_stock', $variationOptions );
                    if ( $option == true ) {
                        OrderNote::order_note_data( [
                            'user_id' => !empty( $request->user_id ) ? $request->user_id : '0',
                            'order_id' => !empty( $order->id ) ? $order->id : '',
                            'product_name' => !empty( $product_data->name ) ? $product_data->name : '',
                            'variant_product' => $product_data->variant_product,
                            'product_variant_name' => !empty( $variant_data->variant ) ? $variant_data->variant : '',
                            'product_stock' => !empty( $variant_data->stock ) ? $variant_data->stock : '',
                            'status' => 'Stock Manage',
                            'theme_id' => $order->theme_id,
                            'store_id' => $order->store_id,
                        ] );
                    }
                }
            }
        }

        OrderNote::order_note_data( [
            'user_id' => !empty( $request->user_id ) ? $request->user_id : '0',
            'order_id' => $order->id,
            'product_order_id' => $order->product_order_id,
            'delivery_status' => 'Pending',
            'status' => 'Order Created',
            'theme_id' => $order->theme_id,
            'store_id' => $order->store_id
        ] );

        try {
            $msg = __( "Hello, Welcome to $store->name .Hi,your order id is $order->product_order_id, Thank you for Shopping We received your purchase request, we'll be in touch shortly!. " );
            $mess = Utility::SendMsgs( 'Order Created', $OrderBillingDetail->telephone, $msg );
        } catch ( \Exception $e ) {
            $smtp_error = __( 'Invalid OAuth access token - Cannot parse access token' );
        }
        // add in Order Tax Detail table end
        if ( !empty( $order ) && !empty( $OrderBillingDetail ) && !empty( $OrderTaxDetail ) ) {
            $order_array[ 'order_id' ] = $order->id;

            // Order jason
            $order_complete_json_path = base_path( 'themes/' . $theme_id . '/theme_json/order-complete.json' );
            $order_complete_json = json_decode( file_get_contents( $order_complete_json_path ), true );

            $order_complate_title = $order_complete_json[ 0 ][ 'inner-list' ][ 0 ][ 'field_default_text' ];
            $order_complate_description = $order_complete_json[ 0 ][ 'inner-list' ][ 1 ][ 'field_default_text' ];

            $setting_order_complete_json = AppSetting::where( 'theme_id', $theme_id )
            ->where( 'page_name', 'order_complete' )
            ->where( 'store_id', $store->id )
            ->first();
            if ( !empty( $setting_order_complete_json ) ) {
                $order_complete_json_array_data = json_decode( $setting_order_complete_json->theme_json, true );

                $order_complate_title = $order_complete_json_array_data[ 0 ][ 'inner-list' ][ 0 ][ 'value' ];
                $order_complate_description = $order_complete_json_array_data[ 0 ][ 'inner-list' ][ 1 ][ 'value' ];
            }
            $order_complete_json_array[ 'order-complate' ][ 'order-complate-title' ] = $order_complate_title . ' #' . $order->product_order_id;
            $order_complete_json_array[ 'order-complate' ][ 'order-complate-description' ] = $order_complate_description;

            return $this->success( [ 'order_id' => $order->id, 'slug' => $slug, 'complete_order' => $order_complete_json_array ] );
        } else {
            return $this->error( [ 'message' => 'Somthing went wrong.' ] );
        }
    }

    public function add_address( Request $request, $slug = '' ) {
        $slug = !empty( $slug ) ? $slug : '';
        $store = Store::where( 'slug', $slug )->first();
        $theme_id = !empty( $store ) ? $store->theme_id  : $request->theme_id;
        $rules = [
            'customer_id' => 'required',
            'title' => 'required',
            'address' => 'required',
            'country' => 'required|exists:countries,id',
            'state' => 'required|exists:states,id',
            'city' => 'required',
            'postcode' => 'required',
            'default_address' => 'required',
        ];

        $validator = \Validator::make( $request->all(), $rules );
        if ( $validator->fails() ) {
            $messages = $validator->getMessageBag();
            return $this->error( [
                'message' => $messages->first()
            ] );
        }

        $user = new DeliveryAddress();
        $default_address = !empty( $request->default_address ) ? 1 : 0;
        $user->title = $request->title;
        $user->country_id = $request->country;
        $user->state_id = $request->state;
        $user->city_id = $request->city;
        $user->customer_id = $request->customer_id;
        $user->title = $request->title;
        $user->address = $request->address;
        $user->postcode = $request->postcode;
        $user->default_address = $default_address;
        $user->theme_id = $theme_id;
        $user->store_id = $store->id;
        $user->save();

        if ( $default_address == 1 ) {
            $u_a_a[ 'default_address' ] = 0;
            DeliveryAddress::where( 'customer_id', $request->customer_id )->where( 'id', '!=', $user->id )->update( $u_a_a );
        }
        return $this->success( [ 'message' => 'Address added success.' ] );
    }

    public function apply_coupon( Request $request, $slug = '' ) {
        $user = auth( 'customers' )->user();
        $store = Store::where( 'slug', $slug )->first();
        $slug = !empty( $slug ) ? $slug : $store->slug;
        $theme_id = !empty( $store ) ? $store->theme_id  : $request->theme_id;

        $shipping_Methods = Session::get( 'shipping_method' );
        if ( $shipping_Methods != null ) {
            $shipp = new CartController();
            $ship = $shipp->get_shipping_data( $request, $slug );
            $shipping_Methods = $ship->original[ 'shipping_method' ] ?? [];
        }
        $CURRENCY = Utility::GetValueByName( 'CURRENCY', $theme_id );
        $couponQuery = Coupon::query();
        $code = trim( $request->coupon_code );
        $coupon = ( clone $couponQuery )->where( 'coupon_code', $code )->where( 'theme_id', $theme_id )->where( 'store_id', $store->id )->first();
        if ( !empty( $coupon ) ) {
            $coupon_count = $coupon->UsesCouponCount();
            $coupon_expiry_date = ( clone $couponQuery )->where( 'id', $coupon->id )
            ->whereDate( 'coupon_expiry_date', '>=', date( 'Y-m-d' ) )
            ->where( 'coupon_limit', '>', $coupon_count )
            ->first();
            // Usage limit per user
            $i = 0;

            if ( auth( 'customers' )->user() ) {
                $coupon_email  = $coupon->PerUsesCouponCount();
                foreach ( $coupon_email as $email ) {

                    if ( $email == auth( 'customers' )->user()->email ) {
                        $i++;
                    }
                }
            }
            if ( !empty( $coupon->coupon_limit_user ) ) {
                if ( $i  >= $coupon->coupon_limit_user ) {
                    return $this->error( [ 'message' => 'Coupon has been expiredd.' ] );
                }
            }
            if ( empty( $coupon_expiry_date ) ) {
                return $this->error( [ 'message' => 'Coupon has been expiredd.' ] );
            }
            
            if ( $coupon->free_shipping_coupon == 0 ) {
              
                if ( $request->final_sub_total != null ) {
                    $sub_total_min = $request->final_sub_total;
                } else {
                    $sub_total_min = $request->sub_total;
                }

                if ( $sub_total_min <= $coupon->maximum_spend  || $coupon->maximum_spend == null ) {
                    if ( $sub_total_min >= $coupon->minimum_spend ||  $coupon->minimum_spend == null ) {
                        if ( $request->final_sub_total != null ) {

                            $price = $request->final_sub_total;
                        } else {
                            $price = $request->sub_total;
                        }
                        $amount = $coupon->discount_amount;
                        if ( $coupon->sale_items != 0 ) {
                            $currentDate = Carbon::now()->toDateString();
                            $falsh_sale = FlashSale::where( 'theme_id', $theme_id )->where( 'store_id', $store->id )->where( 'is_active', 1 )->where( 'start_date', '<=', $currentDate )->where( 'end_date', '>=', $currentDate )->get();
                            $saleEnableArray = [];
                            foreach ( $falsh_sale as $sale ) {
                                $saleEnableArray[] = json_decode( $sale->sale_product, true );
                            }
                            $combinedArray = array_merge( ...$saleEnableArray );
                            $saleproducts = array_unique( $combinedArray );
                        } else {
                            $saleproducts = [];
                        }
                        if ( Utility::CustomerAuthCheck( $store->slug ) != true ) {
                            $response = Cart::cart_list_cookie( $request->all(), $store->id );
                            $response = json_decode( json_encode( $response ) );
                        } else {
                            $request->merge( [ 'customer_id' => auth( 'customers' )->user()->id, 'store_id' => $store->id, 'slug' => $slug, 'theme_id' => $theme_id ] );
                            $api = new ApiController();
                            $data = $api->cart_list( $request );
                            $response = $data->getData();
                        }
                        $produt_id = [];
                        foreach ( $response->data->product_list as $item ) {
                            $produt_id[] = $item->product_id;
                        }
                        $produt_ids = array_map( 'intval', $produt_id );

                        if ( empty( array_diff( $saleproducts, $produt_ids ) ) && empty( array_diff( $produt_ids, $saleproducts ) ) == true ) {
                            return $this->error( [ 'message' => 'Coupon has been expiredd.' ] );
                        }

                        if ( $coupon->coupon_type == 'flat' ) {
                            $price -= $amount;
                        }

                        if ( $coupon->coupon_type == 'percentage' ) {
                            if ( $request->final_sub_total != null ) {
                                $sub_totals = $request->final_sub_total;
                            } else {
                                $sub_totals = $request->sub_total;
                            }
                            $amount = $amount * $sub_totals / 100;
                            $price -= $amount;
                        }
                        if ( $coupon->coupon_type == 'fixed product discount' ) {
                            $coupon_applied = explode( ',', ( $coupon->applied_product ) );
                            $exclude_product = explode( ',', $coupon->exclude_product );
                            $applied_categories = explode( ',', $coupon->applied_categories );
                            $exclude_categories = explode( ',', $coupon->exclude_categories );
                            $total_price = [];
                            $quty = [];
                            $product = [];

                            foreach ( $response->data->product_list as $item ) {
                                $product[] = $item->final_price;
                            }
                            $final_sub_total_sum = array_sum( $product );

                            foreach ( $response->data->product_list as $item ) {

                                $quty[] = $item->qty;

                                $cat = Product::where( 'id', $item->product_id )->where( 'theme_id', $theme_id )->where( 'store_id', $store->id )->pluck( 'category_id' )->first();

                                if ( $coupon->sale_items != 0 ) {
                                    $currentDate = Carbon::now()->toDateString();
                                    $falsh_sale = FlashSale::where( 'theme_id', $theme_id )->where( 'store_id', $store->id )->where( 'is_active', 1 )->where( 'start_date', '<=', $currentDate )->where( 'end_date', '>=', $currentDate )->get();
                                    $saleEnableArray = [];
                                    foreach ( $falsh_sale as $sale ) {
                                        $saleEnableArray[] = json_decode( $sale->sale_product, true );
                                    }
                                    $combinedArray = array_merge( ...$saleEnableArray );
                                    $saleproduct = array_unique( $combinedArray );
                                } else {
                                    $saleproduct = [];
                                }
                                if ( $applied_categories[ 0 ] !=  '' ||  $exclude_categories[ 0 ] !=  '' ) {
                                    $common_cat = array_intersect( $applied_categories, $exclude_categories );
                                    if ( in_array( $cat, $common_cat ) ) {
                                        $apply_product  = $item->final_price;
                                        $apply_product -= 0;
                                        $total_price[] = $apply_product;
                                    } else {
                                        if ( $applied_categories[ 0 ] ==  ''  &&  $exclude_categories[ 0 ] !=  '' ) {
                                            if ( $exclude_categories[ 0 ] !=  '' && $applied_categories[ 0 ] ==  '' && $coupon_applied[ 0 ] ==  '' ) {
                                                if ( in_array( $cat, $exclude_categories ) ) {
                                                    $apply_product = $item->final_price;
                                                    $apply_product -= 0;
                                                    $total_price[] = $apply_product;
                                                } else {
                                                    if ( in_array( $item->product_id, $exclude_product ) ) {
                                                        $apply_product = $item->final_price;
                                                        $apply_product -= 0;
                                                        $total_price[] = $apply_product;
                                                    } else {
                                                        if ( in_array( $item->product_id, $saleproduct ) ) {
                                                            $apply_product  = $item->final_price;
                                                            $apply_product -= 0;
                                                            $total_price[] = $apply_product;
                                                        } else {
                                                            $apply_product = $item->final_price;
                                                            $apply_product -= $amount * $item->qty;
                                                            $total_price[] = $apply_product;
                                                        }
                                                    }
                                                }
                                            } else {
                                                if ( in_array( $cat, $exclude_categories ) ) {
                                                    if ( in_array( $item->product_id, $coupon_applied ) == true ) {
                                                        $apply_product = $item->final_price;
                                                        $apply_product -= 0;
                                                        $total_price[] = $apply_product;
                                                    } else {
                                                        if ( in_array( $item->product_id, $coupon_applied ) == true ) {
                                                            if ( in_array( $item->product_id, $saleproduct ) ) {
                                                                $apply_product  = $item->final_price;
                                                                $apply_product -= 0;
                                                                $total_price[] = $apply_product;
                                                            } else {
                                                                $apply_product = $item->final_price;
                                                                $apply_product -= $amount * $item->qty;
                                                                $total_price[] = $apply_product;
                                                            }
                                                        } else {
                                                            $apply_product = $item->final_price;
                                                            $apply_product -= 0;
                                                            $total_price[] = $apply_product;
                                                        }
                                                    }
                                                } else {
                                                    if ( in_array( $item->product_id, $coupon_applied ) == true ) {
                                                        if ( in_array( $item->product_id, $saleproduct ) ) {
                                                            $apply_product  = $item->final_price;
                                                            $apply_product -= 0;
                                                            $total_price[] = $apply_product;
                                                        } else {
                                                            $apply_product = $item->final_price;
                                                            $apply_product -= $amount * $item->qty;
                                                            $total_price[] = $apply_product;
                                                        }
                                                    } else {
                                                        $apply_product = $item->final_price;
                                                        $apply_product -= 0;
                                                        $total_price[] = $apply_product;
                                                    }
                                                }
                                            }
                                        } else {

                                            if ( in_array( $cat, $applied_categories ) ) {
                                                // if exxlude product and applied_categories
                                                if ( in_array( $item->product_id, $exclude_product ) == true ) {
                                                    $apply_product  = $item->final_price;
                                                    $apply_product -= 0;
                                                    $total_price[] = $apply_product;
                                                } else {
                                                    if ( in_array( $cat, $applied_categories ) && in_array( $item->product_id, $coupon_applied ) ) {
                                                        if ( in_array( $item->product_id, $saleproduct ) ) {
                                                            $apply_product  = $item->final_price;
                                                            $apply_product -= 0;
                                                            $total_price[] = $apply_product;
                                                        } else {
                                                            $apply_product = $item->final_price;
                                                            $apply_product -= $amount * $item->qty;
                                                            $total_price[] = $apply_product;
                                                        }
                                                    } else {
                                                        $apply_product  = $item->final_price;
                                                        $apply_product -= 0;
                                                        $total_price[] = $apply_product;
                                                    }
                                                }
                                            } else {
                                                // if not this product catgory in  applied_categories but product in  coupon_applied
                                                $apply_product  = $item->final_price;
                                                $apply_product -= 0;
                                                $total_price[] = $apply_product;
                                            }
                                        }
                                    }

                                    $price = array_sum( $total_price );
                                    $discount_amounts = $final_sub_total_sum - $price;

                                } else {
                                    if ( $coupon_applied[ 0 ] ==  '' &&  $exclude_product[ 0 ] ==  '' ) {
                                        if ( in_array( $item->product_id, $saleproduct ) ) {
                                            $apply_product  = $item->final_price;
                                            $apply_product -= 0;
                                            $total_price[] = $apply_product;
                                        } else {
                                            if ( in_array( $item->product_id, $saleproduct ) ) {
                                                $apply_product  = $item->final_price;
                                                $apply_product -= 0;
                                                $total_price[] = $apply_product;
                                            } else {
                                                $apply_product = $item->final_price;
                                                $apply_product -= $amount * $item->qty;
                                                $total_price[] = $apply_product;
                                            }
                                        }

                                        $price = array_sum( $total_price );
                                        $discount_amounts = $final_sub_total_sum - $price;
                                    } else {

                                        if ( $coupon_applied[ 0 ] ==  '' ) {
                                            if ( in_array( $item->product_id, $exclude_product ) ) {
                                                $apply_product  = $item->final_price;
                                                $apply_product -= 0;
                                                $total_price[] = $apply_product;
                                            } else {
                                                if ( in_array( $item->product_id, $saleproduct ) ) {
                                                    $apply_product  = $item->final_price;
                                                    $apply_product -= 0;
                                                    $total_price[] = $apply_product;
                                                } else {
                                                    $apply_product = $item->final_price;
                                                    $apply_product -= $amount * $item->qty;
                                                    $total_price[] = $apply_product;
                                                }
                                            }
                                        } else {

                                            $common_values = array_intersect( $coupon_applied, $exclude_product );

                                            if ( in_array( $item->product_id, $coupon_applied ) ) {

                                                if ( in_array( $item->product_id, $common_values ) ) {
                                                    $apply_product  = $item->final_price;
                                                    $apply_product  -= 0;
                                                    $total_price[] = $apply_product;
                                                } else {

                                                    if ( in_array( $item->product_id, $saleproduct ) ) {
                                                        $apply_product  = $item->final_price;
                                                        $apply_product -= 0;
                                                        $total_price[] = $apply_product;
                                                    } else {
                                                        $apply_product = $item->final_price;
                                                        $apply_product -= $amount * $item->qty;
                                                        $total_price[] = $apply_product;
                                                    }
                                                }
                                            } else {

                                                $apply_product  = $item->final_price;
                                                $apply_product -= 0;
                                                $total_price[] = $apply_product;
                                            }
                                        }

                                        $price = array_sum( $total_price );
                                        $discount_amounts = $final_sub_total_sum - $price;
                                    }
                                }
                            }

                            if ( $coupon->coupon_limit_x_item != null ) {
                                $intArray = array_map( 'intval', $quty );
                                $sum = array_sum( $intArray );
                                $total_amount  = $discount_amounts / $sum;
                                if ( $sum  >= $coupon->coupon_limit_x_item ) {

                                    $discount_amounts =  $total_amount * $coupon->coupon_limit_x_item;
                                } else {

                                    $discount_amounts =  $total_amount *  $sum;
                                }
                            }
                            if ( $coupon->discount_amount != 0 && $discount_amounts == 0 ) {
                                return $this->error( [ 'message' => ' Sorry, this coupon is not applicable to selected products.' ] );
                            }
                        } else {
                            return $this->error( [ 'message' => ' The minimum spend for this coupon is ' . SetNumberFormat( $coupon->minimum_spend ) . '.' ] );
                        }
                    } else {
                        return $this->error( [ 'message' => ' The maximum spend for this coupon is ' . SetNumberFormat( $coupon->maximum_spend ) . '.' ] );
                    }

                    $coupon_array[ 'message' ] = 'Coupon is valid.';
                    $coupon_array[ 'id' ] = $coupon->id;
                    $coupon_array[ 'name' ] = $coupon->coupon_name;
                    $coupon_array[ 'code' ] = $coupon->coupon_code;
                    $coupon_array[ 'coupon_discount_type' ] = $coupon->coupon_type;
                    if ( $coupon->coupon_type == 'fixed product discount' ) {

                        $coupon_array[ 'coupon_discount_amount' ] = $discount_amounts;
                    } else {
                        $coupon_array[ 'coupon_discount_amount' ] = $coupon->discount_amount;
                    }

                    $coupon_array[ 'coupon_end' ] = '----------------------';
                    $coupon_array[ 'original_price' ] = SetNumber( $request->sub_total );
                    $coupon_array[ 'final_price' ] = SetNumber( $price );
                    $coupon_array[ 'discount_price' ] = SetNumber( $price );
                    if ( $coupon->coupon_type == 'fixed product discount' ) {
                        $coupon_array[ 'amount' ] = SetNumber( $discount_amounts );
                        $coupon_array[ 'discount_amount_currency' ] = SetNumberFormat( $discount_amounts );
                    } else {
                        $coupon_array[ 'amount' ] = SetNumber( $amount );
                        $coupon_array[ 'discount_amount_currency' ] = SetNumberFormat( $amount );
                    }
                    $coupon_array[ 'shipping_total_price' ] = SetNumberFormat( $price );
                }
            } else {
               
                $amount = $coupon->discount_amount;
                if ( $shipping_Methods != null ) {
                    
                    foreach ( $shipping_Methods as $shippingMethod ) {
                        if ( $shippingMethod->method_name) {
                            if ( $shippingMethod->cost < $request->final_sub_total ) {                               
                                $price = $request->final_sub_total;
                                $amount = $coupon->discount_amount;
                                if ( $request->final_sub_total != null ) {                                    
                                    $sub_total_min = $request->final_sub_total;
                                } else {
                                    $sub_total_min = $request->sub_total;
                                }
                                if ( $sub_total_min <= $coupon->maximum_spend  || $coupon->maximum_spend == null ) {
                                    if ( $sub_total_min >= $coupon->minimum_spend || $coupon->minimum_spend == null ) {
                                        if ( $coupon->sale_items != 0 ) {
                                            $currentDate = Carbon::now()->toDateString();
                                            $falsh_sale = FlashSale::where( 'theme_id', $theme_id )->where( 'store_id', $store->id )->where( 'is_active', 1 )->where( 'start_date', '<=', $currentDate )->where( 'end_date', '>=', $currentDate )->get();
                                            // dd( $falsh_sale );
                                            $saleEnableArray = [];
                                            foreach ( $falsh_sale as $sale ) {
                                                $saleEnableArray[] = json_decode( $sale->sale_product, true );
                                            }
                                            $combinedArray = array_merge( ...$saleEnableArray );
                                            $saleproducts = array_unique( $combinedArray );
                                        } else {
                                            $saleproducts = [];
                                        }
                                        if ( auth('customers')->guest() ) {
                                            $response = Cart::cart_list_cookie( $request->all(), $store->id );
                                            $response = json_decode( json_encode( $response ) );
                                        } else {
                                            $request->merge( [ 'customer_id' => auth( 'customers' )->user()->id, 'store_id' => $store->id, 'slug' => $slug, 'theme_id' => $theme_id ] );
                                            $api = new ApiController();
                                            $data = $api->cart_list( $request, $slug );
                                            $response = $data->getData();
                                           
                                        }
                                        $produt_id = [];
                                        foreach ( $response->data->product_list as $item ) {
                                            $produt_id[] = $item->product_id;
                                        }
                                        $produt_ids = array_map( 'intval', $produt_id );
                                        if ( empty( array_diff( $saleproducts, $produt_ids ) ) && empty( array_diff( $produt_ids, $saleproducts ) ) == true ) {
                                            return $this->error( [ 'message' => 'Coupon has been expiredd.' ] );
                                        }
                                        if ( $coupon->coupon_type == 'flat' ) {
                                            $price -= $amount;
                                        }
                                        if ( $coupon->coupon_type == 'percentage' ) {
                                            if ( $request->final_sub_total != null ) {
                                                $sub_totals = $request->final_sub_total;
                                            } else {
                                                $sub_totals = $request->sub_total;
                                            }
                                            $amount = $amount * $sub_totals / 100;
                                            $price -= $amount;
                                        }
                                        if ( $coupon->coupon_type == 'fixed product discount' ) {
                                            $coupon_applied = explode( ',', ( $coupon->applied_product ) );
                                            $exclude_product = explode( ',', $coupon->exclude_product );
                                            $applied_categories = explode( ',', $coupon->applied_categories );
                                            $exclude_categories = explode( ',', $coupon->exclude_categories );
                                            $total_price = [];
                                            $quty = [];
                                            $product = [];

                                            foreach ( $response->data->product_list as $item ) {
                                                $product[] = $item->final_price;
                                            }
                                            $final_sub_total_sum = array_sum( $product );

                                            foreach ( $response->data->product_list as $item ) {

                                                $quty[] = $item->qty;

                                                $cat = Product::where( 'id', $item->product_id )->where( 'theme_id', $theme_id )->where( 'store_id', $store->id )->pluck( 'category_id' )->first();

                                                if ( $coupon->sale_items != 0 ) {
                                                    $currentDate = Carbon::now()->toDateString();
                                                    $falsh_sale = FlashSale::where( 'theme_id', $theme_id )->where( 'store_id', $store->id )->where( 'is_active', 1 )->where( 'start_date', '<=', $currentDate )->where( 'end_date', '>=', $currentDate )->get();
                                                    // dd( $falsh_sale );
                                                    $saleEnableArray = [];
                                                    foreach ( $falsh_sale as $sale ) {
                                                        $saleEnableArray[] = json_decode( $sale->sale_product, true );
                                                    }
                                                    $combinedArray = array_merge( ...$saleEnableArray );
                                                    $saleproduct = array_unique( $combinedArray );
                                                } else {
                                                    $saleproduct = [];
                                                }
                                                if ( $applied_categories[ 0 ] !=  '' ||  $exclude_categories[ 0 ] !=  '' ) {
                                                    $common_cat = array_intersect( $applied_categories, $exclude_categories );
                                                    if ( in_array( $cat, $common_cat ) ) {
                                                        $apply_product  = $item->final_price;
                                                        $apply_product -= 0;
                                                        $total_price[] = $apply_product;
                                                    } else {
                                                        if ( $applied_categories[ 0 ] ==  ''  &&  $exclude_categories[ 0 ] !=  '' ) {
                                                            if ( $exclude_categories[ 0 ] !=  '' && $applied_categories[ 0 ] ==  '' && $coupon_applied[ 0 ] ==  '' ) {
                                                                if ( in_array( $cat, $exclude_categories ) ) {
                                                                    $apply_product = $item->final_price;
                                                                    $apply_product -= 0;
                                                                    $total_price[] = $apply_product;
                                                                } else {
                                                                    if ( in_array( $item->product_id, $exclude_product ) ) {
                                                                        $apply_product = $item->final_price;
                                                                        $apply_product -= 0;
                                                                        $total_price[] = $apply_product;
                                                                    } else {
                                                                        if ( in_array( $item->product_id, $saleproduct ) ) {
                                                                            $apply_product  = $item->final_price;
                                                                            $apply_product -= 0;
                                                                            $total_price[] = $apply_product;
                                                                        } else {
                                                                            $apply_product = $item->final_price;
                                                                            $apply_product -= $amount * $item->qty;
                                                                            $total_price[] = $apply_product;
                                                                        }
                                                                    }
                                                                }
                                                            } else {
                                                                if ( in_array( $cat, $exclude_categories ) ) {
                                                                    if ( in_array( $item->product_id, $coupon_applied ) == true ) {

                                                                        $apply_product = $item->final_price;
                                                                        $apply_product -= 0;
                                                                        $total_price[] = $apply_product;
                                                                    } else {
                                                                        if ( in_array( $item->product_id, $coupon_applied ) == true ) {

                                                                            if ( in_array( $item->product_id, $saleproduct ) ) {
                                                                                $apply_product  = $item->final_price;
                                                                                $apply_product -= 0;
                                                                                $total_price[] = $apply_product;
                                                                            } else {
                                                                                $apply_product = $item->final_price;
                                                                                $apply_product -= $amount * $item->qty;
                                                                                $total_price[] = $apply_product;
                                                                            }
                                                                        } else {
                                                                            // dd( 'zsds' );

                                                                            $apply_product = $item->final_price;
                                                                            $apply_product -= 0;
                                                                            $total_price[] = $apply_product;
                                                                        }
                                                                    }
                                                                } else {
                                                                    if ( in_array( $item->product_id, $coupon_applied ) == true ) {

                                                                        if ( in_array( $item->product_id, $saleproduct ) ) {
                                                                            $apply_product  = $item->final_price;
                                                                            $apply_product -= 0;
                                                                            $total_price[] = $apply_product;
                                                                        } else {
                                                                            $apply_product = $item->final_price;
                                                                            $apply_product -= $amount * $item->qty;
                                                                            $total_price[] = $apply_product;
                                                                        }
                                                                    } else {

                                                                        $apply_product = $item->final_price;
                                                                        $apply_product -= 0;
                                                                        $total_price[] = $apply_product;
                                                                    }
                                                                }
                                                            }
                                                        } else {

                                                            if ( in_array( $cat, $applied_categories ) ) {
                                                                // if exxlude product and applied_categories
                                                                if ( in_array( $item->product_id, $exclude_product ) == true ) {

                                                                    $apply_product  = $item->final_price;
                                                                    $apply_product -= 0;
                                                                    $total_price[] = $apply_product;
                                                                } else {
                                                                    if ( in_array( $cat, $applied_categories ) && in_array( $item->product_id, $coupon_applied ) ) {
                                                                        if ( in_array( $item->product_id, $saleproduct ) ) {
                                                                            $apply_product  = $item->final_price;
                                                                            $apply_product -= 0;
                                                                            $total_price[] = $apply_product;
                                                                        } else {
                                                                            $apply_product = $item->final_price;
                                                                            $apply_product -= $amount * $item->qty;
                                                                            $total_price[] = $apply_product;
                                                                        }
                                                                    } else {
                                                                        $apply_product  = $item->final_price;
                                                                        $apply_product -= 0;
                                                                        $total_price[] = $apply_product;
                                                                    }
                                                                }
                                                            } else {
                                                                // if not this product catgory in  applied_categories but product in  coupon_applied
                                                                $apply_product  = $item->final_price;
                                                                $apply_product -= 0;
                                                                $total_price[] = $apply_product;
                                                            }
                                                        }
                                                    }

                                                    $price = array_sum( $total_price );
                                                    $discount_amounts = $final_sub_total_sum - $price;
                                                    // dd( $price, 'sds' );

                                                } else {
                                                    if ( $coupon_applied[ 0 ] ==  '' &&  $exclude_product[ 0 ] ==  '' ) {
                                                        // dd( $item->product_id, $saleproduct );
                                                        if ( in_array( $item->product_id, $saleproduct ) ) {
                                                            $apply_product  = $item->final_price;
                                                            $apply_product -= 0;
                                                            $total_price[] = $apply_product;
                                                        } else {
                                                            if ( in_array( $item->product_id, $saleproduct ) ) {
                                                                $apply_product  = $item->final_price;
                                                                $apply_product -= 0;
                                                                $total_price[] = $apply_product;
                                                            } else {
                                                                $apply_product = $item->final_price;
                                                                $apply_product -= $amount * $item->qty;
                                                                $total_price[] = $apply_product;
                                                            }
                                                        }

                                                        $price = array_sum( $total_price );
                                                        // dd( $price, 'sd' );
                                                        $discount_amounts = $final_sub_total_sum - $price;
                                                    } else {

                                                        if ( $coupon_applied[ 0 ] ==  '' ) {
                                                            if ( in_array( $item->product_id, $exclude_product ) ) {
                                                                $apply_product  = $item->final_price;
                                                                $apply_product -= 0;
                                                                $total_price[] = $apply_product;
                                                            } else {
                                                                if ( in_array( $item->product_id, $saleproduct ) ) {
                                                                    $apply_product  = $item->final_price;
                                                                    $apply_product -= 0;
                                                                    $total_price[] = $apply_product;
                                                                } else {
                                                                    $apply_product = $item->final_price;
                                                                    $apply_product -= $amount * $item->qty;
                                                                    $total_price[] = $apply_product;
                                                                }
                                                            }
                                                        } else {

                                                            $common_values = array_intersect( $coupon_applied, $exclude_product );

                                                            if ( in_array( $item->product_id, $coupon_applied ) ) {

                                                                if ( in_array( $item->product_id, $common_values ) ) {
                                                                    $apply_product  = $item->final_price;
                                                                    $apply_product  -= 0;
                                                                    $total_price[] = $apply_product;
                                                                } else {

                                                                    if ( in_array( $item->product_id, $saleproduct ) ) {
                                                                        $apply_product  = $item->final_price;
                                                                        $apply_product -= 0;
                                                                        $total_price[] = $apply_product;
                                                                    } else {
                                                                        $apply_product = $item->final_price;
                                                                        $apply_product -= $amount * $item->qty;
                                                                        $total_price[] = $apply_product;
                                                                    }
                                                                }
                                                            } else {

                                                                $apply_product  = $item->final_price;
                                                                $apply_product -= 0;
                                                                $total_price[] = $apply_product;
                                                            }
                                                        }

                                                        $price = array_sum( $total_price );
                                                        $discount_amounts = $final_sub_total_sum - $price;
                                                    }
                                                }
                                            }
                                            // dd( $discount_amounts, $final_sub_total_sum, $price );

                                            if ( $coupon->coupon_limit_x_item != null ) {
                                                $intArray = array_map( 'intval', $quty );
                                                $sum = array_sum( $intArray );
                                                $total_amount  = $discount_amounts / $sum;
                                                if ( $sum  >= $coupon->coupon_limit_x_item ) {

                                                    $discount_amounts =  $total_amount * $coupon->coupon_limit_x_item;
                                                } else {

                                                    $discount_amounts =  $total_amount *  $sum;
                                                }
                                            }
                                            // dd( $coupon->discount_amount != 0, $discount_amounts );
                                            if ( $coupon->discount_amount != 0 && $discount_amounts == 0 ) {
                                                return $this->error( [ 'message' => ' Sorry, this coupon is not applicable to selected products.' ] );
                                            }
                                        }
                                    } else {
                                        return $this->error( [ 'message' => ' The minimum spend for this coupon is ' . SetNumberFormat( $coupon->minimum_spend ) . '.' ] );
                                    }
                                } else {
                                    return $this->error( [ 'message' => ' The maximum spend for this coupon is ' . SetNumberFormat( $coupon->maximum_spend ) . '.' ] );
                                }

                                $coupon_array[ 'message' ] = 'Coupon is valid.';
                                $coupon_array[ 'id' ] = $coupon->id;
                                $coupon_array[ 'name' ] = $coupon->coupon_name;
                                $coupon_array[ 'code' ] = $coupon->coupon_code;
                                $coupon_array[ 'coupon_discount_type' ] = $coupon->coupon_type;
                                if ( $coupon->coupon_type == 'fixed product discount' ) {

                                    $coupon_array[ 'coupon_discount_amount' ] = $discount_amounts;
                                } else {
                                    $coupon_array[ 'coupon_discount_amount' ] = $coupon->discount_amount;
                                }
                                $coupon_array[ 'coupon_end' ] = '----------------------';
                                $coupon_array[ 'original_price' ] = SetNumber( $request->final_sub_total );
                                $coupon_array[ 'final_price' ] = SetNumber( $price );
                                $coupon_array[ 'discount_price' ] = SetNumber( $price );
                                if ( $coupon->coupon_type == 'fixed product discount' ) {
                                    $coupon_array[ 'amount' ] = SetNumber( $discount_amounts );
                                    $coupon_array[ 'discount_amount_currency' ] = SetNumberFormat( $discount_amounts );
                                } else {
                                    $coupon_array[ 'amount' ] = SetNumber( $amount );
                                    $coupon_array[ 'discount_amount_currency' ] = SetNumberFormat( $amount );
                                }
                                $coupon_array[ 'shipping_total_price' ] = SetNumberFormat( $price );
                            } else {

                                $amount = 0;
                                $coupon_array[ 'message' ] = 'Coupon is valid.';
                                $coupon_array[ 'id' ] = $coupon->id;
                                $coupon_array[ 'name' ] = $coupon->coupon_name;
                                $coupon_array[ 'code' ] = $coupon->coupon_code;
                                $coupon_array[ 'coupon_discount_type' ] = $coupon->coupon_type;
                                $coupon_array[ 'coupon_discount_amount' ] = 0;
                                $coupon_array[ 'coupon_end' ] = '----------------------';
                                $coupon_array[ 'original_price' ] = SetNumber( $request->sub_total );
                                $coupon_array[ 'final_price' ] = SetNumber( 0 );
                                $coupon_array[ 'discount_price' ] = SetNumber( 0 );
                                $coupon_array[ 'amount' ] = SetNumber( 0 );
                                $coupon_array[ 'discount_amount_currency' ] = SetNumberFormat( 0 );
                                $coupon_array[ 'shipping_total_price' ] = SetNumberFormat( 0 );
                            }
                        } else {
                            $amount = 0;
                            $discount_amounts = 0;
                            $coupon_array[ 'message' ] = 'Coupon is valid.';
                            $coupon_array[ 'id' ] = $coupon->id;
                            $coupon_array[ 'name' ] = $coupon->coupon_name;
                            $coupon_array[ 'code' ] = $coupon->coupon_code;
                            $coupon_array[ 'coupon_discount_type' ] = $coupon->coupon_type;
                            $coupon_array[ 'coupon_discount_amount' ] = 0;
                            $coupon_array[ 'coupon_end' ] = '----------------------';
                            $coupon_array[ 'original_price' ] = SetNumber( $request->sub_total );
                            $coupon_array[ 'final_price' ] = SetNumber( 0 );
                            $coupon_array[ 'discount_price' ] = SetNumber( 0 );
                            $coupon_array[ 'amount' ] = SetNumber( 0 );
                            $coupon_array[ 'discount_amount_currency' ] = SetNumberFormat( 0 );
                            $coupon_array[ 'shipping_total_price' ] = SetNumberFormat( 0 );
                        }
                    }
                } else {
                    $price = $request->final_sub_total;
                    
                    $amount = $coupon->discount_amount;
                    if ( $request->final_sub_total != null ) {

                        $sub_total_min = $request->final_sub_total;
                    } else {

                        $sub_total_min = $request->sub_total;
                    }
                    if ( $coupon->sale_items != 0 ) {
                        $currentDate = Carbon::now()->toDateString();
                        $falsh_sale = FlashSale::where( 'theme_id', $theme_id )->where( 'store_id', $store->id )->where( 'is_active', 1 )->where( 'start_date', '<=', $currentDate )->where( 'end_date', '>=', $currentDate )->get();
                        // dd( $falsh_sale );
                        $saleEnableArray = [];
                        foreach ( $falsh_sale as $sale ) {
                            $saleEnableArray[] = json_decode( $sale->sale_product, true );
                        }
                        $combinedArray = array_merge( ...$saleEnableArray );
                        $saleproducts = array_unique( $combinedArray );
                    } else {
                        $saleproducts = [];
                    }
                    if ( auth('customers')->guest() ) {
                        $response = Cart::cart_list_cookie( $request->all(), $store->id );
                        $response = json_decode( json_encode( $response ) );
                       
                    } else {
                        $address = DeliveryAddress::find($request->billing_address_id);
                        if ($address) {
                            $parms['billing_info']['delivery_country'] = $address->country_id;
                            $parms['billing_info']['delivery_state'] = $address->state_id;
                            $parms['billing_info']['delivery_city'] = $address->city_id;
                            $request->merge($parms);
                        }
                        $request->merge( [ 'customer_id' => auth( 'customers' )->user()->id, 'store_id' => $store->id, 'slug' => $slug, 'theme_id' => $theme_id ] );
                        $api = new ApiController();
                        $data = $api->cart_list( $request, $slug);
                        $response = $data->getData();
                        
                    }
                   
                    $coupon_array[ 'message' ] = 'Coupon is valid.';
                    $coupon_array[ 'id' ] = $coupon->id;
                    $coupon_array[ 'name' ] = $coupon->coupon_name;
                    $coupon_array[ 'code' ] = $coupon->coupon_code;
                    $coupon_array[ 'coupon_discount_type' ] = $coupon->coupon_type;
                    $coupon_array[ 'tax_price' ] = $response->data->tax_price ?? 0 ;
                    if ( $coupon->coupon_type == 'fixed product discount' ) {

                        $coupon_array[ 'coupon_discount_amount' ] = $response->data->total_coupon_price ?? 0;
                    } else {
                        $coupon_array[ 'coupon_discount_amount' ] = $response->data->total_coupon_price ?? 0;
                    }
                    $coupon_array[ 'coupon_end' ] = '----------------------';
                    $coupon_array[ 'original_price' ] = SetNumber( $request->final_sub_total );
                    $coupon_array[ 'final_price' ] = SetNumber( $price );
                    $coupon_array[ 'discount_price' ] = SetNumber( $response->data->total_coupon_price ?? 0 );
                    if ( $coupon->coupon_type == 'fixed product discount' ) {
                        $coupon_array[ 'amount' ] = SetNumber( $response->data->total_coupon_price ?? 0 );
                        $coupon_array[ 'discount_amount_currency' ] = SetNumberFormat( $response->data->total_coupon_price ?? 0 );
                    } else {
                        $coupon_array[ 'amount' ] = SetNumber( $response->data->total_coupon_price ?? 0 );
                        $coupon_array[ 'discount_amount_currency' ] = SetNumberFormat( $response->data->total_coupon_price ?? 0 );
                    }
                    $coupon_array[ 'shipping_total_price' ] = SetNumberFormat($response->data->total_sub_price ?? 0 );
                }
            }

            if ( $coupon->coupon_type == 'fixed product discount' ) {
                //session()->put( 'coupon_prices', $discount_amounts );
                $request->merge( [ 'total_coupon_amount' => $discount_amounts ] );
            } else {
                //session()->put( 'coupon_prices', $amount );
                $request->merge( [ 'total_coupon_amount' => $amount ] );
            }
            // $taxes = new CartController();
            // $tax_data = $taxes->get_tax_data( $request, $slug );
            $coupon_array[ 'shipping_method' ] = !empty( $shipping_Methods ) ? $shipping_Methods : '';
            $coupon_array[ 'CURRENCY' ] = $CURRENCY;
            // $cartController = new CartController();
            // $cartController->get_shipping_method( $request, $slug );
            return $this->success( $coupon_array );
        }
        return $this->error( [ 'message' => 'Invalid coupon code.' ] );
    }

    public function update_address(Request $request, $slug = '')
    {
        $store = Store::where('slug', $slug)->first();
        $theme_id = !empty($store) ? $store->theme_id  : $request->theme_id;

        $rules = [
            'address_id' => 'required',
            'customer_id' => 'required',
            'title' => 'required',
            'address' => 'required',
            'country' => 'required',
            'state' => 'required',
            'city' => 'required',
            'postcode' => 'required',
            'default_address' => 'required',
        ];

        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return $this->error([
                'message' => $messages->first()
            ]);
        }

        $default_address = !empty($request->default_address) ? 1 : 0;

        $DeliveryAddress = DeliveryAddress::find($request->address_id);
        if (!empty($DeliveryAddress)) {
            $DeliveryAddress->title = $request->title;
            $DeliveryAddress->country_id = $request->country;
            $DeliveryAddress->state_id = $request->state;
            $DeliveryAddress->city_id = $request->city;
            $DeliveryAddress->customer_id = $request->customer_id;
            $DeliveryAddress->title = $request->title;
            $DeliveryAddress->address = $request->address;
            $DeliveryAddress->postcode = $request->postcode;
            $DeliveryAddress->default_address = $default_address;
            $DeliveryAddress->save();

            if ($default_address == 1) {
                $u_a_a['default_address'] = 0;
                DeliveryAddress::where('customer_id', $request->customer_id)->where('id', '!=', $request->address_id)->update($u_a_a);
            }

            return $this->success(['message' => 'Address update successfully.']);
        } else {
            return $this->error(['message' => 'Address not found.']);
        }
    }
}
