<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Spatie\Translatable\HasTranslations;

class State extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'name', 'country_id', 'country_code', 'fips_code', 'iso2', 'type', 'latitude', 'longitude', 'flag', 'wikiDataId'
    ];

    public $translatable = ['name'];

    public function country()
    {
        return $this->hasOne(Country::class, 'id', 'country_id');
    }


    public function getState($country_id){
        try {
            $result = $this->where('country_id',$country_id)->get();
            if (isset($result) && !empty($result)){
                return $result;
            }
            return null;
        }catch (QueryException $ex){
            return null;
        }
    }
}

