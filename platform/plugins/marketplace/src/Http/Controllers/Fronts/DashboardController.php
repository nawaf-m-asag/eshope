<?php

namespace Botble\Marketplace\Http\Controllers\Fronts;

use Assets;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Repositories\Interfaces\CustomerInterface;
use Botble\Marketplace\Http\Requests\BecomeVendorRequest;
use Botble\Marketplace\Models\Store;
use Botble\Marketplace\Repositories\Interfaces\StoreInterface;
use Botble\Media\Chunks\Exceptions\UploadMissingFileException;
use Botble\Media\Chunks\Handler\DropZoneUploadHandler;
use Botble\Media\Chunks\Receiver\FileReceiver;
use Botble\Slug\Models\Slug;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RvMedia;
use SeoHelper;
use SlugHelper;
use Theme;

class DashboardController
{
    /**
     * DashboardController constructor.
     * @param Repository $config
     */
    public function __construct(Repository $config)
    {
        Assets::setConfig($config->get('plugins.marketplace.assets', []));

        Theme::asset()
            ->add('customer-style', 'vendor/core/plugins/ecommerce/css/customer.css');
        Theme::asset()
            ->container('footer')
            ->add('ecommerce-utilities-js', 'vendor/core/plugins/ecommerce/js/utilities.js', ['jquery']);

        Theme::asset()
            ->container('footer')
            ->add('avatar-js', 'vendor/core/plugins/ecommerce/js/avatar.js', ['jquery']);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        page_title()->setTitle(__('Dashboard'));

        Assets::addScriptsDirectly([
            'vendor/core/plugins/marketplace/plugins/apexcharts-bundle/dist/apexcharts.min.js',
        ])
            ->addStylesDirectly('vendor/core/plugins/marketplace/plugins/apexcharts-bundle/dist/apexcharts.css');

        $user = auth('customer')->user();
        $store = $user->store;
        $orders = $store->orders()->latest()->limit(10)->get();

        return view('plugins/marketplace::themes.dashboard.index', compact('user', 'store', 'orders'));
    }

    /**
     * @param Request $request
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse|\Illuminate\Http\JsonResponse
     */
    public function postUpload(Request $request, BaseHttpResponse $response)
    {
        if (setting('media_chunk_enabled') != '1') {
            $validator = Validator::make($request->all(), [
                'file.0' => 'required|image|mimes:jpg,jpeg,png',
            ]);

            if ($validator->fails()) {
                return $response->setError()->setMessage($validator->getMessageBag()->first());
            }

            $result = RvMedia::handleUpload(Arr::first($request->file('file')), 0, 'customers');

            if ($result['error']) {
                return $response->setError(true)->setMessage($result['message']);
            }

            return $response->setData($result['data']);
        }

        try {
            // Create the file receiver
            $receiver = new FileReceiver('file', $request, DropZoneUploadHandler::class);
            // Check if the upload is success, throw exception or return response you need
            if ($receiver->isUploaded() === false) {
                throw new UploadMissingFileException;
            }
            // Receive the file
            $save = $receiver->receive();
            // Check if the upload has finished (in chunk mode it will send smaller files)
            if ($save->isFinished()) {
                $result = RvMedia::handleUpload($save->getFile(), 0, 'accounts');

                if ($result['error'] == false) {
                    return $response->setData($result['data']);
                }

                return $response->setError(true)->setMessage($result['message']);
            }
            // We are in chunk mode, lets send the current progress
            $handler = $save->handler();
            return response()->json([
                'done'   => $handler->getPercentageDone(),
                'status' => true,
            ]);
        } catch (Exception $exception) {
            return $response->setError(true)->setMessage($exception->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function postUploadFromEditor(Request $request)
    {
        return RvMedia::uploadFromEditor($request);
    }

    /**
     * @return \Response
     */
    public function getBecomeVendor()
    {
        SeoHelper::setTitle(__('Become Vendor'));

        Theme::breadcrumb()->add(__('Home'), url('/'))
            ->add(__('Become Vendor'), route('marketplace.vendor.become-vendor'));

        return Theme::scope('marketplace.become-vendor', [],
            'plugins/marketplace::themes.become-vendor')->render();
    }

    /**
     * @param BecomeVendorRequest $request
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function postBecomeVendor(BecomeVendorRequest $request, BaseHttpResponse $response)
    {
        $existing = SlugHelper::getSlug($request->input('shop_url'), SlugHelper::getPrefix(Store::class),
            Store::class);

        if ($existing) {
            return $response->setError()->setMessage(__('Shop URL is existing. Please choose another one!'));
        }

        $store = app(StoreInterface::class)->createOrUpdate([
            'name'        => $request->input('shop_name'),
            'phone'       => $request->input('shop_phone'),
            'customer_id' => auth('customer')->id(),
        ]);

        Slug::create([
            'reference_type' => Store::class,
            'reference_id'   => $store->id,
            'key'            => Str::slug($request->input('shop_url')),
            'prefix'         => SlugHelper::getPrefix(Store::class),
        ]);

        $customer = auth('customer')->user();

        $customer->is_vendor = true;
        app(CustomerInterface::class)->createOrUpdate($customer);

        return $response->setNextUrl(route('marketplace.vendor.dashboard'))->setMessage(__('Registered successfully!'));
    }
}
