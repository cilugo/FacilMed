{{-- Login único para os quatro tipos de conta. Depois de entrar, /dashboard
     manda cada um para o próprio painel. Visual: public/css/login.css. --}}
@extends('layouts.auth')

@section('titulo', 'Entrar')

@section('conteudo')
    <h1 class="title">Bem-vindo de volta!</h1>
    <p class="subtitle">Faça login para continuar</p>

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="form-group">
            <label for="email">E-mail</label>
            <input class="input @error('email') input--erro @enderror" type="email" id="email" name="email"
                   value="{{ old('email') }}" placeholder="exemplo@exemplo.com" autocomplete="username" required autofocus>
            @error('email') <span class="campo-erro">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="password">Senha</label>
            <input class="input @error('password') input--erro @enderror" type="password" id="password" name="password"
                   placeholder="Sua senha" autocomplete="current-password" required>
            @error('password') <span class="campo-erro">{{ $message }}</span> @enderror
        </div>

        <div class="linha-opcoes">
            <label class="lembrar"><input type="checkbox" name="remember"> Lembrar de mim</label>
            <a href="{{ route('password.request') }}" class="forgot-password">Esqueci minha senha</a>
        </div>

        <button type="submit" class="login-button">Entrar</button>
    </form>

    <p class="register">
        Não tem uma conta? <a href="{{ route('cadastro.escolher') }}">Cadastre-se</a>
    </p>
@endsection
