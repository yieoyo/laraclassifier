{{-- regular object attribute --}}
<td>{{ str($entry->{$column['name']})->stripTags()->limit(80, "[...]") }}</td>