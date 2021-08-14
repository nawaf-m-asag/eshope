@extends('plugins/marketplace::themes.dashboard.master')

@section('content')
    <section class="ps-dashboard">
        <div class="ps-section__left">
            <div class="row">
                <div class="col-md-8">
                    <div class="ps-card ps-card--sale-report">
                        <div class="ps-card__header">
                            <h4>{{ __('Sales Reports') }}</h4>
                        </div>
                        <div class="ps-card__content">
                            <div id="orders-in-month-chart"></div>
                        </div>
                        <div class="ps-card__footer">
                            <div class="row">
                                <div class="col-md-8">
                                    <p>{{ __('Items Earning Sales') }} ({{ get_application_currency()->symbol }})</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="ps-card ps-card--earning">
                        <div class="ps-card__header">
                            <h4>{{ __('Earnings') }}</h4>
                        </div>
                        <div class="ps-card__content">
                            <div class="ps-card__chart">
                                <div id="revenue-chart"></div>
                                <div class="ps-card__information"><i class="icon icon-wallet"></i><strong>{{ format_price($user->balance) }}</strong><small>{{ __('Balance') }}</small></div>
                            </div>
                            <div class="ps-card__status">
                                <p class="yellow"><strong> {{ format_price($user->balance) }}</strong><span>{{ __('Income') }}</span></p>
                                <p class="green"><strong> {{ format_price($user->balance / 10) }}</strong><span>{{ __('Fees') }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="ps-card">
                <div class="ps-card__header">
                    <h4>{{ __('Recent Orders') }}</h4>
                </div>
                <div class="ps-card__content">
                    <div class="table-responsive">
                        <table class="table ps-table">
                            <thead>
                            <tr>
                                <th>{{ __('ID') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Customer') }}</th>
                                <th>{{ __('Payment') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Total') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                                @if (count($orders) > 0)
                                    @foreach($orders as $order)
                                        <tr>
                                            <td>{{ get_order_code($order->id) }}</td>
                                            <td><strong>{{ $order->created_at->format('M d, Y') }}</strong></td>
                                            <td><a href="{{ route('marketplace.vendor.orders.edit', $order->id) }}"><strong>{{ $order->user->name ?: $order->address->name }}</strong></a></td>
                                            <td>{!! $order->payment->status->toHtml() !!}</td>
                                            <td>{!! $order->status->toHtml() !!}</td>
                                            <td><strong>{{ format_price($order->amount) }}</strong></td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="6" class="text-center">{{ __('No orders!') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="ps-card__footer"><a class="ps-card__morelink" href="{{ route('marketplace.vendor.orders.index') }}">{{ __('View Full Orders') }}<i class="icon icon-chevron-right"></i></a></div>
            </div>
        </div>
        <div class="ps-section__right">
            <section class="ps-card ps-card--statics">
                <div class="ps-card__header">
                    <h4>{{ __('Statistics') }}</h4>
<!--                    <div class="ps-card__sortby"><i class="icon-calendar-empty"></i>
                        <div class="form-group&#45;&#45;select">
                            <select class="form-control">
                                <option value="1">Last 30 days</option>
                                <option value="2">Last 90 days</option>
                                <option value="3">Last 180 days</option>
                            </select><i class="icon-chevron-down"></i>
                        </div>
                    </div>-->
                </div>
                <div class="ps-card__content">
                    <div class="ps-block--stat yellow">
                        <div class="ps-block__left"><span><i class="icon-cart"></i></span></div>
                        <div class="ps-block__content">
                            <p>{{ __('Orders') }}</p>
                            <h4>{{ $store->orders()->count() }}<small class="asc"><i class="icon-arrow-up"></i></small></h4>
                        </div>
                    </div>
                    <div class="ps-block--stat pink">
                        <div class="ps-block__left"><span><i class="icon-cart"></i></span></div>
                        <div class="ps-block__content">
                            <p>{{ __('Revenue') }}</p>
                            <h4>{{ format_price($user->balance) }}<small class="asc"><i class="icon-arrow-up"></i></small></h4>
                        </div>
                    </div>
                    <div class="ps-block--stat green">
                        <div class="ps-block__left"><span><i class="icon-cart"></i></span></div>
                        <div class="ps-block__content">
                            <p>{{ __('Products') }}</p>
                            <h4>{{ $store->products()->count() }}<small class="desc"><i class="icon-arrow-down"></i></small></h4>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </section>
@stop

@push('footer')
    <script>
        $(document).ready(function () {
            new ApexCharts(document.querySelector('#revenue-chart'), {
                series: [50, 50],
                chart: {height: '250', type: 'donut'},
                chartOptions: {labels: ["{{ __('Income') }}", "{{ __('Fees') }}"]},
                plotOptions: {pie: {donut: {size: '71%', polygons: {strokeWidth: 0}}, expandOnClick: !1}},
                states: {hover: {filter: {type: 'darken', value: .9}}},
                dataLabels: {enabled: !1},
                legend: {show: !1},
                tooltip: {enabled: !1}
            }).render();

            @php
                $start = now()->startOfMonth();
                $end = now()->endOfMonth();

                $dates = [];
                while ($start->lte($end)) {
                     $dates[] = $start->toDateTimeString();
                     $start->addDay();
                }
            @endphp
            new ApexCharts(document.querySelector('#orders-in-month-chart'), {
                series: [{name: 'series1', data: [100, 120, 0, 0, 0, 0,  0,  0, 0, 99, 125, 127, 130, 148, 0, 0, 0, 0, 0, 0,  0,  0, 0, 0, 0, 0, 0,  0,  0, 0]}],
                chart: {height: 350, type: 'area', toolbar: {show: !1}},
                dataLabels: {enabled: !1},
                stroke: {curve: 'smooth'},
                colors: ['#fcb800', '#80bc00'],
                xaxis: {
                    type: 'datetime',
                    categories: {!! json_encode($dates) !!}
                },
                tooltip: {x: {format: 'dd/MM/yy HH:mm'}}
            }).render();
        });
    </script>
@endpush
