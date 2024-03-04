@php
	$key ??= '';
	$parents ??= [];
	$langFileName ??= '';
	$item ??= '';
@endphp
<div class="row input-group">
	{{-- Selected Language Key --}}
	@php
		$labelFor = htmlentities($key);
		$label = $key;
	@endphp
	{{ html()->label($label, $labelFor)->class('col-sm-2 col-form-label fw-bold text-end wordwrap') }}
	
	{{-- Master Language Text --}}
	<div class="hidden-sm hidden-xs col-md-5">
		<div class="card bg-light rounded">
			<div class="card-body">
				@php
					if (is_array($parents) && count($parents)) {
						$parentsArray = implode('.', $parents);
						$stringText = trans($langFileName . '.' . $parentsArray . '.' . $key);
					} else {
						$stringText = trans($langFileName . '.' . $key);
					}
				@endphp
				{!! htmlentities($stringText) !!}
			</div>
		</div>
	</div>
	
	{{-- Selected Language Text (textarea) --}}
	<div class="col-sm-10 col-md-5">
		@php
			$fieldName = (empty($parents) ? $key : implode('__', $parents) . '__' . $key);
			$fieldNameEnc = md5($fieldName);
			$fieldAttributes = ['class' => 'form-control', 'rows' => 2];
			$invalidStyle = 'border: 2px solid #ff0000;';
			
			if (preg_match('/(\|)/u', $item)) {
				$subItems = explode('|', $item);
				
				echo '<div style="margin-left: 15px;">';
				foreach ($subItems as $k => $subItem) {
					$subLabelFor = htmlentities($subItem);
					$subItemLabel = (!$k ? trans('admin.singular') : trans('admin.plural'));
					
					preg_match('/^({\w}|\[[\w,]+\])([\w\s:]+)/u', trim($subItem), $matches);
					
					if (!empty($matches)) {
						$subItemLabel = $subItemLabel . ' (' . $matches[1] . ')';
						echo html()->label($subItemLabel . ':', $subLabelFor);
						
						$subHiddenFieldNameEnc = $fieldNameEnc . "[before][]";
						$subHiddenFieldValue = convertUTF8HtmlToAnsi($matches[1]);
						echo html()->hidden($subHiddenFieldNameEnc, $subHiddenFieldValue);
						echo html()->hidden('savedKeys[' . $fieldNameEnc . ']', $fieldName);
						
						$subItem = $matches[2];
					} else {
						echo html()->label($subItemLabel . ':', $subLabelFor);
					}
					
					$subFieldNameEnc = $fieldNameEnc . "[after][]";
					$subFieldValue = convertUTF8HtmlToAnsi($subItem);
					if (empty($subFieldValue)) {
						$fieldAttributes['style'] = $invalidStyle;
					}
					echo html()->textarea($subFieldNameEnc, $subFieldValue)->attributes($fieldAttributes) . '<br>';
					echo html()->hidden('savedKeys[' . $fieldNameEnc . ']', $fieldName);
				}
				echo '</div>';
			} else {
				$fieldValue = convertUTF8HtmlToAnsi($item);
				if (empty($fieldValue)) {
					$fieldAttributes['style'] = $invalidStyle;
				}
				echo html()->textarea($fieldNameEnc, $fieldValue)->attributes($fieldAttributes) . '<br>';
				echo html()->hidden('savedKeys[' . $fieldNameEnc . ']', $fieldName);
			}
		@endphp
	</div>
</div>
