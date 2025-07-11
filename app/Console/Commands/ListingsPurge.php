<?php
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

namespace App\Console\Commands;

// Increase the server resources
$iniConfigFile = __DIR__ . '/../../Helpers/Common/Functions/ini.php';
if (file_exists($iniConfigFile)) {
	include_once $iniConfigFile;
}

use App\Models\Country;
use App\Models\Post;
use App\Models\Scopes\ActiveScope;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;
use App\Notifications\PostArchived;
use App\Notifications\PostDeleted;
use App\Notifications\PostWilBeDeleted;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class ListingsPurge extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'listings:purge';
	
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Delete all old Listings.';
	
	/**
	 * Default Listing Expiration Duration
	 *
	 * @var int
	 */
	private int $unactivatedPostsExpiration = 30;       // Delete the unactivated Posts after this expiration
	private int $activatedPostsExpiration = 30;         // Archive the activated Posts after this expiration
	private int $archivedPostsExpiration = 7;           // Delete the archived Posts after this expiration
	private int $manuallyArchivedPostsExpiration = 90;  // Delete the manually archived Posts after this expiration
	
	public function __construct()
	{
		parent::__construct();
		
		$this->applyRequiredSettings();
	}
	
	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		if (isDemoDomain(config('app.url'))) {
			$msg = t('demo_mode_message');
			$this->cmdLogger($msg);
			exit();
		}
		
		// Get all countries
		$countries = Country::query()->withoutAppends()->withoutGlobalScope(ActiveScope::class);
		if ($countries->doesntExist()) {
			$msg = 'No country found.';
			$this->cmdLogger($msg);
			exit();
		}
		
		// Get the default/current locale
		$defaultLocale = app()->getLocale();
		
		// Browse countries
		foreach ($countries->lazy() as $country) {
			
			// Get the country locale
			$countryLocale = getCountryMainLangCode(collect($country));
			if (empty($countryLocale) || !is_string($countryLocale)) {
				$countryLocale = $defaultLocale;
			}
			
			// Set the country locale
			app()->setLocale($countryLocale);
			
			// Get country's (non-permanent) items
			$posts = Post::query()
				->withoutAppends()
				->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->inCountry($country->code)
				->columnIsEmpty('is_permanent')
				->whereHas('category', fn ($q) => $q->columnIsEmpty('is_for_permanent'));
			
			if ($posts->doesntExist()) {
				$msg = 'No listings in "' . $country->name . '" (' . strtoupper($country->code) . ') website.';
				$this->cmdLogger($msg);
				
				continue;
			}
			
			/*
			 * Items Processing (Using Eloquent Cursor Method)
			 * The cursor method allows you to iterate through your database records using a cursor, which will only execute a single query.
			 * When processing large amounts of data, the cursor method may be used to greatly reduce your memory usage
			 */
			foreach ($posts->lazy() as $post) {
				$this->itemProcessing($post, $country);
			}
			
		}
		
		$msg = 'END.';
		$this->cmdLogger($msg);
	}
	
	/**
	 * @param \App\Models\Post $post
	 * @param \App\Models\Country $country
	 * @return void
	 */
	private function itemProcessing(Post $post, Country $country): void
	{
		// Debug
		// if ($country->code != 'US') return;
		
		// Get the Country's TimeZone
		$timeZone = (!empty($country->time_zone)) ? $country->time_zone : config('app.timezone');
		
		// Get the current Datetime
		$today = now($timeZone);
		
		// Ensure that the date columns are not null and are Carbon objects
		$createdAt = $post->created_at ?? now($timeZone);
		if (!$createdAt instanceof Carbon) {
			$createdAt = (new Carbon($createdAt))->timezone($timeZone);
		}
		$archivedAt = $post->archived_at ?? now($timeZone);
		if (!$archivedAt instanceof Carbon) {
			$archivedAt = (new Carbon($archivedAt))->timezone($timeZone);
		}
		$archivedManuallyAt = $post->archived_manually_at ?? now($timeZone);
		if (!$archivedManuallyAt instanceof Carbon) {
			$archivedManuallyAt = (new Carbon($archivedManuallyAt))->timezone($timeZone);
		}
		$deletionMailSentAt = $post->deletion_mail_sent_at ?? now($timeZone);
		if (!$deletionMailSentAt instanceof Carbon) {
			$deletionMailSentAt = (new Carbon($deletionMailSentAt))->timezone($timeZone);
		}
		
		// Debug
		// dd($createdAt->diffInDays($today));
		
		/* For non-activated items */
		if (!isVerifiedPost($post)) {
			// Delete non-active items after '$this->unactivatedPostsExpiration' days
			if ($createdAt->diffInDays($today) >= $this->unactivatedPostsExpiration) {
				$post->delete();
			}
			
			/*
			 * IMPORTANT
			 * Exit: Non-activated item expected treatment applied
			 */
			
			return;
		}
		
		/* For activated items */
		
		/* Is it a website with premium options enabled? */
		/*
		 * Important:
		 * The basic packages can be saved as paid in the "payments" table by the OfflinePayment plugin
		 * So, don't apply the fake basic features, so we have to exclude packages whose price is 0.
		 */
		$isNotBasic = fn ($q) => $q->where('price', '>', 0);
		
		// Subscription ============================================================
		// Load the post's user's subscription payment
		$post->loadMissing([
			'user' => function ($query) use ($isNotBasic) {
				$query->with(['payment' => fn ($q) => $q->withWhereHas('package', $isNotBasic)]);
			},
		]);
		
		$hasValidSubscription = (
			!empty($post->user)
			&& !empty($post->user->payment)
			&& !empty($post->user->payment->package)
			&& !empty($post->user->payment->package->expiration_time)
		);
		if ($hasValidSubscription) {
			$this->activatedPostsExpiration = $post->user->payment->package->expiration_time ?? 0;
		}
		
		/* Check if the item's user is premium|featured */
		if (!empty($post->user) && $post->user->featured == 1) {
			// Un-featured the item's user when its payment expires du to the validity period
			if (empty($post->user->payment)) {
				$post->user->featured = 0;
				$post->push();
			}
		}
		
		// Promotion ============================================================
		// Load the item's promotion payment
		$post->loadMissing(['payment' => fn ($q) => $q->withWhereHas('package', $isNotBasic)]);
		
		if (!empty($post->payment) && !empty($post->payment->package)) {
			if (!empty($post->payment->package->expiration_time)) {
				$this->activatedPostsExpiration = $post->payment->package->expiration_time;
			}
		}
		
		/* Check if the item is premium|featured */
		if ($post->featured == 1) {
			// Un-featured the item when its payment expires du to the validity period
			if (!empty($post->payment)) {
				/*
				 * IMPORTANT
			 	 * Exit: Premium|featured item expected treatment applied
			 	 */
				
				return;
			}
			
			// Un-featured
			$post->featured = 0;
			$post->save();
			
			/*
			 * Payment is not found:
			 * Continue to apply non-premium|non-featured treatment
			 */
		}
		
		/* For non-archived items (Not to be confused with "non-activated items") */
		// Auto-archive
		if (empty($post->archived_at)) {
			// Archive all activated listings after '$this->activatedPostsExpiration' days
			if ($createdAt->diffInDays($today) >= $this->activatedPostsExpiration) {
				// Archive
				$post->archived_at = $today;
				$post->save();
				
				if ($country->active == 1) {
					try {
						// Send Notification Email to the Author
						$post->notify(new PostArchived($post, $this->archivedPostsExpiration));
                                        } catch (Throwable $e) {
                                                Log::error('Notification failed', [
                                                        'error' => $e->getMessage(),
                                                ]);
					}
				}
			}
			
			/*
			 * IMPORTANT
			 * Exit: Non-archived item expected treatment applied
			 */
			
			return;
		}
		
		/* For archived items (Not to be confused with "activated items") */
		// Auto-delete
		// $today = $today->addDays(4); // Debug
		
		// Count days since the item has been archived
		$daysSinceListingHasBeenArchived = $archivedAt->diffInDays($today);
		
		// Send one alert email each X day started from Y days before the final deletion until the item deletion (using 'archived_at')
		// Start alert email sending from 7 days earlier (for example)
		$daysEarlier = 7;       // In days (Y)
		$intervalOfSending = 2; // In days (X)
		
		if (empty($post->archived_manually_at)) {
			$archivedPostsExpirationSomeDaysEarlier = $this->archivedPostsExpiration - $daysEarlier;
		} else {
			$archivedPostsExpirationSomeDaysEarlier = $this->manuallyArchivedPostsExpiration - $daysEarlier;
		}
		
		if ($daysSinceListingHasBeenArchived >= $archivedPostsExpirationSomeDaysEarlier) {
			// Update the '$daysEarlier' to show in the mail
			$daysEarlier = $daysEarlier - $daysSinceListingHasBeenArchived;
			
			if ($daysEarlier > 0) {
				// Count days since the item's deletion mail has been sent (Using the 'deletion_mail_sent_at' column)
				$daysSinceListingDeletionMailHasBeenSent = $deletionMailSentAt->diffInDays($today);
				
				/*
				 * =============================================================
				 * Send a deletion mail when:
				 * - deletion mails are never sent before
				 * - the latest sending is earlier than the interval of sending
				 * =============================================================
				 */
				if (empty($post->deletion_mail_sent_at) || $daysSinceListingDeletionMailHasBeenSent >= $intervalOfSending) {
					try {
						$post->notify(new PostWilBeDeleted($post, $daysEarlier));
                                        } catch (Throwable $e) {
                                                Log::error('Notification failed', [
                                                        'error' => $e->getMessage(),
                                                ]);
					}
					
					// Update the field 'deletion_mail_sent_at' with today timestamp
					$post->deletion_mail_sent_at = $today;
					$post->save();
				}
			}
		}
		
		// Delete all archived item '$this->archivedPostsExpiration' days later (using 'archived_at')
		if ($daysSinceListingHasBeenArchived >= $this->archivedPostsExpiration) {
			if ($country->active == 1) {
				try {
					// Send Notification Email to the Author
					$post->notify(new PostDeleted($post));
                                } catch (Throwable $e) {
                                        Log::error('Notification failed', [
                                                'error' => $e->getMessage(),
                                        ]);
				}
			}
			
			// Delete
			$post->delete();
		}
		
		/*
		 * IMPORTANT
		 * Exit: Archived item expected treatment applied
		 */
	}
	
	// PRIVATE
	
	private function applyRequiredSettings(): void
	{
		$this->unactivatedPostsExpiration = (int)config(
			'settings.cron.unactivated_listings_expiration',
			$this->unactivatedPostsExpiration
		);
		$this->activatedPostsExpiration = (int)config(
			'settings.cron.activated_listings_expiration',
			$this->activatedPostsExpiration
		);
		$this->archivedPostsExpiration = (int)config(
			'settings.cron.archived_listings_expiration',
			$this->archivedPostsExpiration
		);
		$this->manuallyArchivedPostsExpiration = (int)config(
			'settings.cron.manually_archived_listings_expiration',
			$this->manuallyArchivedPostsExpiration
		);
	}
	
	/**
	 * @param $msg
	 * @return void
	 */
	private function cmdLogger($msg): void
	{
		if (isCli()) {
			$this->warn($msg);
		} else {
			$this->printWeb($msg);
		}
	}
	
	/**
	 * @param $var
	 */
	private function printWeb($var)
	{
		// Only for Debug !
		// echo '<pre>'; print_r($var); echo '</pre>';
	}
}
