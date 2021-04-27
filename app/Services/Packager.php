<?php

namespace App\Services;

use App\Models\Pallet;
use App\Models\Product;
use Illuminate\Support\Collection;

class Packager
{
    /** @var Collection */
    private $products;
    /** @var Collection */
    private $pallets;

    public function __construct()
    {
        $this->products = collect();
        $this->pallets = collect();
    }

    public function addProduct(Product $product, int $quantity = 1): void
    {
        for ($i = 0; $i < $quantity; $i++) {
            $this->products->push($product);
        }
    }

    public function setProducts(iterable $products): void
    {
        $this->products = collect();

        foreach ($products as $product) {
            $this->addProduct($product);
        }
    }

    public function addPallet(Pallet $pallet): void
    {
        $this->pallets->push($pallet);
    }

    public function setPallets(iterable $pallets): void
    {
        $this->pallets = collect();

        foreach ($pallets as $pallet) {
            $this->addPallet($pallet);
        }
    }

    public function pack(): Collection
    {
        $optimalPackedPallets = null;

        // Go through pallets with volume low -> high
        $palletsAscByVolume = $this->pallets->sortBy('volume');
        // Start packing products from volume high -> low
        $productsDescByVolume = $this->products->sortByDesc('volume');

        foreach ($palletsAscByVolume as $pallet) {
            $palletPacker = new PalletPacker($pallet, $productsDescByVolume);
            $packedPallets = $palletPacker->pack();

            if (!$optimalPackedPallets) {
                $optimalPackedPallets = $packedPallets;
                continue;
            }

            if ($optimalPackedPallets->count() <= $packedPallets->count()) {
                continue;
            }

            $optimalPackedPallets = $packedPallets;
        }

        return $optimalPackedPallets;
    }
}
