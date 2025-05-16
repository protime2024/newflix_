<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $table = 'languages';

    protected $fillable = [
        'name',
        'code',
        'is_default',
        'image'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Accessor to get full image URL if needed
    public function getImageUrlAttribute()
    {
        return $this->image ? getFilePath('language') . '/' . $this->image : null;
    }

    // Scope to get default language
    public function scopeDefault($query)
    {
        return $query->where('is_default', 1);
    }

    // Ensure only one default language exists
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->is_default) {
                static::where('id', '!=', $model->id)->update(['is_default' => false]);
            }
        });
    }
}