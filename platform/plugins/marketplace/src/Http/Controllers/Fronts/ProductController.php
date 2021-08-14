<?php

namespace Botble\Marketplace\Http\Controllers\Fronts;

use Assets;
use Botble\Base\Forms\FormBuilder;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Http\Requests\ProductRequest;
use Botble\Ecommerce\Http\Requests\ProductVersionRequest;
use Botble\Ecommerce\Repositories\Eloquent\ProductVariationRepository;
use Botble\Ecommerce\Repositories\Interfaces\GroupedProductInterface;
use Botble\Ecommerce\Repositories\Interfaces\ProductAttributeInterface;
use Botble\Ecommerce\Repositories\Interfaces\ProductAttributeSetInterface;
use Botble\Ecommerce\Repositories\Interfaces\ProductVariationInterface;
use Botble\Ecommerce\Repositories\Interfaces\ProductVariationItemInterface;
use Botble\Ecommerce\Services\Products\StoreAttributesOfProductService;
use Botble\Ecommerce\Services\Products\StoreProductService;
use Botble\Ecommerce\Services\StoreProductTagService;
use Botble\Ecommerce\Traits\ProductActionsTrait;
use Botble\Marketplace\Forms\ProductForm;
use Botble\Marketplace\Tables\ProductTable;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class ProductController extends BaseController
{
    use ProductActionsTrait {
        ProductActionsTrait::postAddVersion as basePostAddVersion;
        ProductActionsTrait::postUpdateVersion as basePostUpdateVersion;
    }

    /**
     * @param ProductTable $dataTable
     * @return Factory|View
     * @throws Throwable
     */
    public function index(ProductTable $table)
    {
        page_title()->setTitle(__('Products'));

        return $table->render('plugins/marketplace::themes.dashboard.table.base');
    }

    /**
     * @param FormBuilder $formBuilder
     * @return string
     */
    public function create(FormBuilder $formBuilder)
    {
        page_title()->setTitle(trans('plugins/ecommerce::products.create'));

        Assets::addStyles(['datetimepicker'])
            ->addScripts([
                'moment',
                'datetimepicker',
                'jquery-ui',
                'input-mask',
                'blockui',
            ])
            ->addStylesDirectly(['vendor/core/plugins/ecommerce/css/ecommerce.css'])
            ->addScriptsDirectly([
                'vendor/core/plugins/ecommerce/libraries/bootstrap-confirmation/bootstrap-confirmation.min.js',
                'vendor/core/plugins/ecommerce/js/edit-product.js',
            ]);

        return $formBuilder->create(ProductForm::class)->renderForm();
    }

    /**
     * @param ProductRequest $request
     * @param StoreProductService $service
     * @param BaseHttpResponse $response
     * @param ProductVariationInterface $variationRepository
     * @param ProductVariationItemInterface $productVariationItemRepository
     * @param GroupedProductInterface $groupedProductRepository
     * @param StoreAttributesOfProductService $storeAttributesOfProductService
     * @param StoreProductTagService $storeProductTagService
     * @return BaseHttpResponse
     * @throws Exception
     */
    public function store(
        ProductRequest $request,
        StoreProductService $service,
        BaseHttpResponse $response,
        ProductVariationInterface $variationRepository,
        ProductVariationItemInterface $productVariationItemRepository,
        GroupedProductInterface $groupedProductRepository,
        StoreAttributesOfProductService $storeAttributesOfProductService,
        StoreProductTagService $storeProductTagService
    ) {
        $product = $this->productRepository->getModel();

        $request->merge([
            'store_id' => auth('customer')->user()->store->id,
            'images'   => json_decode($request->input('images')),
        ]);

        $product = $service->execute($request, $product);
        $storeProductTagService->execute($request, $product);

        $addedAttributes = $request->input('added_attributes', []);

        if ($request->input('is_added_attributes') == 1 && $addedAttributes) {
            $storeAttributesOfProductService->execute($product, array_keys($addedAttributes));

            $variation = $variationRepository->create([
                'configurable_product_id' => $product->id,
            ]);

            foreach ($addedAttributes as $attribute) {
                $productVariationItemRepository->createOrUpdate([
                    'attribute_id' => $attribute,
                    'variation_id' => $variation->id,
                ]);
            }

            $variation = $variation->toArray();

            $variation['variation_default_id'] = $variation['id'];

            $variation['sku'] = $product->sku ?? time();
            foreach ($addedAttributes as $attributeId) {
                $attribute = $this->productAttributeRepository->findById($attributeId);
                if ($attribute) {
                    $variation['sku'] .= '-' . $attribute->slug;
                }
            }

            $this->postSaveAllVersions([$variation['id'] => $variation], $variationRepository, $product->id, $response);
        }

        if ($request->has('grouped_products')) {
            $groupedProductRepository->createGroupedProducts($product->id, array_map(function ($item) {
                return [
                    'id'  => $item,
                    'qty' => 1,
                ];
            }, array_filter(explode(',', $request->input('grouped_products', '')))));
        }

        return $response
            ->setPreviousUrl(route('marketplace.vendor.products.index'))
            ->setNextUrl(route('marketplace.vendor.products.edit', $product->id))
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    /**
     * @param int $id
     * @param FormBuilder $formBuilder
     * @return string
     */
    public function edit($id, FormBuilder $formBuilder)
    {
        $product = $this->productRepository->findOrFail($id);

        if ($product->is_variation || $product->store_id != auth('customer')->user()->store->id) {
            abort(404);
        }

        page_title()->setTitle(trans('plugins/ecommerce::products.edit', ['name' => $product->name]));

        Assets::addStyles(['datetimepicker'])
            ->addScripts([
                'moment',
                'datetimepicker',
                'jquery-ui',
                'input-mask',
                'blockui',
            ])
            ->addStylesDirectly(['vendor/core/plugins/ecommerce/css/ecommerce.css'])
            ->addScriptsDirectly([
                'vendor/core/plugins/ecommerce/libraries/bootstrap-confirmation/bootstrap-confirmation.min.js',
                'vendor/core/plugins/ecommerce/js/edit-product.js',
            ]);

        return $formBuilder
            ->create(ProductForm::class, ['model' => $product])
            ->renderForm();
    }

    /**
     * @param int $id
     * @param ProductRequest $request
     * @param StoreProductService $service
     * @param GroupedProductInterface $groupedProductRepository
     * @param BaseHttpResponse $response
     * @param ProductVariationInterface $variationRepository
     * @param ProductVariationItemInterface $productVariationItemRepository
     * @param StoreProductTagService $storeProductTagService
     * @return BaseHttpResponse|JsonResponse|RedirectResponse
     */
    public function update(
        $id,
        ProductRequest $request,
        StoreProductService $service,
        GroupedProductInterface $groupedProductRepository,
        BaseHttpResponse $response,
        ProductVariationInterface $variationRepository,
        ProductVariationItemInterface $productVariationItemRepository,
        StoreProductTagService $storeProductTagService
    ) {
        $product = $this->productRepository->findOrFail($id);

        if ($product->is_variation || $product->store_id != auth('customer')->user()->store->id) {
            abort(404);
        }

        $request->merge([
            'store_id' => auth('customer')->user()->store->id,
            'images'   => json_decode($request->input('images')),
        ]);

        $product = $service->execute($request, $product);
        $storeProductTagService->execute($request, $product);

        $variationRepository
            ->getModel()
            ->where('configurable_product_id', $product->id)
            ->update(['is_default' => 0]);

        $defaultVariation = $variationRepository->findById($request->input('variation_default_id'));
        if ($defaultVariation) {
            $defaultVariation->is_default = true;
            $defaultVariation->save();
        }

        $addedAttributes = $request->input('added_attributes', []);

        if ($request->input('is_added_attributes') == 1 && $addedAttributes) {
            $result = $variationRepository->getVariationByAttributesOrCreate($id, $addedAttributes);

            /**
             * @var Collection $variation
             */
            $variation = $result['variation'];

            foreach ($addedAttributes as $attribute) {
                $productVariationItemRepository->createOrUpdate([
                    'attribute_id' => $attribute,
                    'variation_id' => $variation->id,
                ]);
            }

            $variation = $variation->toArray();
            $variation['variation_default_id'] = $variation['id'];

            $product->productAttributeSets()->sync(array_keys($addedAttributes));

            $variation['sku'] = $product->sku ?? time();
            foreach (array_keys($addedAttributes) as $attributeId) {
                $attribute = $this->productAttributeRepository->findById($attributeId);
                if ($attribute) {
                    $variation['sku'] .= '-' . $attribute->slug;
                }
            }

            $this->postSaveAllVersions([$variation['id'] => $variation], $variationRepository, $product->id, $response);
        } elseif ($product->variations()->count() === 0) {
            $product->productAttributeSets()->detach();
            $product->productAttributes()->detach();
        }

        if ($request->has('grouped_products')) {
            $groupedProductRepository->createGroupedProducts($product->id, array_map(function ($item) {
                return [
                    'id'  => $item,
                    'qty' => 1,
                ];
            }, array_filter(explode(',', $request->input('grouped_products', '')))));
        }

        return $response
            ->setPreviousUrl(route('marketplace.vendor.products.index'))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    /**
     * @param int $id
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     * @return BaseHttpResponse
     * @throws Throwable
     */
    public function getRelationBoxes($id, BaseHttpResponse $response)
    {
        $product = null;
        if ($id) {
            $product = $this->productRepository->findById($id);
        }

        $dataUrl = route('marketplace.vendor.products.get-list-product-for-search', $product ? $product->id : 0);

        return $response->setData(view('plugins/ecommerce::products.partials.extras',
            compact('product', 'dataUrl'))->render());
    }

    /**
     * {@inheritDoc}
     */
    public function postAddVersion(
        ProductVersionRequest $request,
        ProductVariationInterface $productVariation,
        $id,
        BaseHttpResponse $response
    ) {
        $request->merge([
            'images' => json_decode($request->input('images', '[]')),
        ]);

        return $this->basePostAddVersion($request, $productVariation, $id, $response);
    }

    /**
     * @param Request $request
     * @param ProductVariationRepository|ProductVariationInterface $productVariation
     * @param int $id
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     * @throws Exception
     */
    public function postUpdateVersion(
        ProductVersionRequest $request,
        ProductVariationInterface $productVariation,
        $id,
        BaseHttpResponse $response
    ) {
        $request->merge([
            'images' => json_decode($request->input('images', '[]')),
        ]);

        return $this->basePostUpdateVersion($request, $productVariation, $id, $response);
    }

    /**
     * {@inheritDoc}
     */
    public function getVersionForm(
        $id,
        Request $request,
        ProductVariationInterface $productVariation,
        BaseHttpResponse $response,
        ProductAttributeSetInterface $productAttributeSetRepository,
        ProductAttributeInterface $productAttributeRepository,
        ProductVariationItemInterface $productVariationItemRepository
    ) {
        $product = null;
        $variation = null;
        $productVariationsInfo = [];

        if ($id) {
            $variation = $productVariation->findOrFail($id);
            $product = $this->productRepository->findOrFail($variation->product_id);
            $productVariationsInfo = $productVariationItemRepository->getVariationsInfo([$id]);
        }

        $productId = $variation ? $variation->configurable_product_id : $request->input('product_id');

        $productAttributeSets = $productAttributeSetRepository->getAllWithSelected($productId);
        $productAttributes = $productAttributeRepository->getAllWithSelected($productId);

        $originalProduct = $product;

        return $response
            ->setData(
                view('plugins/marketplace::themes.dashboard.products.product-variation-form', compact(
                    'productAttributeSets',
                    'productAttributes',
                    'product',
                    'productVariationsInfo',
                    'originalProduct'
                ))->render()
            );
    }
}
