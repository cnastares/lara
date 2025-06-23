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

@section('content')
	@include('front.common.spacer')
	<div class="main-container">
		<div class="container">
			<div class="row clearfix">
				
				@include('front.post.inc.notification')
				
				<div class="col-12">
					<div class="contact-form">
						
						<h3 class="gray mt-0">
							<strong><a href="{{ urlGen()->post($post) }}">{{ $title }}</a></strong>
						</h3>
						
						<hr class="border-0 bg-secondary mt-1">
						
						<h4>{{ t('There is something wrong with this listing') }}</h4>
						
						<div class="row d-flex justify-content-center">
							<div class="col-10">
								<form role="form" method="POST" action="{{ urlGen()->reportPost($post) }}">
									@csrf
									@honeypot
									
									<fieldset>
										{{-- report_type_id --}}
										@php
											$reportTypes ??= [];
										@endphp
										@include('helpers.forms.fields.select2', [
											'label'           => t('Reason'),
											'id'              => 'reportTypeId',
											'name'            => 'report_type_id',
											'required'        => true,
											'placeholder'     => t('Select a reason'),
											'options'         => $reportTypes,
											'optionValueName' => 'id',
											'optionTextName'  => 'name',
											'value'           => null,
											'baseClass'       => ['wrapper' => 'mb-3 col-md-6 col-12'],
										])
										
										{{-- email --}}
										@if (auth()->check() && isset(auth()->user()->email))
											<input type="hidden" name="email" value="{{ auth()->user()->email }}">
										@else
											@include('helpers.forms.fields.email', [
												'label'       => t('Your Email'),
												'id'          => 'email',
												'name'        => 'email',
												'required'    => (getAuthField() == 'email'),
												'placeholder' => t('enter_your_email'),
												'value'       => null,
												'attributes'  => ['maxlength' => 60, 'data-valid-type' => 'email'],
												'prefix'      => '<i class="fa-regular fa-envelope"></i>',
												'baseClass'   => ['wrapper' => 'mb-3 col-md-6 col-12'],
											])
										@endif
										
										{{-- message --}}
										@include('helpers.forms.fields.textarea', [
											'label'         => t('Message'),
											'name'          => 'message',
											'placeholder'   => t('enter_your_message'),
											'required'      => true,
											'value'         => null,
											'attributes'    => ['rows' => 7],
											'pluginOptions' => ['height' => 200]
										])
										
										{{-- captcha --}}
										@include('helpers.forms.fields.captcha', ['label' => trans('auth.captcha_human_verification')])
										
										<input type="hidden" name="post_id" value="{{ data_get($post, 'id') }}">
										<input type="hidden" name="abuseForm" value="1">
										
										{{-- button --}}
										<div class="row mb-3 mt-5">
											<div class="col-md-6 text-start">
												<a href="{{ rawurldecode(url()->previous()) }}" class="btn btn-default btn-lg btn-block">
													{{ t('Back') }}
												</a>
											</div>
											<div class="col-md-6 text-end">
												<button type="submit" class="btn btn-primary btn-lg btn-block">
													{{ t('Send Report') }}
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
