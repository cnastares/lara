{{--
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
--}}
@extends('front.layouts.master')

@section('wizard')
    @include('front.post.createOrEdit.multiSteps.inc.wizard')
@endsection

@php
	$packages ??= collect();
	$paymentMethods ??= collect();
	
	$selectedPackage ??= null;
	$currentPackagePrice = $selectedPackage->price ?? 0;
	
	// Get steps URLs & labels
	$previousStepUrl ??= null;
	$previousStepLabel ??= null;
	$formActionUrl ??= request()->fullUrl();
	$nextStepUrl ??= '/';
	$nextStepLabel ??= t('submit');
@endphp
@section('content')
	@include('front.common.spacer')
    <div class="main-container">
        <div class="container">
            <div class="row">
    
                @include('front.post.inc.notification')
                
                <div class="col-md-12 page-content">
                    <div class="inner-box">
						
                        <h2 class="title-2">
							<strong>
								@if (!empty($selectedPackage))
									<i class="fa-solid fa-wallet"></i> {{ t('Payment') }}
								@else
									<i class="fa-solid fa-tags"></i> {{ t('Pricing') }}
								@endif
							</strong>
						</h2>
						
                        <div class="row">
                            <div class="col-12">
                                <form id="payableForm" action="{{ $formActionUrl }}" method="POST" class="form">
	                                @csrf
                                    <fieldset>
										
										@if (!empty($selectedPackage))
											@include('front.payment.packages.selected')
										@else
											@include('front.payment.packages')
                                        @endif
										
                                        <div class="row mt-5">
                                            <div class="col-md-6 text-start">
												<a href="{{ $previousStepUrl }}" class="btn btn-default btn-lg btn-block">
													{!! $previousStepLabel !!}
												</a>
                                            </div>
	                                        <div class="col-md-6 text-end">
                                                <button id="payableFormSubmitButton" class="btn btn-primary btn-lg btn-block payableFormSubmitButton">
	                                                {!! $nextStepLabel !!}
                                                </button>
                                            </div>
                                        </div>
                                    
                                    </fieldset>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
	
	@includeWhen(!auth()->check(), 'auth.login.inc.modal')
@endsection

@section('after_scripts')
	<script>
		const packageType = 'promotion';
		const formType = 'multiStep';
		const isCreationFormPage = true;
	</script>
	@include('front.common.js.payment-scripts')
	@include('front.common.js.payment-js')
@endsection
