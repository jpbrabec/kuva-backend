<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
  protected $table = "photo_reports";
  public function photo() {
    return $this->belongsTo('App\Models\Photo');
  }
}
