

<!-- In resources/views/layouts/sidebar.blade.php -->
<div class="list-group">
    @foreach($chatSessions as $session)
        <a href="{{ route('chat.show', $session->id) }}"
           class="list-group-item list-group-item-action {{ request()->is('chat/'.$session->id) ? 'active' : '' }}">
            {{ $session->title ?? 'Untitled Session' }}
        </a>
    @endforeach
</div>
