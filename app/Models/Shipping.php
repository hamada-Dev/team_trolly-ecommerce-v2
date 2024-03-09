<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Shipping extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'name', 'description', 'slug', 'theme_id', 'store_id'
    ];

    public $translatable = ['name', 'description'];
}
