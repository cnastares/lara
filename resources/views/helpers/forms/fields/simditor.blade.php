{{-- simditor (WYSIWYG Editor) --}}
{{-- https://simditor.tower.im --}}
{{-- https://simditor.tower.im/docs/doc-usage.html --}}
{{-- https://simditor.tower.im//docs/doc-config.html --}}
@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'simditor';
	$type = 'textarea';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$placeholder ??= null;
	$required ??= false;
	$hint ??= null;
	$attributes ??= [];
	
	$height ??= 300;
	$pluginOptions ??= [];
	
	$locale = $pluginOptions['locale'] ?? app()->getLocale();
	$defaultImage = $pluginOptions['defaultImage'] ?? asset('assets/plugins/simditor/images/image.png');
	$elPreviewContainer = $pluginOptions['elPreviewContainer'] ?? '#preview';
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			<textarea
					id="simditor_{{ $id }}"
					name="{{ $name }}"
					@if (!empty($placeholder))placeholder="{{ $placeholder }}"@endif
		            @include('helpers.forms.attributes.field')
		    >{{ $value }}</textarea>
			
			@include('helpers.forms.partials.hint')
			@include('helpers.forms.partials.validation')
			
			@if ($isHorizontal)
		</div>
	@endif
</div>
@include('helpers.forms.partials.newline')

@php
	$viewName = str($viewName)->replace('-', '_')->toString();
@endphp

{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@pushonce("{$viewName}_assets_styles")
	<link href="{{ asset('assets/plugins/simditor/styles/simditor.css') }}" media="all" rel="stylesheet" type="text/css"/>
	@if (config('lang.direction') == 'rtl')
		<link media="all" rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/simditor/styles/simditor-rtl.css') }}" />
	@endif
	<style>
		.simditor .simditor-wrapper > textarea, .simditor .simditor-body {
			min-height: {{ (int)$height }}px;
		}
	</style>
@endpushonce

@pushonce("{$viewName}_assets_scripts")
	<script src="{{ asset('assets/plugins/simditor/scripts/mobilecheck.js') }}"></script>
	<script src="{{ asset('assets/plugins/simditor/scripts/module.js') }}"></script>
	<script src="{{ asset('assets/plugins/simditor/scripts/hotkeys.js') }}"></script>
	<script src="{{ asset('assets/plugins/simditor/scripts/dompurify.js') }}"></script>
	<script src="{{ asset('assets/plugins/simditor/scripts/simditor.js') }}"></script>
@endpushonce

{{-- include field specific assets code --}}
@push("{$viewName}_helper_scripts")
	@php
		$editorI18n = trans('simditor', [], $locale);
		$editorI18nJson = '';
		if (!empty($editorI18n) && is_array($editorI18n)) {
			$editorI18nJson = collect($editorI18n)->toJson();
			$editorI18nJson = convertUTF8HtmlToAnsi($editorI18nJson);
		}
	@endphp
	<script>
		var simditorSelector = 'textarea[name="{{ $name }}"].simditor';
		{{-- var simditorSelector = 'textarea.simditor'; --}}
		var simditorLocale = '{{ $locale }}';
		
		@if (!empty($editorI18nJson))
			Simditor.i18n = {'{{ $locale }}': {!! $editorI18nJson !!}};
		@endif
		
		var simditorToolbar = [];
		simditorToolbar.push('bold');
		simditorToolbar.push('italic');
		simditorToolbar.push('underline');
		simditorToolbar.push('|');
		simditorToolbar.push('title');
		simditorToolbar.push('fontScale');
		simditorToolbar.push('color');
		simditorToolbar.push('|');
		simditorToolbar.push('ul');
		simditorToolbar.push('ol');
		simditorToolbar.push('blockquote');
		simditorToolbar.push('|');
		simditorToolbar.push('table');
		@if ($allowsLinks)
			simditorToolbar.push('link');
			simditorToolbar.push('image');
		@endif
		simditorToolbar.push('|');
		simditorToolbar.push('alignment');
		simditorToolbar.push('indent');
		simditorToolbar.push('outdent');
		@if (isFromAdminPanel())
			simditorToolbar.push('|');
			simditorToolbar.push('code');
		@endif
		
		var simditorMobileToolbar = ['bold', 'italic', 'underline', 'ul', 'ol'];
		
		var simditorAllowedTags = [
			'br', 'span', 'img', 'b', 'strong', 'i', 'strike', 'u', 'font', 'p', 'ul',
			'ol', 'li', 'blockquote', 'pre', 'h1', 'h2', 'h3', 'h4', 'hr', 'table'
		];
		@if ($allowsLinks)
			simditorAllowedTags.push('a');
		@endif
		
		{{-- Fake Code Separator --}}
		(function () {
			onDocumentReady((event) => {
				@if (!empty($editorI18nJson))
					Simditor.locale = simditorLocale;
				@endif
				
				if (mobilecheck()) {
					simditorToolbar = simditorMobileToolbar;
				}
				
				const placeholder = '{{ $placeholder }}';
				const defaultImage = '{{ $defaultImage }}';
				const elPreviewContainer = '{{ $elPreviewContainer }}';
				
				const simditorEl = $(simditorSelector);
				if (simditorEl.length > 0) {
					const simditorOptions = {
						textarea: $(simditorSelector),
						placeholder: placeholder,
						toolbar: simditorToolbar,
						allowedTags: simditorAllowedTags,
						defaultImage: defaultImage,
						pasteImage: false,
						upload: false
					};
					
					const editor = new Simditor(simditorOptions);
					
					const previewEl = $(elPreviewContainer);
					if (previewEl.length > 0) {
						return editor.on('valuechanged', e => {
							return previewEl.html(editor.getValue());
						});
					}
				}
			});
		}).call(this);
	</script>
@endpush
