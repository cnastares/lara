{{-- checkbox field --}}
@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'checkbox-switch';
	$type = 'checkbox'; // checkbox_switch
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$required ??= false;
	$hint ??= null;
	
	$reverse ??= false;
	$labelClass ??= '';
	$attributes ??= [];
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
	
	$isFieldChecked = str_ends_with($name, '_at') ? !empty($value) : ((int)$value === 1 && $value !== '0');
	
	$attrStr = '';
	$attrStr = (!empty($value) && $isFieldChecked) ? 'checked="checked"' : '';
	if (!empty($attributes)) {
		foreach ($attributes as $attribute => $value) {
			$value = ($attribute == 'class')
				? "form-check-input $value"
				: (($attribute == 'style') ? "cursor: pointer; $value" : $value);
			$attrStr .= !empty($attrStr) ? ' ' : '';
			$attrStr .= $attribute . '="' . $value . '"';
		}
		if (!array_key_exists('class', $attributes)) {
			$attrStr .= !empty($attrStr) ? ' ' : '';
			$attrStr .= 'class="form-check-input"';
		}
		if (!array_key_exists('style', $attributes)) {
			$attrStr .= !empty($attrStr) ? ' ' : '';
			$attrStr .= 'style="cursor: pointer;"';
		}
	} else {
		$attrStr .= !empty($attrStr) ? ' ' : '';
		$attrStr .= 'class="form-check-input" style="cursor: pointer;"';
	}
	
	$labelClass = !empty($labelClass) ? " $labelClass" : '';
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			@php
				$reverseClass = $reverse ? ' form-check-reverse' : '';
			@endphp
			<div class="form-check form-switch{{ $reverseClass }}{{ $isHorizontal ? ' mt-2' : '' }}">
				<input type="hidden" name="{{ $name }}" value="0">
				<input type="checkbox" value="1" name="{{ $name }}" id="{{ $id }}"{!! $attrStr !!}>
				<label class="form-check-label{{ $labelClass }}" for="{{ $id }}">
					{!! $label !!}
				</label>
				
				@include('helpers.forms.partials.hint')
				@include('helpers.forms.partials.validation')
			</div>
			
			@if ($isHorizontal)
		</div>
	@endif
</div>
@include('helpers.forms.partials.newline')
