{{-- summernote (WYSIWYG Editor) --}}
{{-- https://summernote.org/examples/ --}}
{{-- https://github.com/summernote/summernote/ --}}
@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'summernote';
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
	
	$allowsLinks ??= (
		isFromAdminPanel()
		|| (
			config('settings.listing_form.remove_url_before') != '1' &&
			config('settings.listing_form.remove_url_after') != '1'
		)
	);
	$height ??= 400;
	$pluginOptions ??= [];
	
	$lang = $pluginOptions['lang'] ?? app()->getLocale();
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
	
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', 'summernote');
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			<textarea
					id="summernote_{{ $id }}"
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
	$pluginBasePath = 'assets/plugins/summernote/';
	$pluginFullPath = public_path($pluginBasePath);
@endphp

{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@pushonce("{$viewName}_assets_styles")
	@if (isFromAdminPanel())
		<link href="{{ asset($pluginBasePath . 'summernote.css') }}" rel="stylesheet">
	@else
		<link href="{{ asset($pluginBasePath . 'summernote-bs4.css') }}" rel="stylesheet">
		<style>
			/*
			 * Default CSS Values for HTML Elements
			 * to prevent the editor's CSS overwrite
			 */
			.note-editor ul {
				list-style-type: disc;
			}
			.note-editor ol {
				list-style-type: decimal;
			}
			.note-editor ul, .note-editor ol {
				list-style-position: inside;
				display: block;
				margin-top: 1em;
				margin-bottom: 1em;
				margin-left: 0;
				margin-right: 0;
				padding-left: 40px;
			}
			.note-editor ul li {
				list-style-type: disc;
			}
			.note-editor ol li {
				list-style-type: decimal;
			}
			.note-editor ul ul, .note-editor ol ul {
				list-style-type: circle;
			}
			.note-editor ol ol, .note-editor ul ol {
				list-style-type: lower-latin;
			}
			.note-editor li {
				display: list-item;
			}
		</style>
	@endif
@endpushonce

@pushonce("{$viewName}_assets_scripts")
	<script src="{{ asset($pluginBasePath . 'summernote.min.js') }}"></script>
	@php
		$editorLocale = '';
		if (file_exists($pluginFullPath . 'lang/summernote-' . getLangTag($lang) . '.js')) {
			$editorLocale = getLangTag($lang);
		}
		if (empty($editorLocale)) {
			if (file_exists($pluginFullPath . 'lang/summernote-' . strtolower($lang) . '.js')) {
				$editorLocale = strtolower($lang);
			}
		}
		if (empty($editorLocale)) {
			$editorLocale = 'en-US';
		}
	@endphp
	@if ($editorLocale != 'en-US')
		<script src="{{ url($pluginBasePath . 'lang/summernote-' . $editorLocale . '.js') }}" type="text/javascript"></script>
	@endif
	
	<script>
		var summernoteSelector = 'textarea.summernote';
		
		var summernoteToolbar = [];
		summernoteToolbar.push(['style', ['style']]);
		summernoteToolbar.push(['font', ['bold', 'underline', 'clear']]);
		summernoteToolbar.push(['color', ['color']]);
		summernoteToolbar.push(['para', ['ul', 'ol', 'paragraph']]);
		summernoteToolbar.push(['table', ['table']]);
		@if ($allowsLinks)
			summernoteToolbar.push(['insert', ['link']]);
		@endif
		@if (isFromAdminPanel())
			summernoteToolbar.push(['view', ['fullscreen', 'codeview']]);
		@endif
		
		onDocumentReady((event) => {
			const lang = '{{ $editorLocale }}';
			
			$(summernoteSelector).summernote({
				lang: lang,
				tabsize: 2,
				height: {{ (int)$height }},
				toolbar: summernoteToolbar
			});
		});
	</script>
@endpushonce

{{-- include field specific assets code --}}
@push("{$viewName}_helper_styles")
@endpush

@push("{$viewName}_helper_scripts")
@endpush
