<?php

namespace Botble\Marketplace\Http\Controllers\Fronts;

use Assets;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Marketplace\Http\Requests\SettingRequest;
use Botble\Marketplace\Repositories\Interfaces\StoreInterface;
use Illuminate\Contracts\Config\Repository;
use RvMedia;

class SettingController
{
    /**
     * DashboardController constructor.
     * @param Repository $config
     */
    public function __construct(Repository $config)
    {
        Assets::setConfig($config->get('plugins.marketplace.assets', []));
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        page_title()->setTitle(__('Settings'));

        $store = auth('customer')->user()->store;

        return view('plugins/marketplace::themes.dashboard.settings', compact('store'));
    }

    /**
     * @param SettingRequest $request
     * @param StoreInterface $storeRepository
     * @param BaseHttpResponse $response
     */
    public function saveSettings(SettingRequest $request, StoreInterface $storeRepository, BaseHttpResponse $response)
    {
        $store = auth('customer')->user()->store;

        if ($request->hasFile('logo_input')) {
            $result = RvMedia::handleUpload($request->file('logo_input'), 0, 'stores');
            if ($result['error'] == false) {
                $file = $result['data'];
                $request->merge(['logo' => $file->url]);
            }
        }

        $store->fill($request->input());

        $storeRepository->createOrUpdate($store);

        event(new UpdatedContentEvent(STORE_MODULE_SCREEN_NAME, $request, $store));

        return $response
            ->setNextUrl(route('marketplace.vendor.settings'))
            ->setMessage(__('Update successfully!'));
    }
}
