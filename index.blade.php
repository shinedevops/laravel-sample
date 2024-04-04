@extends('layouts.app')

@section('title', 'All Subscriptions')

@section('content')
<div class="box-content super-plan-manage-page">
    <div class="tab-content server-detail-form plan-manage-data">
        <div id="officer" class="tab_panel">
            <div class="dashboard-content-outer">
                <x-alert />
                @if ($authUser->isSubscribed())
                    @php
                        $subscriptionDetails = $authUser->userSubscriptionPlan();
                    @endphp
                    <div class="box-content create-subscription-plan">
                        <div class="form-heading">
                            <h5>Active Plan</h5>
                        </div>
                        <div class="super-enter-company-detail">
                            <div class="paymnet-info-sec active-plan-sec">
                                <div class="login-page-section">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="active-plan-left">
                                                <div class="plan-icon">
                                                    <img src="{{ asset('images/plan-icon.svg') }}" alt="img">
                                                </div>
                                                <div class="unlimited-text">
                                                    <h6>{{ $subscriptionDetails->name }}</h6>
                                                    <h4>${{ $subscriptionDetails->price }}/{{ $subscriptionDetails->billing_cycle }}</h4>
                                                </div>
                                                <div class="active-left-dec">
                                                    <p class="desc-text">
                                                        {{ $subscriptionDetails->description }}
                                                    </p>
                                                </div>
                                                @if ($authUser->subscription($subscriptionDetails->name)->onTrial())
                                                    <div class="active-btn">
                                                        <p  class="button secondary-btn">Trialing</p>
                                                    </div>
                                                @else
                                                    <div class="active-btn">
                                                        <p  class="button secondary-btn">Active</p>
                                                    </div>
                                                @endif
                                                <div class="change-plan">
                                                    <p class="button secondary-btn" id="change_plan_button">Change plan</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-7">
                                            <div class="right-active-plan">
                                                <div class="border-active-plan">
                                                    <div class="plan-status">
                                                        <span class="secondary-bg bg-text">Purchased On</span>
                                                    </div>
                                                    <p class="text">{{ date('F d, Y', strtotime($subscriptionDetails->created_at)) }}</p>
                                                </div>
                                                <div class="border-active-plan">
                                                    <div class="plan-status">
                                                        <span class="primary-bg bg-text">Expiry Date</span>
                                                    </div>
                                                    <p class="text">{{ date('F d, Y', strtotime($subscriptionDetails->expiry_date)) }}</p>
                                                </div>
                                                @if ($authUser->subscribed($subscriptionDetails->name))
                                                    @if (!$authUser->subscription($subscriptionDetails->name)->onGracePeriod())
                                                        <div class="border-active-plan">
                                                            <p class="text">Don’t want to continue with this plan?</p>
                                                            <div class="cancle-plan">
                                                                <a href="{{ route('admin.subscriptions.cancel', ['subscription' => $subscriptionDetails->id]) }}" class="button primary-btn">Cancel</a>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    @if ($authUser->subscription($subscriptionDetails->name)->onGracePeriod())
                                                        <div class="border-active-plan">
                                                                <p class="text">Do want to continue with this plan?</p>
                                                                <div class="cancle-plan">
                                                                    <a href="{{ route('admin.subscriptions.resume', ['subscription' => $subscriptionDetails->id]) }}" class="button primary-btn">Resume</a>
                                                                </div>
                                                        </div>
                                                        <li>
                                                            <p>Plan expired On: {{ $authUser->subscription($subscriptionDetails->name)->ends_at }}</p>
                                                        </li>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="plan-management @if(isset($subscriptionDetails)) d-none @endif">
                    @if ($authUser->subscriptions()->active()->count())
                    <div class=""><p class="btn btn-primary active_plan_button secondary-btn">Back</p></div>
                @endif
                    <div class="header-content d-flex">
                        <div class="header-title">
                            <h6>All Subscription Plans</h6>
                        </div>
                    </div>

                    <div class="row">
                        @forelse ($subscriptions as $subscription)
                            <div class="col-md-4">
                                <div class="plan-outer">
                                    <div class="plan-inner">
                                        <div class="plan-edit d-flex">
                                            <p class="large-text">{{ $subscription->name }}</p>
                                        </div>
                                        <div class="plan-price d-flex">
                                            <h4>${{ $subscription->price }}</h4>
                                            <span>/{{ strtolower($subscription->billing_cycle) }}</span>
                                        </div>
                                        <div class="plan-description">
                                            <p>{{ $subscription->description }}</p>
                                        </div>
                                    </div>

                                    @if (isset($subscriptionDetails) && ($subscriptionDetails->price < $subscription->price))
                                        <div class="border-active-plan">
                                            <p class="text">Do you want to upgrade your plan?</p>
                                            <div class="cancle-plan">
                                                <a href="{{ route('admin.subscriptions.upgrade', ['subscription' => $subscription->id]) }}" class="button primary-btn">Upgrade</a>
                                            </div>
                                        </div>
                                    @elseif(isset($subscriptionDetails) && ($subscriptionDetails->price > $subscription->price))
                                        <div class="details-buttons">
                                            <ul class="d-flex">
                                                <p>You can't buy this plan.</p>
                                            </ul>
                                        </div>
                                    @elseif(isset($subscriptionDetails) && $subscription->id == $subscriptionDetails->id)
                                        <div class="details-buttons">
                                            <ul class="d-flex">
                                                <li>
                                                    <a href="javascript:void(0);" class="button primary-btn">Active</a>
                                                </li>
                                            </ul>
                                        </div>
                                    @else
                                        <div class="details-btn">
                                            <ul class="d-flex">
                                                <li>
                                                    <a href="{{ route('admin.subscriptions.buy', ['subscription' => $subscription->id]) }}" class="button primary-btn">Buy</a>
                                                </li>
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="col-md-12">
                                <div class="plan-outer">
                                    <p class="text-center m-0">No Subscriptions available.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    jQuery(document).ready(function() {
        jQuery('#change_plan_button').click(function() {
            jQuery('.plan-management').removeClass('d-none');
            jQuery('.create-subscription-plan').addClass('d-none');
        });

        jQuery('.active_plan_button').click( function() {
            jQuery('.plan-management').addClass('d-none');
            jQuery('.create-subscription-plan').removeClass('d-none');
        });
    });
</script>
@endpush