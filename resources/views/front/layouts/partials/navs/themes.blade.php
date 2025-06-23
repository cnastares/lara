@php
	use App\Enums\ThemePreference;
@endphp
@if (isSettingsAppDarkModeEnabled() || isFromAdminPanel())
	@php
		// Get all themes
		$userThemes = getFormattedThemes();
		
		// Get selected theme
		$defaultTheme = isSettingsAppSystemThemeEnabled()
			? ThemePreference::SYSTEM->value
			: ThemePreference::LIGHT->value;
		$defaultThemeLabel = isSettingsAppSystemThemeEnabled()
			? ThemePreference::SYSTEM->label()
			: ThemePreference::LIGHT->label();
		$selectedTheme = getThemePreference() ?? $defaultTheme;
		$selectedThemeLabel = getFormattedThemes($selectedTheme)['label'] ?? $defaultThemeLabel;
		
		// CSS Classes
		$dropdownClass = '';
		$buttonClass = 'btn btn-secondary';
		if (isFromAdminPanel()) {
			$dropdownClass = ' nav-item';
			$buttonClass = 'nav-link waves-effect waves-dark';
		}
	@endphp
	@if (!empty($userThemes))
		<div id="themeSwitcher" class="dropdown{{ $dropdownClass }}">
			<a href="#"
			   data-theme="{{ $selectedTheme }}"
			   class="{{ $buttonClass }} dropdown-toggle"
			   role="button"
			   data-bs-toggle="dropdown"
			   aria-expanded="false"
			>
				{!! $selectedThemeLabel !!}
			</a>
			
			<ul class="dropdown-menu">
				@foreach($userThemes as $key => $label)
					@php
						$activeClass = ($selectedTheme == $key) ? ' active' : '';
					@endphp
					<li>
						<a href=""
						   data-csrf-token="{{ csrf_token() }}"
						   data-theme="{{ $key }}"
						   data-user-id="{{ $authUser->id ?? null }}"
						   class="dropdown-item{{ $activeClass }}"
						>
							{!! $label['label'] ?? '' !!}
						</a>
					</li>
				@endforeach
			</ul>
		</div>
	@endif
@endif
