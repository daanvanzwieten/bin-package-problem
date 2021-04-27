<?php

namespace Tests\Unit;

use App\Models\Pallet;
use App\Models\Product;
use App\Services\Packager;
use PHPUnit\Framework\TestCase;

class PackagerTest extends TestCase
{
    public function test_packager_packs_single_product()
    {
        $packager = new Packager();
        $packager->setPallets([
            Pallet::instance(100, 100, 50),
            Pallet::instance(150, 150, 75),
            Pallet::instance(200, 200, 100),
        ]);
        $packager->setProducts([
            Product::instance(60, 60, 10),
        ]);

        $packedPallets = $packager->pack();

        $this->assertCount(1, $packedPallets);

        $packedPallet = $packedPallets->first();

        $this->assertEquals(100, $packedPallet->width);
    }

    public function test_packager_packs_multiple_products()
    {
        $packager = new Packager();
        $packager->setPallets([
            Pallet::instance(100, 100, 50),
            Pallet::instance(150, 150, 75),
            Pallet::instance(200, 200, 100),
        ]);
        $packager->setProducts([
            Product::instance(50, 100, 30),
            Product::instance(100, 50, 30),
            Product::instance(60, 60, 30),
        ]);

        $packedPallets = $packager->pack();

        $this->assertCount(1, $packedPallets);

        $packedPallet = $packedPallets->first();

        $this->assertEquals(150, $packedPallet->width);
    }

    public function test_packager_packs_multiple_products_with_different_sizes()
    {
        $packager = new Packager();
        $packager->setPallets([
            Pallet::instance(100, 100, 50),
            Pallet::instance(150, 150, 75),
            Pallet::instance(200, 200, 100),
        ]);
        $packager->setProducts([
            Product::instance(50, 100, 25),
            Product::instance(100, 50, 25),
            Product::instance(40, 100, 25),
            Product::instance(100, 30, 25),
            Product::instance(100, 30, 25),
        ]);

        $packedPallets = $packager->pack();

        $this->assertCount(1, $packedPallets);

        $packedPallet = $packedPallets->first();

        $this->assertEquals(100, $packedPallet->width);
    }

    public function test_packager_chooses_smallest_pallet()
    {
        $packager = new Packager();
        $packager->setPallets([
            Pallet::instance(100, 100, 50),
            Pallet::instance(110, 110, 50),
        ]);
        $packager->setProducts([
            Product::instance(60, 60, 30),
            Product::instance(60, 60, 30),
        ]);

        $packedPallets = $packager->pack();

        $this->assertCount(2, $packedPallets);

        $packedPallet = $packedPallets->first();

        $this->assertEquals(100, $packedPallet->width);
    }
}
