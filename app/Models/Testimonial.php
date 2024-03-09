<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Testimonial extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'user_id', 'maincategory_id','subcategory_id', 'product_id', 'rating_no', 'title', 'description', 'status', 'theme_id'
    ];
    public $translatable = ['title', 'description'];
    public function MainCategoryData()
    {
        return $this->hasOne(MainCategory::class, 'id', 'maincategory_id');
    }
    public function SubCategoryData()
    {
        return $this->hasOne(SubCategory::class, 'id', 'subcategory_id');
    }

    public function ProductData()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    public function UserData()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public static function ProductReview($currentTheme, $no = 2, $id)
    {
        $product_review = Testimonial::where('product_id',$id)->where('theme_id', $currentTheme)->first();
        return view('front_end.sections.pages.product_review', compact('product_review'))->render();
    }
}
