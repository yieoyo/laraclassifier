@php
	$accountMenu ??= collect();
	$accountMenu = ($accountMenu instanceof \Illuminate\Support\Collection) ? $accountMenu : collect();
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
							<a href="#{{ $boxId }}" data-bs-toggle="collapse" class="float-end"><i class="fa fa-angle-down"></i></a>
						</h5>
						@foreach($menu as $key => $value)
							<div class="panel-collapse collapse show" id="{{ $boxId }}">
								<ul class="acc-list">
									<li>
										<a {!! $value['isActive'] ? 'class="active"' : '' !!} href="{{ $value['url'] }}">
											<i class="{{ $value['icon'] }}"></i> {{ $value['name'] }}
											@if (!empty($value['countVar']))
												<span class="badge badge-pill{{ $value['cssClass'] ?? '' }}">
													{{ \App\Helpers\Number::short($value['countVar']) }}
												</span>
											@endif
										</a>
									</li>
								</ul>
							</div>
						@endforeach
					</div>
				@endforeach
			@endif
			
		</div>
	</div>
</aside>
