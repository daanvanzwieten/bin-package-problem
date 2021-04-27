<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property int $length
 * @property int $width
 * @property int $height
 * @property-read int $volume
 */
class Product extends Model
{
    protected $fillable = [
        'length',
        'width',
        'height',
    ];

    public static function instance(int $length, int $width, int $height): self
    {
        return new static(compact('length', 'width', 'height'));
    }

    public function getVolumeAttribute(): int
    {
        return $this->length * $this->width * $this->height;
    }
}
