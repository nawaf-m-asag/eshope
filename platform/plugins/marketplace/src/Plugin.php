<?php

namespace Botble\Marketplace;

use Botble\PluginManagement\Abstracts\PluginOperationAbstract;
use Schema;

class Plugin extends PluginOperationAbstract
{
    public static function remove()
    {
        Schema::table('ec_products', function (Blueprint $table) {
            $table->dropColumn('store_id');
        });

        Schema::table('ec_customers', function (Blueprint $table) {
            $table->dropColumn('is_vendor');
        });

        Schema::dropIfExists('mp_stores');
    }
}
