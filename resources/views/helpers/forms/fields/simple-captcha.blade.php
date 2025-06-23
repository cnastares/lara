{{-- simple-captcha --}}
@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'simple-captcha';
	$type = 'text';
	$label ??= null; // trans('auth.captcha_human_verification')
	$id ??= null;
	$name = 'captcha';
	$placeholder ??= t('captcha_placeholder');
	$hint = t('captcha_hint');
	$wrapper ??= []; // Wrapper attributes (including "class")
	
	$id = !empty($id) ? $id : $name;
	
	$wrapperBaseClass = ($isHorizontal ? 'mb-3 row' : 'mb-3 col-md-12');
	$wrapperClass = $wrapper['class'] ?? $wrapperBaseClass . ' captcha-div';
	$wrapper['class'] = $wrapperClass;
	
	$pluginOptions ??= [];
	
	$captchaType = config('settings.security.captcha');
	$delayToDisplay = $pluginOptions['delayToDisplay'] ?? (int)config('settings.security.captcha_delay', 1000);
	$reloadUrl = $pluginOptions['reloadUrl'] ?? url('captcha/' . $captchaType);
	$blankImage = $pluginOptions['blankImage'] ?? url('images/blank.gif');
	$defaultCaptchaWidth = config('captcha.' . $captchaType . '.width', 150);
	$captchaWidth = $pluginOptions['width'] ?? $defaultCaptchaWidth;
	
	$isSimpleCaptchaEnabled = (
		in_array($captchaType, ['default', 'math', 'flat', 'mini', 'inverse', 'custom'])
		&& !empty(config('captcha.option'))
	);
	
	$hideClass = 'd-none';
	
	if ($isSimpleCaptchaEnabled) {
		$captchaUrl = captcha_src($captchaType);
		$captchaImage = '<img src="' . $blankImage . '" style="cursor: pointer; vertical-align: middle;">';
		$styleCss = ' style="width: ' . $captchaWidth . 'px;"';
		
		// DEBUG
		// The generated key need to be un-hashed before to be stored in session
		// dump(session('captcha.key'));
	}
	
	$reloadTitle = t('captcha_reload_hint');
@endphp
@if ($isSimpleCaptchaEnabled)
	<div @include('helpers.forms.attributes.field-wrapper')>
		@include('helpers.forms.partials.label')
		
		<div class="{{ $isHorizontal ? $colField : '' }}">
			{{--
			<a rel="nofollow" href="javascript:;" class="{{ $hideClass }}" title="{{ $reloadTitle }}">
				<button type="button" class="btn btn-primary btn-refresh" title="{{ $reloadTitle }}"><i class="fa-solid fa-rotate"></i></button>
			</a>
			--}}
			<a rel="nofollow" href="javascript:;" class="btn btn-primary btn-refresh {{ $hideClass }}" title="{{ $reloadTitle }}">
				<i class="fa-solid fa-rotate"></i>
			</a>
			
			@if (!empty($hint))
				<div class="form-text my-1">{!! $hint !!}</div>
			@endif
			
			<input
					type="text"
					name="{{ $name }}"
					autocomplete="off"
					@if (!empty($placeholder))placeholder="{{ $placeholder }}"@endif
					@include('helpers.forms.attributes.field', ['class' => $hideClass])
					{!! $styleCss !!}
			>
			
			@include('helpers.forms.partials.validation')
		</div>
	</div>
	@include('helpers.forms.partials.newline')
@endif

@php
	$viewName = str($viewName)->replace('-', '_')->toString();
@endphp

{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@pushonce("{$viewName}_assets_scripts")
	@if ($isSimpleCaptchaEnabled)
		<script>
			let simpleCaptchaHideClass = '{{ trim($hideClass) }}';
			
			function loadCaptchaImage(captchaImage, captchaUrl) {
				captchaUrl = getTimestampedUrl(captchaUrl);
				
				captchaImage = captchaImage.replace(/src="[^"]*"/gi, 'src="' + captchaUrl + '"');
				
				/* Remove existing <img> */
				const captchaImageEls = document.querySelectorAll('.captcha-div img');
				if (captchaImageEls.length > 0) {
					captchaImageEls.forEach(element => element.remove());
				}
				
				/* Add the <img> tag in the DOM */
				const captchaDivEls = document.querySelectorAll('.captcha-div > div');
				if (captchaDivEls.length > 0) {
					captchaDivEls.forEach(element => element.insertAdjacentHTML('afterbegin', captchaImage));
				}
				
				/* Show the captcha's div only when the image src is fully loaded */
				let newCaptchaImageEls = document.querySelectorAll('.captcha-div img');
				if (newCaptchaImageEls.length > 0) {
					newCaptchaImageEls.forEach(element => {
						element.addEventListener('load', () => {
							const captchaSelectors = [
								'.captcha-div label',
								'.captcha-div a',
								'.captcha-div div',
								'.captcha-div small',
								'.captcha-div input'
							];
							toggleElementsClass(captchaSelectors, 'remove', simpleCaptchaHideClass);
						});
						
						element.addEventListener('error', () => {
							console.error('Error loading captcha image');
						});
					});
				}
			}
			
			function reloadCaptchaImage(captchaImageEl, captchaUrl) {
				captchaUrl = getTimestampedUrl(captchaUrl);
				captchaImageEl.src = captchaUrl;
			}
			
			function getTimestampedUrl(captchaUrl) {
				if (captchaUrl.indexOf('?') !== -1) {
					return captchaUrl;
				}
				
				const timestamp = new Date().getTime();
				let queryString = '?t=' + timestamp;
				captchaUrl = captchaUrl + queryString;
				
				return captchaUrl;
			}
		</script>
	@endif
@endpushonce

{{-- include field specific assets code --}}
@push("{$viewName}_helper_scripts")
	@if ($isSimpleCaptchaEnabled)
		<script>
			onDocumentReady((event) => {
				const captchaImage = '{!! $captchaImage !!}';
				const captchaUrl = '{{ $reloadUrl }}';
				
				/* Load the captcha image */
				{{--
				 * Load the captcha image N ms after the page is loaded
				 *
				 * Admin panel: 0ms
				 * Front:
				 * Chrome: 600ms
				 * Edge: 600ms
				 * Safari: 500ms
				 * Firefox: 100ms
				--}}
				const stTimeout = {{ $delayToDisplay }};
				setTimeout(() => loadCaptchaImage(captchaImage, captchaUrl), stTimeout);
				
				/*
				 * Handle captcha image click
				 * Reload the captcha image on by clicking on it
				 */
				onDomElementsAdded('.captcha-div img', elements => {
					if (elements.length <= 0) {
						return false;
					}
					elements.forEach(element => {
						element.addEventListener('click', e => {
							e.preventDefault();
							reloadCaptchaImage(e.target, captchaUrl);
						});
					});
				});
				
				/*
				 * Handle captcha reload link click
				 * Reload the captcha image on by clicking on the reload link
				 */
				const captchaLinkEl = document.querySelector('.captcha-div a');
				if (captchaLinkEl) {
					captchaLinkEl.addEventListener('click', e => {
						e.preventDefault();
						
						const captchaImage = document.querySelector('.captcha-div img');
						if (captchaImage) {
							reloadCaptchaImage(captchaImage, captchaUrl);
						}
					});
				}
			});
		</script>
	@endif
@endpush
