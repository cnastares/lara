{{-- checklist --}}
@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'checklist';
	$type = 'checkbox'; // checklist
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= [];
	$default ??= null;
	$required ??= false;
	$hint ??= null;
	
	$reverse ??= false;
	$checkLabelClass ??= '';
	$checkLabelClass .= !empty($label) ? (!empty($checkLabelClass) ? ' fw-normal' : 'fw-normal') : '';
	$checkLabelClass = !empty($checkLabelClass) ? " $checkLabelClass" : '';
	$checkboxes ??= [];
	$checkboxesKeyName ??= null; // 'id'
	$checkboxesLabelName ??= null; // 'name'
	$col ??= 4;
	$col = (is_integer($col) && $col >= 1 && $col <= 12) ? $col : 4;
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = old($dotSepName, $value);
	$value = collect($value);
	
	$attrStr = '';
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			@php
				$reverseClass = $reverse ? ' form-check-reverse' : '';
			@endphp
			<div class="row">
				@foreach ($checkboxes as $key => $checkbox)
					@php
						$checkboxId = data_get($checkbox, 'id');
						$checkboxName = data_get($checkbox, 'name');
						$checkboxName = (!str_contains($checkboxName, '[') || !str_contains($checkboxName, ']'))
							? str_replace(['[', ']'], '', $checkboxName) . '[]'
							: $checkboxName;
						$checkboxLabel = data_get($checkbox, 'label');
						$checkboxValue = $key;
						
						$isChecked = (
							in_array($checkboxValue, $value->toArray()) ||
							(
								!empty($checkboxesKeyName) &&
								in_array($checkboxValue, $value->pluck($checkboxesKeyName, $checkboxesKeyName)->toArray())
							)
						);
						
						$checkboxDotSepName = arrayFieldToDotNotation($checkboxName);
						$checkboxId = !empty($checkboxId) ? $checkboxId : str_replace('.', '-', $checkboxDotSepName);
					@endphp
					<div class="col-md-{{ $col }} my-0 py-0">
						<div class="form-check{{ $reverseClass }}">
							<input
									type="checkbox"
									id="{{ $checkboxId }}"
									name="{{ $checkboxName }}"
									value="{{ $checkboxValue }}"
									class="form-check-input" @checked($isChecked)
							>
							<label class="form-check-label fw-normal{{ $checkLabelClass }}" for="{{ $checkboxId }}">
								{!! $checkboxLabel !!}
							</label>
						</div>
					</div>
				@endforeach
			</div>
			
			@include('helpers.forms.partials.hint')
			@include('helpers.forms.partials.validation')
			
			@if ($isHorizontal)
		</div>
	@endif
</div>
@include('helpers.forms.partials.newline')
