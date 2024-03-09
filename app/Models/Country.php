<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\State;
use Spatie\Translatable\HasTranslations;

class Country extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'name'
    ];
    public $translatable = ['name'];

    public function states()
    {
        return $this->hasMany(State::class, 'country_id');
    }
}
