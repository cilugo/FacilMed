@extends('layouts.auth')

@section('titulo', 'Nova senha')

@section('conteudo')
    <h1 class="title">Crie uma senha nova</h1>
    <p class="subtitle">Mínimo de 8 caracteres.</p>

    <form method="POST" action="{{ route('password.store') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="form-group">
            <label for="email">E-mail</label>
            <input class="input @error('email') input--erro @enderror" type="email" id="email" name="email"
                   value="{{ old('email', $request->email) }}" autocomplete="username" required>
            @error('email') <span class="campo-erro">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="password">Senha nova</label>
            <input class="input @error('password') input--erro @enderror" type="password" id="password" name="password"
                   autocomplete="new-password" required autofocus>
            @error('password') <span class="campo-erro">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">Repita a senha nova</label>
            <input class="input" type="password" id="password_confirmation" name="password_confirmation"
                   autocomplete="new-password" required>
        </div>

        <button type="submit" class="login-button" style="margin-top: 8px;">Salvar senha nova</button>
    </form>
@endsection
