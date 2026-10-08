{{--
    Avatar com foto de perfil (01/10/2026). Sem foto — ou se a imagem não
    carregar — aparecem as iniciais, como antes.

    Uso:  <x-avatar :nome="$user->name" :foto="$user->foto_url" />
          <x-avatar :nome="..." :foto="..." class="fm-avatar--grande" />
--}}
@props(['nome' => '', 'foto' => null])

<span {{ $attributes->merge(['class' => 'fm-avatar']) }}>
    {{ \App\Support\Formatador::iniciais($nome) }}
    @if ($foto)
        <img src="{{ $foto }}" alt="" class="fm-avatar__foto" onerror="this.remove()">
    @endif
</span>
