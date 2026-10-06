{{--
    Bloco "Trocar senha" dos perfis da clínica e do admin (mesmo de usuário/perfil).
    Rota do Breeze: password.update. Erros no bag "updatePassword".
--}}
@php $erroSenha = $errors->updatePassword; @endphp

<section class="fm-painel" style="margin-top: 18px;">
    <header class="fm-painel__topo">
        <h2 class="fm-painel__titulo"><x-icone nome="shield" /> Trocar senha</h2>
    </header>

    <form method="POST" action="{{ route('password.update') }}" class="fm-form fm-form--duas">
        @csrf
        @method('PUT')

        <div class="fm-campo {{ $erroSenha->has('current_password') ? 'fm-campo--erro' : '' }}">
            <label for="current_password">Senha atual *</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
            @if ($erroSenha->has('current_password')) <span class="fm-campo__erro">{{ $erroSenha->first('current_password') }}</span> @endif
        </div>
        <div></div>

        <div class="fm-campo {{ $erroSenha->has('password') ? 'fm-campo--erro' : '' }}">
            <label for="password">Senha nova *</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8" maxlength="72">
            <span class="fm-campo__ajuda">Mínimo de 8 caracteres.</span>
            @if ($erroSenha->has('password')) <span class="fm-campo__erro">{{ $erroSenha->first('password') }}</span> @endif
        </div>

        <div class="fm-campo">
            <label for="password_confirmation">Repita a senha nova *</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        </div>

        <div class="fm-form__acoes">
            <button type="submit" class="fm-botao">Trocar senha</button>
        </div>
    </form>
</section>
