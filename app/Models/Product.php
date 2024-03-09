<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\MainCategory;
use App\Models\TaxOption;

use DB;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations;
    use HasFactory;

    public $translatable = ['name', 'description','specification','detail'];
    protected $filable = [
        'name',
        'slug'
    ];


    protected $appends = ["in_cart", "in_whishlist"];


    public static function slugs($data)
    {
        $slug = '';
        $slug = strtolower(str_replace(" ", "-",$data));
        $table = with(new Product)->getTable();

        $allSlugs = self::getRelatedSlugs($table, $slug ,$id = 0);

        if (!$allSlugs->contains('slug', $slug)) {
            return $slug;
        }
        for ($i = 1; $i <= 100; $i++) {
            $newSlug = $slug . '-' . $i;
            if (!$allSlugs->contains('slug', $newSlug)) {
                return $newSlug;

            }
        }
    }

    protected static function getRelatedSlugs($table, $slug, $id = 0)
    {
        return DB::table($table)->select()->where('slug', 'like', $slug . '%')->where('id', '<>', $id)->get();
    }

    public function ProductData()
    {
        return $this->hasOne(MainCategory::class, 'id', 'maincategory_id');
    }

    public function reviewData()
    {
        return $this->hasMany(Testimonial::class, 'id', 'product_id');
    }

    public function tagData()
    {
        if($this->tag_id) {
            $tagIds = explode(',', $this->tag_id);
            $tags = Tag::whereIn('id', $tagIds)->select('id', 'name')->get()->toArray();
            return $tags;
        }
        return [];
    }


    public function SubCategoryctData()
    {
        return $this->hasOne(SubCategory::class, 'id', 'subcategory_id');
    }
    public function ProductVariant($sku_name = '')
    {
        $ProductStock = ProductVariant::where('product_id', $this->id)->where('variant', $sku_name)->first();
        return $ProductStock;
    }

    public static function bestseller_guest($theme_id = '', $storeId, $per_page = '6', $destination = 'app')
    {
        $bestseller_array_query = Product::where('theme_id', $theme_id)->where('store_id', $storeId)->where('status' , 1);
        if (!empty($destination) && $destination == 'web') {
            if ($per_page != 'all') {
                $bestseller_array_query->limit($per_page);
            }
            $bestseller_array = $bestseller_array_query->inRandomOrder()->get();
        } else {
            $bestseller_array = $bestseller_array_query->paginate($per_page);
        }
        // $bestseller_array = Product::where('theme_id', $theme_id)->where('tag_api', 'best seller')->paginate(6);
        $cart = 0;

        $return['status'] = 'success';
        $return['bestseller_array'] = $bestseller_array;
        $return['cart'] = $cart;
        return $return;
    }

    public static function Sub_image($product_id = 0)
    {
        $return['status'] = false;
        $return['data'] = [];
        $ProductImage = ProductImage::where('product_id', $product_id)->get();
        if (!empty($ProductImage)) {
            $return['status'] = true;
            $return['data'] = $ProductImage;
        }
        return $return;
    }

    public function getInWhishlistAttribute()
    {
        $id = !empty(auth('customers')->user()) ? auth('customers')->user()->id : 0;
        return Wishlist::where('product_id', $this->id)->where('customer_id', $id)->exists();
    }

    public function getInCartAttribute()
    {
        $id = !empty(auth('customers')->user()) ? auth('customers')->user()->id : 0;
        return Cart::where('product_id', $this->id)->where('customer_id', $id)->exists();
    }


    public static function productSalesPage($theme, $slug, $productId, $details = false)
    {
        $storeId = getCurrenctStoreId($slug);
        date_default_timezone_set('Asia/Kolkata');
        $currentDateTime = \Carbon\Carbon::now();
        $sale_product = FlashSale::where('theme_id', $theme)
            ->where('store_id', $storeId)
            ->where('is_active', 1)
            ->get();
        $latestSales = [];

        foreach ($sale_product as $flashsale) {
            $saleEnableArray = json_decode($flashsale->sale_product, true);
            $startDate = \Carbon\Carbon::parse($flashsale->start_date . ' ' . $flashsale->start_time);
            $endDate = \Carbon\Carbon::parse($flashsale->end_date . ' ' . $flashsale->end_time);

            if ($endDate < $startDate) {
                $endDate->addDay();
            }
            $currentDateTime->setTimezone($startDate->getTimezone());

            if ($currentDateTime >= $startDate && $currentDateTime <= $endDate) {
                if (is_array($saleEnableArray) && in_array($productId, $saleEnableArray)) {
                    $latestSales[$productId] = [
                        'discount_type' => $flashsale->discount_type,
                        'discount_amount' => $flashsale->discount_amount,
                        'start_date' => $flashsale->start_date,
                        'end_date' => $flashsale->end_date,
                        'start_time' => $flashsale->start_time,
                        'end_time' => $flashsale->end_time,
                    ];
                }
            }
        }

        if ($details) {
            return view('front_end.sections.product_detail_sale_lable', compact('latestSales'))->render();
        }
        return view('front_end.sections.product_sales', compact('latestSales'))->render();
    }

    public static function productSalesTag($theme, $slug, $productId)
    {
        $storeId = getCurrenctStoreId($slug);
        date_default_timezone_set('Asia/Kolkata');
        $currentDateTime = \Carbon\Carbon::now();
        $sale_product = FlashSale::where('theme_id', $theme)
            ->where('store_id', $storeId)
            ->where('is_active', 1)
            ->get();
        $latestSales = [];

        foreach ($sale_product as $flashsale) {
            $saleEnableArray = json_decode($flashsale->sale_product, true);
            $startDate = \Carbon\Carbon::parse($flashsale->start_date . ' ' . $flashsale->start_time);
            $endDate = \Carbon\Carbon::parse($flashsale->end_date . ' ' . $flashsale->end_time);

            if ($endDate < $startDate) {
                $endDate->addDay();
            }
            $currentDateTime->setTimezone($startDate->getTimezone());

            if ($currentDateTime >= $startDate && $currentDateTime <= $endDate) {
                if (is_array($saleEnableArray) && in_array($productId, $saleEnableArray)) {
                    $latestSales[$productId] = [
                        'discount_type' => $flashsale->discount_type,
                        'discount_amount' => $flashsale->discount_amount,
                        'start_date' => $flashsale->start_date,
                        'end_date' => $flashsale->end_date,
                        'start_time' => $flashsale->start_time,
                        'end_time' => $flashsale->end_time,
                    ];
                }
            }
        }

       return $latestSales;
    }

    public static function ProductPrice($theme, $slug, $productId)
    {
        $store = Store::where('slug', $slug)->first();
        $storeId = getCurrenctStoreId($slug);
        $product = Product::find($productId);
        $price = $product->sale_price;
        $tax = Tax::find($product->tax_id);
        $taxmethod = TaxMethod::where('tax_id',$product->tax_id)->where('theme_id', $theme)->where('store_id', $storeId)->orderBy('priority', 'asc')->first();
        $tax_option = TaxOption::where('store_id', $store->id)
        ->where('theme_id', $store->theme_id)
        ->pluck('value', 'name')->toArray();
        if($taxmethod)
        {
            if($tax_option['price_type'] ?? '' == 'inclusive' && $tax_option['shop_price'] ?? '' == 'including')
            {
                if($product->variant_product == 0)
                {
                    $tax_price = $taxmethod->tax_rate * $product->sale_price / 100;
                    if($tax_option['round_tax'] == 1)
                    {
                        $include_price = $product->sale_price + $tax_price;
                        $price = round($include_price);
                    }
                    else{
                        $price = $product->sale_price + $tax_price;
                    }
                }else{

                }

            }else{
                $price = $product->sale_price;
            }
        }
        return $price;
    }

    public static function GetLatestProduct($theme, $slug = '', $no = 2)
    {
        $storeId = getCurrenctStoreId($slug);
        $lat_products = Product::orderBy('created_at', 'Desc')->where('theme_id', $theme)->where('store_id', $storeId)->where('status' , 1)->limit($no)->get();
        return view('front_end.sections.homepage_latest_product', compact('theme','slug', 'lat_products'))->render();
    }

    public static function GetLatProduct($theme, $slug = '', $no = 1)
    {
        $storeId = getCurrenctStoreId($slug);
        $latest_pro = Product::orderBy('created_at', 'Desc')->where('theme_id', $theme)->where('store_id', $storeId)->where('status' , 1)->limit($no)->first();
        return view('front_end.sections.home_latest_product', compact('theme','slug', 'latest_pro'))->render();
    }

    public static function ProductPageBestseller($theme, $slug = '')
    {
        $storeId = getCurrenctStoreId($slug);
        $MainCategory = MainCategory::where('theme_id', $theme)->where('store_id', $storeId)->get()->pluck('name', 'id');
        $MainCategory->prepend('All Products', '0');
        $homeproducts = Product::where('theme_id', $theme)->where('store_id', $storeId)->where('status' , 1)->get();
        return view('front_end.sections.bestseller_product', compact('theme','homeproducts', 'MainCategory', 'slug'))->render();
    }

    // Calculate Product Inclusive amount
    public static function productTaxIncludeAmount($theme, $slug = '', $amount, $taxId)
    {
        $storeId = getCurrenctStoreId($slug);
        $tax_price = 0;
        $tax_option = TaxOption::where('store_id',$storeId)
        ->where('theme_id',$theme)
        ->pluck('value', 'name')->toArray();
        if ($tax_option && $tax_option['price_type'] == 'inclusive') {
            $tax_price = Cart::getProductTaxAmount($taxId, $amount, $storeId, $theme, null, null, null, true);
        }

        return $amount + $tax_price;
    }

    public function getOriginalPriceAttribute()
    {
        $variantId = $this->getAttribute('variantId');
        $variantName = $this->getAttribute('variantName');
        $variant_data = ProductVariant::where('variant', $variantName)->where('product_id', $this->id)->first();

        $variant_id = !empty($variantId) ? $variantId : ($variant_data ? $variant_data->id : null);
        $price = $this->price;
        if ($this->variant_product == 1) {
            $ProductStock = ProductVariant::find($variant_id);
            $price = 0;
            if (!empty($ProductStock)) {
                if ($ProductStock->price == 0 && $ProductStock->variation_price == 0) {
                    $price = $this->price;
                } else {
                    $price = $ProductStock->variation_price;
                }
            }
        }
        return SetNumber($price);
    }

    public function getFinalPriceAttribute()
    {
        $variantId = $this->getAttribute('variantId');
        $variantName = $this->getAttribute('variantName');
        $variant_data = ProductVariant::where('variant', $variantName)->where('product_id', $this->id)->first();

        $variant_id = !empty($variantId) ? $variantId : ($variant_data ? $variant_data->id : null);
        $price = $this->price;
        $discount_type = $this->discount_type;
        $discount_amount = $this->discount_amount;
        date_default_timezone_set('Asia/Kolkata');
        $currentDateTime = \Carbon\Carbon::now()->toDateTimeString();
        $sale_product = \App\Models\FlashSale::where('theme_id', APP_THEME())
            ->where('store_id', getCurrentStore())
            ->get();
        $latestSales = [];
        foreach ($sale_product as $flashsale) {
            if($flashsale->is_active == 1)
            {
                $saleEnableArray = json_decode($flashsale->sale_product, true);
                $startDate = \Carbon\Carbon::parse($flashsale['start_date'] . ' ' . $flashsale['start_time']);
                $endDate = \Carbon\Carbon::parse($flashsale['end_date'] . ' ' . $flashsale['end_time']);

                if ($endDate < $startDate) {
                    $endDate->addDay();
                }

                if ($currentDateTime >= $startDate && $currentDateTime <= $endDate) {
                    if (is_array($saleEnableArray) && in_array($this->id, $saleEnableArray)) {
                        $latestSales[$this->id] = [
                            'discount_type' => $flashsale->discount_type,
                            'discount_amount' => $flashsale->discount_amount,
                        ];
                    }
                }
            }
        }
        if ($latestSales == null) {
            $latestSales[$this->id] = [
                'discount_type' => $this->discount_type,
                'discount_amount' => $this->discount_amount,
            ];
        }
        foreach ($latestSales as $productId => $saleData) {

            if ($this->variant_product == 0) {
                if ($saleData['discount_type'] == 'flat') {
                    $price = $this->price - $saleData['discount_amount'];
                }
                if ($saleData['discount_type'] == 'percentage') {
                    $discount_price =  $this->price * $saleData['discount_amount'] / 100;
                    $price = $this->price - $discount_price;
                }
            } else {
                $product_variant_data = ProductVariant::where('product_id', $this->id)->where('id',$variant_id)->first();

                if ($product_variant_data) {
                    if ($saleData['discount_type'] == 'flat') {
                        $price = $product_variant_data->price - $saleData['discount_amount'];
                    } elseif ($saleData['discount_type'] == 'percentage') {
                        $discount_price = $product_variant_data->price * $saleData['discount_amount'] / 100;
                        $price = $product_variant_data->price - $discount_price;
                    }else{
                        $price = $product_variant_data->price;
                    }
                }
            }
        }
        return SetNumber($price);
    }
}
