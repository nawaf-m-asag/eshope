<?php

namespace Database\Seeders;

use Botble\Ecommerce\Models\StoreLocator;
use Illuminate\Database\Seeder;

class StoreLocatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        StoreLocator::truncate();

        StoreLocator::create([
            'name'                 => 'Martfury',
            'email'                => 'sales@botble.com',
            'phone'                => '1800979769',
            'address'              => '502 New Street',
            'state'                => 'Brighton VIC',
            'city'                 => 'Brighton VIC',
            'country'              => 'AU',
            'is_primary'           => 1,
            'is_shipping_location' => 1,
        ]);
    }
}
