<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryBoy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'profile_image', 'type', 'password', 'contact','created_by','theme_id','store_id'
    ];
}
