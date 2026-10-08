@extends('layouts.auth')

@section('titulo', 'Esqueci minha senha')

@section('conteudo')
    <h1 class="title">Esqueceu a senha?</h1>
    <p class="subtitle">Digite o e-mail da sua conta. Vamos mandar um link para você criar uma senha nova.</p>

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf
        <div class="form-group">
            <label for="email">E-mail</label>
            <input class="input @error('email') input--erro @enderror" type="email" id="email" name="email"
                   value="{{ old('email') }}" placeholder="exemplo@dominio.com" autocomplete="email" required autofocus>
            @error('email') <span class="campo-erro">{{ $message }}</span> @enderror
        </div>

        <button type="submit" class="login-button" style="margin-top: 8px;">Enviar link</button>
    </form>

    <p class="register"><a href="{{ route('login') }}">Voltar para o login</a></p>
@endsection
