<?php

namespace App\Models;

use App\Traits\ApiQuery;
use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveTelevision extends Model {
    use HasFactory, GlobalStatus, ApiQuery;

    public function category() {
        return $this->belongsTo(ChannelCategory::class, 'channel_category_id');
    }
}
