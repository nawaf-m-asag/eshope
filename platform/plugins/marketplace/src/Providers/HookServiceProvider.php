<?php

namespace Botble\Marketplace\Providers;

use BaseHelper;
use Botble\Base\Forms\FormAbstract;
use Botble\Base\Models\BaseModel;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Product;
use Botble\Marketplace\Repositories\Interfaces\StoreInterface;
use Html;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Yajra\DataTables\EloquentDataTable;

class HookServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->booted(function () {
            add_filter(BASE_FILTER_BEFORE_RENDER_FORM, [$this, 'registerAdditionalData'], 128, 2);

            add_action(BASE_ACTION_AFTER_CREATE_CONTENT, [$this, 'saveAdditionalData'], 128, 3);

            add_action(BASE_ACTION_AFTER_UPDATE_CONTENT, [$this, 'saveAdditionalData'], 128, 3);

            add_filter(IS_IN_ADMIN_FILTER, [$this, 'setInAdmin'], 20, 0);

            add_filter(BASE_FILTER_GET_LIST_DATA, [$this, 'addColumnToCustomerTable'], 153, 2);
            add_filter(BASE_FILTER_TABLE_HEADINGS, [$this, 'addHeadingToCustomerTable'], 153, 2);
            add_filter(BASE_FILTER_TABLE_QUERY, [$this, 'modifyQueryInCustomerTable'], 153, 3);
        });
    }

    /**
     * @param FormAbstract $form
     * @param BaseModel $data
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function registerAdditionalData($form, $data)
    {
        if (get_class($data) == Product::class && request()->segment(1) === BaseHelper::getAdminPrefix()) {
            $stores = $this->app->make(StoreInterface::class)->pluck('name', 'id');

            $form
                ->addAfter('status', 'store_id', 'customSelect', [
                    'label'      => trans('plugins/marketplace::store.forms.store'),
                    'label_attr' => ['class' => 'control-label'],
                    'choices'    => [0 => trans('plugins/marketplace::store.forms.select_store')] + $stores,
                ]);
        } elseif (get_class($data) == Customer::class) {
            $form
                ->addAfter('email', 'is_vendor', 'onOff', [
                    'label'         => trans('plugins/marketplace::store.forms.is_vendor'),
                    'label_attr'    => ['class' => 'control-label'],
                    'default_value' => false,
                ]);
        }
    }

    /**
     * @param string $type
     * @param Request $request
     * @param BaseModel $object
     */
    public function saveAdditionalData($type, $request, $object)
    {
        if (!is_in_admin()) {
            return false;
        }

        if (in_array($type, [PRODUCT_MODULE_SCREEN_NAME])) {
            $object->store_id = $request->input('store_id');
            $object->save();
        } elseif (in_array($type, [CUSTOMER_MODULE_SCREEN_NAME])) {
            $object->is_vendor = $request->input('is_vendor');
            $object->save();
        }
    }

    /**
     * @return bool
     */
    public function setInAdmin(): bool
    {
        return in_array(request()->segment(1), ['vendor', BaseHelper::getAdminPrefix()]);
    }

    /**
     * @param EloquentDataTable $data
     * @param string|Model $model
     * @return EloquentDataTable
     */
    public function addColumnToCustomerTable($data, $model)
    {
        if (!$model || get_class($model) != Customer::class) {
            return $data;
        }

        return $data->addColumn('is_vendor', function ($item) use ($model) {
            return $item->is_vendor ? Html::tag('span', trans('core/base::base.yes'), ['class' => 'text-success']) : trans('core/base::base.no');
        });
    }

    /**
     * @param array $headings
     * @param string|Model $model
     * @return array
     */
    public function addHeadingToCustomerTable($headings, $model)
    {
        if (!$model || get_class($model) != Customer::class) {
            return $headings;
        }

        return array_merge($headings, [
            'is_vendor' => [
                'name'  => 'ec_customers.is_vendor',
                'title' => trans('plugins/marketplace::store.forms.is_vendor'),
                'class' => 'text-center',
                'width' => '100px',
            ],
        ]);
    }

    /**
     * @param Builder $query
     * @param Model $model
     * @param array $selectedColumns
     * @return mixed
     */
    public function modifyQueryInCustomerTable($query, $model, array $selectedColumns = [])
    {
        if (!$model || get_class($model) != Customer::class) {
            return $query;
        }

        return $query->select(array_merge($selectedColumns, [
            'ec_customers.is_vendor',
        ]));
    }
}
