<?php

namespace Botble\Ecommerce\Tables;

use BaseHelper;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Ecommerce\Repositories\Interfaces\TaxInterface;
use Botble\Table\Abstracts\TableAbstract;
use Html;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class TaxTable extends TableAbstract
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
     * TaxTable constructor.
     * @param DataTables $table
     * @param UrlGenerator $urlGenerator
     * @param TaxInterface $taxRepository
     */
    public function __construct(DataTables $table, UrlGenerator $urlGenerator, TaxInterface $taxRepository)
    {
        parent::__construct($table, $urlGenerator);

        $this->repository = $taxRepository;

        if (!Auth::user()->hasAnyPermission(['tax.edit', 'tax.destroy'])) {
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
            ->editColumn('title', function ($item) {
                if (!Auth::user()->hasPermission('tax.edit')) {
                    return $item->name;
                }

                return Html::link(route('tax.edit', $item->id), $item->title);
            })
            ->editColumn('percentage', function ($item) {
                return $item->percentage . '%';
            })
            ->editColumn('checkbox', function ($item) {
                return $this->getCheckbox($item->id);
            })
            ->editColumn('status', function ($item) {
                return $item->status->toHtml();
            })
            ->editColumn('created_at', function ($item) {
                return BaseHelper::formatDate($item->created_at);
            })
            ->addColumn('operations', function ($item) {
                return $this->getOperations('tax.edit', 'tax.destroy', $item);
            });

        return $this->toJson($data);
    }

    /**
     * {@inheritDoc}
     */
    public function query()
    {
        $model = $this->repository->getModel();
        $select = [
            'ec_taxes.id',
            'ec_taxes.title',
            'ec_taxes.percentage',
            'ec_taxes.priority',
            'ec_taxes.status',
            'ec_taxes.created_at',
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
                'name'  => 'ec_taxes.id',
                'title' => trans('core/base::tables.id'),
                'width' => '20px',
                'class' => 'text-left',
            ],
            'title'      => [
                'name'  => 'ec_taxes.title',
                'title' => trans('core/base::tables.name'),
                'class' => 'text-left',
            ],
            'percentage' => [
                'name'  => 'ec_taxes.percentage',
                'title' => trans('plugins/ecommerce::tax.percentage'),
                'class' => 'text-center',
            ],
            'priority'   => [
                'name'  => 'ec_taxes.priority',
                'title' => trans('plugins/ecommerce::tax.priority'),
                'class' => 'text-center',
            ],
            'status'     => [
                'name'  => 'ec_taxes.status',
                'title' => trans('core/base::tables.status'),
                'class' => 'text-center',
            ],
            'created_at' => [
                'name'  => 'ec_taxes.created_at',
                'title' => trans('core/base::tables.created_at'),
                'width' => '100px',
                'class' => 'text-left',
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function buttons()
    {
        return $this->addCreateButton(route('tax.create'), 'tax.create');
    }

    /**
     * {@inheritDoc}
     */
    public function bulkActions(): array
    {
        return $this->addDeleteAction(route('tax.deletes'), 'tax.destroy', parent::bulkActions());
    }

    /**
     * {@inheritDoc}
     */
    public function getBulkChanges(): array
    {
        return [
            'ec_taxes.title'      => [
                'title'    => trans('core/base::tables.name'),
                'type'     => 'text',
                'validate' => 'required|max:120',
            ],
            'ec_taxes.status'     => [
                'title'    => trans('core/base::tables.status'),
                'type'     => 'select',
                'choices'  => BaseStatusEnum::labels(),
                'validate' => 'required|in:' . implode(',', BaseStatusEnum::values()),
            ],
            'ec_taxes.created_at' => [
                'title' => trans('core/base::tables.created_at'),
                'type'  => 'date',
            ],
        ];
    }
}
