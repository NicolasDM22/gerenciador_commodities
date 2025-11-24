@props(['user', 'isAdmin' => false])

<header class="top-bar">
    <div class="profile">
        {{-- Lógica unificada do Avatar --}}
        <img class="avatar" 
             src="{{ !empty($user->foto_blob) ? 'data:'.$user->foto_mime.';base64,'.$user->foto_blob : 'https://ui-avatars.com/api/?name='.urlencode($user->nome ?? $user->usuario).'&background=random' }}" 
             alt="Avatar">
        
        <div class="profile-info">
            <strong>{{ $user->nome ?? $user->usuario }}</strong>
            <span>{{ $user->email ?? 'Sem e-mail' }}</span>
        </div>
    </div>

    <div class="top-actions">
        {{-- A variável $slot é onde os botões específicos de cada página vão entrar --}}
        {{ $slot }}

        {{-- O botão de Sair é padrão em todas as páginas --}}
        <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
            @csrf
            <button class="button button-primary" type="submit">Sair</button>
        </form>
    </div>
</header>