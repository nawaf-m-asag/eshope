<?php

namespace Botble\Marketplace\Http\Controllers\Fronts;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Marketplace\Models\Store;
use Botble\Marketplace\Repositories\Interfaces\StoreInterface;
use Botble\SeoHelper\SeoOpenGraph;
use Botble\Slug\Repositories\Interfaces\SlugInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Response;
use RvMedia;
use SeoHelper;
use SlugHelper;
use Theme;

class PublicStoreController
{
    /**
     * @var StoreInterface
     */
    protected $storeRepository;

    /**
     * @var SlugInterface
     */
    protected $slugRepository;

    /**
     * PublicStoreController constructor.
     * @param StoreInterface $storeRepository
     * @param SlugInterface $slugRepository
     */
    public function __construct(StoreInterface $storeRepository, SlugInterface $slugRepository)
    {
        $this->storeRepository = $storeRepository;
        $this->slugRepository = $slugRepository;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function getStores(Request $request)
    {
        Theme::breadcrumb()->add(__('Home'), url('/'))->add(__('Stores'), route('public.stores'));
        SeoHelper::setTitle(__('Stores'))->setDescription(__('Stores'));

        $stores = $this->storeRepository->advancedGet([
            'condition' => ['status' => BaseStatusEnum::PUBLISHED],
            'order_by'  => ['created_at' => 'desc'],
            'paginate'  => [
                'per_page'      => 12,
                'current_paged' => (int)$request->input('page'),
            ],
            'with' => ['slugable'],
        ]);

        return Theme::scope('marketplace.stores', compact('stores'), 'plugins/marketplace::themes.stores')->render();
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|Response
     */
    public function getStore($slug)
    {
        $slug = $this->slugRepository->getFirstBy([
            'key'            => $slug,
            'reference_type' => Store::class,
            'prefix'         => SlugHelper::getPrefix(Store::class),
        ]);

        if (!$slug) {
            abort(404);
        }

        $condition = [
            'mp_stores.id'     => $slug->reference_id,
            'mp_stores.status' => BaseStatusEnum::PUBLISHED,
        ];

        if (Auth::check() && request()->input('preview')) {
            Arr::forget($condition, 'status');
        }

        $store = $this->storeRepository->getFirstBy($condition, ['*'], ['slugable']);

        if (!$store) {
            abort(404);
        }

        if ($store->slugable->key !== $slug->key) {
            return redirect()->to($store->url);
        }

        SeoHelper::setTitle($store->name)->setDescription($store->description);

        $meta = new SeoOpenGraph;
        if ($store->logo) {
            $meta->setImage(RvMedia::getImageUrl($store->logo));
        }
        $meta->setDescription($store->description);
        $meta->setUrl($store->url);
        $meta->setTitle($store->name);

        SeoHelper::setSeoOpenGraph($meta);

        Theme::breadcrumb()->add(__('Home'), url('/'))
            ->add(__('Stores'), route('public.stores'));

        Theme::breadcrumb()->add($store->name, $store->url);

        $products = $store->products()->where('status', BaseStatusEnum::PUBLISHED)->paginate(12);

        return Theme::scope('marketplace.store', compact('store', 'products'), 'plugins/marketplace::themes.store')->render();
    }

    /**
     * @param Request $request
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     */
    public function checkStoreUrl(Request $request, BaseHttpResponse $response)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $existing = SlugHelper::getSlug($request->input('url'), SlugHelper::getPrefix(Store::class), Store::class);

        if ($existing) {
            return $response
                ->setError()
                ->setMessage(__('Not Available'));
        }

        return $response->setMessage(__('Available'));
    }
}
