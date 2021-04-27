<?php

namespace App\Services;

use App\Models\PackedProduct;
use App\Models\Pallet;
use App\Models\Product;
use App\Objects\Field;
use Illuminate\Support\Collection;

class PalletPacker
{
    /** @var Pallet */
    private $pallet;
    /** @var Collection */
    private $packedPallets;
    /** @var Collection */
    private $products;
    /** @var Collection */
    private $packedProducts;
    /** @var Collection */
    private $fields;
    /** @var int */
    private $currentLayerHeight;

    public function __construct(Pallet $pallet, iterable $products)
    {
        $this->pallet = $pallet;
        $this->packedPallets = collect();
        $this->products = collect($products);
        $this->packedProducts = collect();
    }

    public function pack(): Collection
    {
        while ($this->products->isNotEmpty()) {
            $this->packPallet();

            $this->handleFullPallet();
        }

        return $this->packedPallets;
    }

    public function packPallet(): void
    {
        if (!$this->createNewLayer()) {
            return;
        }

        foreach ($this->products as $key => $product) {
            if ($packedProduct = $this->place($product)) {
                $this->packedProducts->push($packedProduct);
                $this->products->forget($key);

                $this->calculateFields();
            }
        }

        if ($this->products->isNotEmpty() && $this->currentLayerHeight < $this->pallet->height) {
            $this->packPallet();
        }
    }

    private function createNewLayer(): bool
    {
        $this->setNextLayerHeight();

        if ($this->currentLayerHeight === null) {
            return false;
        }

        $this->calculateFields();

        return true;
    }

    private function setNextLayerHeight(): void
    {
        if ($this->packedProducts->isEmpty()) {
            $this->currentLayerHeight = 0;
        } else {
            $this->currentLayerHeight = $this->packedProducts->where('absolute_height', '>', $this->currentLayerHeight)->min('absolute_height');
        }
    }

    private function calculateFields(): void
    {
        $this->fields = collect();

        $products = $this->packedProducts
            ->where('z', '<=', $this->currentLayerHeight)
            ->where('absolute_height', '>', $this->currentLayerHeight);

        if ($products->isEmpty()) {
            $this->fields->push(new Field(0, $this->pallet->width, 0, $this->pallet->length, $this->currentLayerHeight));
            return;
        }

        $this->createFields($products);
    }

    private function createFields(Collection $products): void
    {
        $yStartingPoint = 0;

        $sorted = $products->sortBy([
            ['y', 'asc'],
            ['x', 'asc'],
        ]);

        while ($yStartingPoint < $this->pallet->length) {
            $field = new Field(0, $this->pallet->width, $yStartingPoint, $this->pallet->length - $yStartingPoint, $this->currentLayerHeight);

            foreach ($sorted as $product) {
                if (!$this->collides($field, $product)) {
                    continue;
                }

                if ($field->y < $product->y) {
                    $yField = clone $field;
                    $yField->length = $product->y - $yField->y;

                    // Find product with x > $product->x and set width to that
                    $nextCollision = $products
                        ->sortBy('x')
                        ->where('x', '>', $product->x)
                        ->where('y', '<', $product->y)
                        ->filter(function ($product) use ($yField) {
                            return ($product->y + $product->length) > $yField->y;
                        })
                        ->first();

                    if ($nextCollision) {
                        $yField->width = $nextCollision->x - $yField->x;
                    }

                    $this->fields->push($yField);
                }

                if ($field->x < $product->x) {
                    $xField = clone $field;
                    $xField->width = $product->x - $xField->x;
                    $this->fields->push($xField);
                }

                $field->x = $product->x + $product->width;
                $field->width = $this->pallet->width - $field->x;

                if ($field->x === $this->pallet->width) {
                    break;
                }
            }

            if ($field->x !== $this->pallet->width) {
                $this->fields->push($field);
            }

            $yProduct = $products->filter(function (PackedProduct  $product) use ($field) {
                return $product->y + $product->length > $field->y;
            })->sortBy(function (PackedProduct  $product) {
                return $product->y + $product->length;
            })->first();

            $yStartingPoint = $yProduct ? $yProduct->y + $yProduct->length : $this->pallet->length;
        }
    }

    private function collides(Field $field, PackedProduct $packedProduct): bool
    {
        return $this->collidesX($field, $packedProduct) && $this->collidesY($field, $packedProduct);
    }

    private function collidesY(Field $field, PackedProduct $packedProduct): bool
    {
        return $packedProduct->y < ($field->y + $field->length) && ($packedProduct->y + $packedProduct->length) > $field->y;
    }

    private function collidesX(Field $field, PackedProduct $packedProduct): bool
    {
        return $packedProduct->x < ($field->x + $field->width) && ($packedProduct->x + $packedProduct->width) > $field->x;
    }

    private function place(Product $product): ?PackedProduct
    {
        foreach ($this->fields as $field) {
            if ($field->z + $product->height > $this->pallet->height) {
                continue;
            }

            if ($coordinates = $field->isPerfectFit($product)) {
                return PackedProduct::fromProductAndCoordinates($product, $coordinates);
            }

            if ($coordinates = $field->canContain($product)) {
                return PackedProduct::fromProductAndCoordinates($product, $coordinates);
            }
        }

        return null;
    }

    private function handleFullPallet(): void
    {
        $this->packedPallets->push(clone $this->pallet);
        $this->currentLayerHeight = 0;
        $this->packedProducts = collect();
    }
}
