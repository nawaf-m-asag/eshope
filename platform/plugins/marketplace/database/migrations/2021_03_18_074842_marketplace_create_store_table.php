<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class MarketplaceCreateStoreTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mp_stores', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('email', 60)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('country', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->integer('customer_id')->unsigned()->nullable();
            $table->string('logo', 255)->nullable();
            $table->string('description', 400)->nullable();
            $table->longText('content')->nullable();
            $table->string('status', 60)->default('published');
            $table->timestamps();
        });

        Schema::table('ec_products', function (Blueprint $table) {
            $table->integer('store_id')->unsigned()->nullable();
        });

        Schema::table('ec_customers', function (Blueprint $table) {
            $table->boolean('is_vendor')->default(false);
        });

        Schema::table('ec_orders', function (Blueprint $table) {
            $table->integer('store_id')->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ec_orders', function (Blueprint $table) {
            $table->dropColumn('store_id');
        });

        Schema::table('ec_products', function (Blueprint $table) {
            $table->dropColumn('store_id');
        });

        Schema::table('ec_customers', function (Blueprint $table) {
            $table->dropColumn('is_vendor');
        });

        Schema::dropIfExists('mp_stores');
    }
}
