<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasFactory;

    use HasTranslations;
    public $translatable = [
        'page_name', 'page_content', 'page_meta_title', 'page_meta_description'
    ];

    protected $fillable = [
        'page_name', 'page_slug', 'page_content', 'page_meta_title', 'page_meta_description', 'page_meta_keywords', 'page_status','theme_id', 'store_id'
    ];
}
