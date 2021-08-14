@php
    $brands = get_all_brands(['status' => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED], ['slugable'], ['products']);
    $categories = get_product_categories(['status' => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED], ['slugable', 'children:id,name,parent_id', 'children.slugable'], ['products'], true);
    $tags = app(\Botble\Ecommerce\Repositories\Interfaces\ProductTagInterface::class)->advancedGet([
        'condition' => ['status' => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED],
        'with'      => ['slugable'],
        'withCount' => ['products'],
        'order_by'  => ['products_count' => 'desc'],
        'take'      => 10,
    ]);
    $rand = mt_rand();
    $categoriesRequest = request()->input('categories', []);
    $urlCurrent = URL::current();
@endphp

<aside class="widget widget_shop">
    <h4 class="widget-title">{{ __('Product Categories') }}</h4>
    <ul class="ps-list--categories">
        @foreach($categories as $category)
            <li class="@if ($urlCurrent == $category->url || (!empty($categoriesRequest && in_array($category->id, $categoriesRequest)))) current-menu-item @endif @if ($category->children->count()) menu-item-has-children @endif">
                <a href="{{ $category->url }}">{{ $category->name }}</a>
                @if ($category->children->count())
                    @php
                        $category->children->append(['url']);
                    @endphp
                    <span class="sub-toggle"><i class="icon-angle"></i></span>
                    <ul class="sub-menu" @if (in_array($urlCurrent, collect($category->children->toArray())->pluck('url')->toArray())) style="display:block" @endif>
                        @foreach($category->children as $child)
                            <li @if($urlCurrent == $child->url) class="current-menu-item" @endif><a href="{{ $child->url }}">{{ $child->name }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</aside>

@if (count($brands) > 0)
    <aside class="widget widget_shop">
        <h4 class="widget-title">{{ __('By Brands') }}</h4>
        <figure class="ps-custom-scrollbar" data-height="250">
            @foreach($brands as $brand)
                <div class="ps-checkbox">
                    <input class="form-control product-filter-item" type="checkbox" name="brands[]" id="brand-{{ $rand }}-{{ $brand->id }}" value="{{ $brand->id }}" @if (in_array($brand->id, request()->input('brands', []))) checked @endif>
                    <label for="brand-{{ $rand }}-{{ $brand->id }}"><span>{{ $brand->name }} <span class="d-inline-block">({{ $brand->products_count }})</span> </span></label>
                </div>
            @endforeach
        </figure>
    </aside>
@endif

@if (count($tags) > 0)
    <aside class="widget widget_shop">
        <h4 class="widget-title">{{ __('By Tags') }}</h4>
        <figure class="ps-custom-scrollbar" data-height="250">
            @foreach($tags as $tag)
                <div class="ps-checkbox">
                    <input class="form-control product-filter-item" type="checkbox" name="tags[]" id="tag-{{ $rand }}-{{ $tag->id }}" value="{{ $tag->id }}" @if (in_array($tag->id, request()->input('tags', []))) checked @endif>
                    <label for="tag-{{ $rand }}-{{ $tag->id }}"><span>{{ $tag->name }} <span class="d-inline-block">({{ $tag->products_count }})</span></span></label>
                </div>
            @endforeach
        </figure>
    </aside>
@endif

<aside class="widget widget_shop">
    <h4 class="widget-title">{{ __('By Price') }}</h4>
    <div class="widget__content nonlinear-wrapper">
        <div class="nonlinear" data-min="0" data-max="{{ theme_option('max_filter_price', 100000) }}"></div>
        <div class="ps-slider__meta">
            <div data-current-exchange-rate="{{ get_current_exchange_rate() }}"></div>
            <input class="product-filter-item product-filter-item-price-0" name="min_price" data-min="0" value="{{ request()->input('min_price', 0) }}" type="hidden">
            <input class="product-filter-item product-filter-item-price-1" name="max_price" data-max="{{ theme_option('max_filter_price', 100000) }}" value="{{ request()->input('max_price', theme_option('max_filter_price', 100000)) }}" type="hidden">
            <span class="ps-slider__value">
                <span class="ps-slider__min"></span> {{ get_application_currency()->title }}</span> - 
                <span class="ps-slider__value"><span class="ps-slider__max"></span> {{ get_application_currency()->title }}
            </span>
        </div>
    </div>

    {!! render_product_swatches_filter([
        'view' => Theme::getThemeNamespace() . '::views.ecommerce.attributes.attributes-filter-renderer'
    ]) !!}
</aside>

<input type="hidden" name="sort-by" class="product-filter-item" value="{{ request()->input('sort-by') }}">
<input type="hidden" name="layout" class="product-filter-item" value="{{ request()->input('layout') }}">
