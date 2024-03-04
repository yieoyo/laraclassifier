@if (!empty(data_get($page, 'picture_url')))
<div class="intro-inner">
	<div class="about-intro" style="background:url({{ data_get($page, 'picture_url') }}) no-repeat center;background-size:cover;">
		<div class="dtable hw100">
			<div class="dtable-cell hw100">
				<div class="container text-center">
					<h1 class="intro-title animated fadeInDown" style="color: {!! data_get($page, 'name_color') !!};">
						{{ data_get($page, 'name') }}
					</h1>
                    <h3 class="text-center title-1" style="color: {!! data_get($page, 'title_color') !!};">
						<strong>{{ data_get($page, 'title') }}</strong>
					</h3>
				</div>
			</div>
		</div>
	</div>
</div>
@endif