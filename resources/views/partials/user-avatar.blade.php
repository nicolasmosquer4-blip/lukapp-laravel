{{-- Avatar del usuario: foto subida o iniciales (renderizado en servidor, sin parpadeo) --}}
<div class="{{ $class ?? 'user-avatar' }}" @isset($id) id="{{ $id }}" @endisset
     @if ($authUser?->avatar_url) style="background-image:url('{{ $authUser->avatar_url }}');background-size:cover;background-position:center;" @endif>@unless ($authUser?->avatar_url){{ mb_strtoupper(mb_substr($authUser?->name ?? 'U', 0, 2)) }}@endunless</div>
