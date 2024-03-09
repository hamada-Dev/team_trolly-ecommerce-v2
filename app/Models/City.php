<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class City extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'name', 'state_id', 'state_code', 'country_id', 'country_code', 'latitude', 'longitude', 'flag', 'wikiDataId'
    ];
    public $translatable = ['name'];

    public function country()
    {
        return $this->hasOne(Country::class, 'id', 'country_id');
    }

    public function state()
    {
        return $this->hasOne(State::class, 'id', 'state_id');
    }
}
