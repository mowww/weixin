<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class answer extends Model
{
    protected $table = 'answer';
  protected $guarded = ['id']; 
public $timestamps = false;
}