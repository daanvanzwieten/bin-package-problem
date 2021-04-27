<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property int $x
 * @property int $width
 * @property int $y
 * @property int $length
 * @property int $z
 * @property int $height
 * @property-read int $absolute_height
 */
class PackedProduct extends Model
{
    protected $fillable = [
        'x',
        'width',
        'y',
        'length',
        'z',
        'height',
    ];

    public static function fromProductAndCoordinates(Product $product, array $coordinates): self
    {
        return new static([
            'x' => $coordinates['x'],
            'width' => $coordinates['width'],
            'y' => $coordinates['y'],
            'length' => $coordinates['length'],
            'z' => $coordinates['z'],
            'height' => $product->height,
        ]);
    }

    public function getAbsoluteHeightAttribute(): int
    {
        return $this->z + $this->height;
    }
}
