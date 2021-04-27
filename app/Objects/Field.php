<?php

namespace App\Objects;

use App\Models\Product;

class Field
{
    public $x;
    public $width;
    public $y;
    public $length;
    public $z;

    public function __construct(int $x, int $width, int $y, int $length, int $z)
    {
        $this->x = $x;
        $this->width = $width;
        $this->y = $y;
        $this->length = $length;
        $this->z = $z;
    }

    public function isPerfectFit(Product $product)
    {
        if ($coordinates = $this->fitsPerfectly($product->width, $product->length)) {
            return $coordinates;
        }

        if ($coordinates = $this->fitsPerfectly($product->length, $product->width)) {
            return $coordinates;
        }

        return false;
    }

    public function canContain(Product $product)
    {
        if ($coordinates = $this->fits($product->width, $product->length)) {
            return $coordinates;
        }

        if ($coordinates = $this->fits($product->length, $product->width)) {
            return $coordinates;
        }

        return false;
    }

    public function fits(int $width, int $length)
    {
        if ($this->width < $width || $this->length < $length) {
            return false;
        }

        return ['x' => $this->x, 'width' => $width, 'y' => $this->y, 'length' => $length, 'z' => $this->z];
    }

    public function fitsPerfectly(int $width, int $length)
    {
        if ($this->width === $width && $this->length === $length) {
            return ['x' => $this->x, 'width' => $width, 'y' => $this->y, 'length' => $length, 'z' => $this->z];
        }

        return false;
    }
}
