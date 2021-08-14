<?php

namespace Botble\Marketplace\Tables;

use Auth;
use BaseHelper;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Marketplace\Models\Store;
use Botble\Marketplace\Repositories\Interfaces\StoreInterface;
use Botble\Table\Abstracts\TableAbstract;
use Html;
use Illuminate\Contracts\Routing\UrlGenerator;
use RvMedia;
use Yajra\DataTables\DataTables;

class StoreTable extends TableAbstract
{

    /**
     * @var bool
     */
    protected $hasActions = true;

    /**
     * @var bool
     */
    protected $hasFilter = true;

    /**
     * StoreTable constructor.
     * @param DataTables $table
     * @param UrlGenerator $urlGenerator
     * @param StoreInterface $storeRepository
     */
    public function __construct(DataTables $table, UrlGenerator $urlGenerator, StoreInterface $storeRepository)
    {
        $this->repository = $storeRepository;
        $this->setOption('id', 'plugins-store-table');
        parent::__construct($table, $urlGenerator);

        if (!Auth::user()->hasAnyPermission(['marketplace.store.edit', 'marketplace.store.destroy'])) {
            $this->hasOperations = false;
            $this->hasActions = false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function ajax()
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('name', function ($item) {
                if (!Auth::user()->hasPermission('marketplace.store.edit')) {
                    return $item->name;
                }
                return Html::link(route('marketplace.store.edit', $item->id), $item->name);
            })
            ->editColumn('logo', function ($item) {
                return Html::image(RvMedia::getImageUrl($item->logo, 'thumb', false, RvMedia::getDefaultImage()),
                    $item->name, ['width' => 50]);
            })
            ->editColumn('checkbox', function ($item) {
                return $this->getCheckbox($item->id);
            })
            ->editColumn('created_at', function ($item) {
                return BaseHelper::formatDate($item->created_at);
            })
            ->editColumn('status', function ($item) {
                return $item->status->toHtml();
            });

        return apply_filters(BASE_FILTER_GET_LIST_DATA, $data, $this->repository->getModel())
            ->addColumn('operations', function ($item) {
                return $this->getOperations('marketplace.store.edit', 'marketplace.store.destroy', $item);
            })
            ->escapeColumns([])
            ->make(true);
    }

    /**
     * {@inheritDoc}
     */
    public function query()
    {
        $model = $this->repository->getModel();
        $select = [
            'mp_stores.id',
            'mp_stores.logo',
            'mp_stores.name',
            'mp_stores.created_at',
            'mp_stores.status',
        ];

        $query = $model->select($select);

        return $this->applyScopes(apply_filters(BASE_FILTER_TABLE_QUERY, $query, $model, $select));
    }

    /**
     * {@inheritDoc}
     */
    public function columns()
    {
        return [
            'id'         => [
                'name'  => 'mp_stores.id',
                'title' => trans('core/base::tables.id'),
                'width' => '20px',
            ],
            'logo'       => [
                'name'  => 'mp_stores.logo',
                'title' => trans('plugins/marketplace::store.forms.logo'),
                'width' => '70px',
            ],
            'name'       => [
                'name'  => 'mp_stores.name',
                'title' => trans('core/base::tables.name'),
                'class' => 'text-left',
            ],
            'created_at' => [
                'name'  => 'mp_stores.created_at',
                'title' => trans('core/base::tables.created_at'),
                'width' => '100px',
            ],
            'status'     => [
                'name'  => 'mp_stores.status',
                'title' => trans('core/base::tables.status'),
                'width' => '100px',
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function buttons()
    {
        $buttons = $this->addCreateButton(route('marketplace.store.create'), 'marketplace.store.create');

        return apply_filters(BASE_FILTER_TABLE_BUTTONS, $buttons, Store::class);
    }

    /**
     * {@inheritDoc}
     */
    public function bulkActions(): array
    {
        return $this->addDeleteAction(route('marketplace.store.deletes'), 'marketplace.store.destroy',
            parent::bulkActions());
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->getBulkChanges();
    }

    /**
     * {@inheritDoc}
     */
    public function getBulkChanges(): array
    {
        return [
            'mp_stores.name'       => [
                'title'    => trans('core/base::tables.name'),
                'type'     => 'text',
                'validate' => 'required|max:120',
            ],
            'mp_stores.status'     => [
                'title'    => trans('core/base::tables.status'),
                'type'     => 'select',
                'choices'  => BaseStatusEnum::labels(),
                'validate' => 'required|in:' . implode(',', BaseStatusEnum::values()),
            ],
            'mp_stores.created_at' => [
                'title' => trans('core/base::tables.created_at'),
                'type'  => 'date',
            ],
        ];
    }
}
