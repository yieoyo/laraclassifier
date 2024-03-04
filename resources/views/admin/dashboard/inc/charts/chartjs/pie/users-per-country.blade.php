@if (config('settings.app.show_countries_charts'))
	@php
		$usersPerCountry ??= [];
		
		$countUsersCountries = (int)data_get($usersPerCountry, 'countCountries');
		$usersDataArr = json_decode(data_get($usersPerCountry, 'data'), true);
		$usersDataArrLabels = data_get($usersDataArr, 'labels') ?? [];
		$countUsersLabels = (is_array($usersDataArrLabels) && count($usersDataArrLabels) > 1) ? count($usersDataArrLabels) : 0;
	@endphp
	
	@if ($countUsersCountries > 1)
		<div class="col-lg-6 col-md-12">
			<div class="card rounded shadow-sm">
				<div class="card-body">
					<div class="d-flex">
						<div>
							<h4 class="card-title mb-1 fw-bold">
								<span class="lstick d-inline-block align-middle"></span>{{ data_get($usersPerCountry, 'title') }}
							</h4>
						</div>
						<div class="ms-auto">
						
						</div>
					</div>
					<div class="position-relative chart-responsive">
						@if ($countUsersLabels > 0)
							<canvas id="pieChartUsers"></canvas>
						@else
							{!! trans('admin.No data found') !!}
						@endif
					</div>
				</div>
			</div>
		</div>
	@endif
	
	@push('dashboard_styles')
		<style>
			canvas {
				-moz-user-select: none;
				-webkit-user-select: none;
				-ms-user-select: none;
			}
		</style>
	@endpush
	
	@push('dashboard_scripts')
		<script>
			@if ($countUsersCountries > 1)
				@if ($countUsersLabels > 0)
					@php
						$usersDisplayLegend = ($countUsersLabels <= 15) ? 'true' : 'false';
					@endphp
					
					var config = {
						type: 'pie', /* pie, doughnut */
						data: {!! data_get($usersPerCountry, 'data') !!},
						options: {
							responsive: true,
							legend: {
								display: {{ $usersDisplayLegend }},
								position: 'right'
							},
							title: {
								display: false
							},
							animation: {
								animateScale: true,
								animateRotate: true
							}
						}
					};
					
					$(function () {
						var ctx = document.getElementById('pieChartUsers').getContext('2d');
						window.myUsersDoughnut = new Chart(ctx, config);
					});
				@endif
			@endif
		</script>
	@endpush
@endif
