@php
	use App\Helpers\Common\Num;
	use Illuminate\Support\Collection;
	
	$accountMenu ??= collect();
	$accountMenu = ($accountMenu instanceof Collection) ? $accountMenu : collect();
@endphp
<aside>
	<div class="inner-box">
		<div class="user-panel-sidebar">
			
			@if ($accountMenu->isNotEmpty())
				@foreach($accountMenu as $group => $menu)
					@php
						$boxId = str($group)->slug();
					@endphp
					<div class="collapse-box">
						<h5 class="collapse-title no-border">
							{{ $group }}&nbsp;
							<a href="#{{ $boxId }}" data-bs-toggle="collapse" class="float-end"><i class="fa-solid fa-angle-down"></i></a>
						</h5>
						@if (!empty($menu))
							<div class="panel-collapse collapse show" id="{{ $boxId }}">
								<ul class="acc-list">
									@foreach($menu as $key => $item)
										<li>
											<a {!! $item['isActive'] ? 'class="active"' : '' !!} href="{{ $item['url'] }}">
												<i class="{{ $item['icon'] }}"></i> {{ $item['name'] }}
												@if (!empty($item['countVar']))
													<span class="badge badge-pill{{ $item['cssClass'] ?? '' }}">
														{{ Num::short($item['countVar']) }}
													</span>
												@endif
											</a>
										</li>
									@endforeach
								</ul>
							</div>
						@endif
					</div>
				@endforeach
			@endif
		
		</div>
	</div>
</aside>
