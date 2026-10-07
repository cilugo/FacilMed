{{--
    Foto de perfil da conta logada (01/10/2026). Usada no perfil do
    usuário, da clínica e do admin. Rotas: foto.atualizar / foto.remover
    (FotoController). Enviar a imagem já salva — sem botão extra.
--}}
@php $u = auth()->user(); @endphp
<section class="fm-painel">
    <header class="fm-painel__topo">
        <h2 class="fm-painel__titulo"><x-icone nome="user" /> Foto de perfil</h2>
    </header>

    <div class="fm-foto-perfil">
        <x-avatar :nome="$u->name" :foto="$u->foto_url" class="fm-avatar--foto-perfil" />

        <div class="fm-foto-perfil__acoes">
            <form method="POST" action="{{ route('foto.atualizar') }}" enctype="multipart/form-data" class="fm-form">
                @csrf
                <div class="fm-campo {{ $errors->has('foto') ? 'fm-campo--erro' : '' }}">
                    <label for="foto">{{ $u->foto ? 'Trocar a foto' : 'Enviar uma foto' }}</label>
                    <input id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp" required>
                    <span class="fm-campo__ajuda">JPG, PNG ou WEBP, até 2 MB.</span>
                    @error('foto') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                </div>
                <div class="fm-form__acoes">
                    <button type="submit" class="fm-botao fm-botao--pequeno">Salvar foto</button>
                </div>
            </form>

            @if ($u->foto)
                <form method="POST" action="{{ route('foto.remover') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="fm-botao fm-botao--suave fm-botao--pequeno">Remover foto</button>
                </form>
            @endif
        </div>
    </div>
</section>
