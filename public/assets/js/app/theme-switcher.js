/*
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
 */

if (typeof cookieParams === 'undefined') {
	var cookieExpiresMinutes = 30 * 24 * 60; /* 30 days */
	var cookieParams = {
		expires: cookieExpiresMinutes,
		path: '/',
		secure: true,
		sameSite: 'Strict'
	};
}
if (typeof isAdminPanel === 'undefined') {
	var isAdminPanel = false;
}
if (typeof isSettingsAppDarkModeEnabled === 'undefined') {
	var isSettingsAppDarkModeEnabled = false;
}
if (typeof isSettingsAppSystemThemeEnabled === 'undefined') {
	var isSettingsAppSystemThemeEnabled = false;
}
if (typeof isLoggedUser === 'undefined') {
	var isLoggedUser = false;
}
if (typeof userThemePreference === 'undefined') {
	var userThemePreference = null;
}

onDocumentReady((event) => {
	
	const cookieManager = new CookieManager(cookieParams);
	const config = {
		isFromAdminPanel: isAdminPanel,
		isDarkThemeEnabled: isSettingsAppDarkModeEnabled,
		isSystemThemeEnabled: isSettingsAppSystemThemeEnabled,
		isLoggedUser: isLoggedUser,
		userPreference: userThemePreference,
		cookieExpiresMinutes: cookieParams.expires
	};
	
	/* Update the theme preference on page load */
	const themeDetector = new ThemeDetector(cookieManager, config);
	
	/* Set or unset the theme preference from the theme switcher */
	const themeSwitcherEl = document.getElementById('themeSwitcher');
	if (themeSwitcherEl) {
		const buttonEl = themeSwitcherEl.querySelector('.dropdown-toggle');
		const menuItemsEls = themeSwitcherEl.querySelectorAll('.dropdown-item');
		
		if (buttonEl && menuItemsEls.length > 0) {
			menuItemsEls.forEach(item => {
				item.addEventListener('click', function (e) {
					e.preventDefault();
					
					// Get the selected theme data
					const csrfToken = this.getAttribute('data-csrf-token');
					const userId = this.getAttribute('data-user-id');
					const selectedTheme = this.getAttribute('data-theme');
					const selectedLabel = this.innerHTML.trim();
					
					// Update button data and HTML label
					buttonEl.setAttribute('data-theme', selectedTheme);
					// Sanitization of the HTML to prevent potential XSS attacks by escaping unsafe HTML.
					// DOMPurify is used for better security.
					buttonEl.innerHTML = DOMPurify.sanitize(selectedLabel);
					
					// Remove active class from all items
					menuItemsEls.forEach(i => i.classList.remove('active'));
					
					// Add active class to selected item
					this.classList.add('active');
					
					// Save the selected theme in cookie (guest) or in database (logged-in user)
					themeDetector.setUserTheme(selectedTheme, (theme) => saveThemePreference(csrfToken, userId, theme));
				});
			});
		}
	}
	
});

/**
 * Set the dark mode for a given user in the Database
 *
 * @param csrfToken
 * @param userId
 * @param theme
 */
function saveThemePreference(csrfToken, userId, theme) {
	let url = `${siteUrl}/account/save-theme-preference`;
	let data = {
		'user_id': userId,
		'theme': theme,
		'_token': csrfToken
	};
	
	httpRequest('post', url, data).then(json => {
		
		// let message = langLayout.themePreference.error;
		
		if (typeof json.theme === 'undefined' || typeof json.message === 'undefined') {
			jsAlert(langLayout.themePreference.error || 'Unknown error', 'error');
			return;
		}
		/*
		if (json.theme === 'light') message = langLayout.themePreference.light;
		if (json.theme === 'dark') message = langLayout.themePreference.dark;
		if (json.theme === 'system') message = langLayout.themePreference.system;
		if (json.theme === '' || json.theme === null) message = langLayout.themePreference.empty;
		*/
		jsAlert(json.message, 'success');
		
	}).catch(error => jsAlert(error, 'error', false, true));
}

/**
 * Check if dark theme is enabled
 * @returns {boolean}
 */
function isDarkThemeEnabled() {
	const cookieManager = new CookieManager(cookieParams);
	const config = {
		isDarkThemeEnabled: isSettingsAppDarkModeEnabled,
		isSystemThemeEnabled: isSettingsAppSystemThemeEnabled,
		isLoggedUser: isLoggedUser,
		userPreference: userThemePreference,
		cookieExpiresMinutes: cookieParams.expires
	};
	
	return ThemeDetector.checkDarkTheme(cookieManager, config);
}
