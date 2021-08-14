<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ec_customer extends Model
{

   public function Review()
   {
       return $this->belongsToMany('App\Models\Ec_review');
   }
    
}
