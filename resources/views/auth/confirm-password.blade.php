@extends('layouts.auth')

@section('titulo', 'Confirmar senha')

@section('conteudo')
    <h1 class="title">Confirme sua senha</h1>
    <p class="subtitle">Esta área é protegida. Digite sua senha para continuar.</p>

    <form method="POST" action="{{ route('password.confirm') }}" novalidate>
        @csrf
        <div class="form-group">
            <label for="password">Senha</label>
            <input class="input @error('password') input--erro @enderror" type="password" id="password" name="password"
                   autocomplete="current-password" required autofocus>
            @error('password') <span class="campo-erro">{{ $message }}</span> @enderror
        </div>

        <button type="submit" class="login-button" style="margin-top: 8px;">Confirmar</button>
    </form>
@endsection
