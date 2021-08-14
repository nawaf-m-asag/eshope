<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Product;
use Botble\Marketplace\Models\Store;
use Botble\Slug\Models\Slug;
use Faker\Factory;
use Illuminate\Support\Str;
use SlugHelper;

class MarketplaceSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Customer::where('is_vendor', 1)->update(['is_vendor' => 0]);
        Store::truncate();
        Slug::where('reference_type', Store::class)->delete();

        $vendors = [];
        foreach (Customer::get() as $customer) {
            $customer->is_vendor = rand(0, 1);
            $customer->save();

            if ($customer->is_vendor) {
                $vendors[] = $customer->id;
            }
        }

        $faker = Factory::create();

        for ($i = 0; $i < count($vendors); $i++) {
            $store = Store::create([
                'name'        => $faker->name,
                'email'       => $faker->safeEmail,
                'phone'       => $faker->e164PhoneNumber,
                'logo'        => 'customers/' . ($i + 1) . '.jpg',
                'country'     => $faker->countryCode,
                'state'       => $faker->state,
                'city'        => $faker->city,
                'address'     => $faker->streetAddress,
                'customer_id' => $vendors[$i],
                'description' => $faker->text(50),
                'content'     => $faker->text(150),
            ]);

            Slug::create([
                'reference_type' => Store::class,
                'reference_id'   => $store->id,
                'key'            => Str::slug($store->name),
                'prefix'         => SlugHelper::getPrefix(Store::class),
            ]);
        }

        foreach (Product::where('is_variation', 0)->get() as $product) {
            $product->store_id = Store::inRandomOrder()->value('id');
            $product->save();
        }
    }
}
