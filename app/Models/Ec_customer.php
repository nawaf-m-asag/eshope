<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ec_customer extends Model
{

   
    protected $fillable = [      
   
        'country_code',  
        'name',
        'phone',    
        'dob',
        'email',
        'confirmed_at',
        'password'
      
    ];

   
    
}
