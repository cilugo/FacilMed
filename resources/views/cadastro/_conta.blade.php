{{-- Campos de conta, iguais nos três cadastros. --}}
<p class="fm-secao-form">Acesso</p>
@include('cadastro._campo', ['nome' => 'name', 'rotulo' => $rotuloNome ?? 'Nome completo', 'obrigatorio' => true, 'atributos' => 'autocomplete="name" maxlength="255"'])
@include('cadastro._campo', ['nome' => 'email', 'rotulo' => 'E-mail', 'tipo' => 'email', 'obrigatorio' => true, 'atributos' => 'autocomplete="email"'])
@include('cadastro._campo', ['nome' => 'password', 'rotulo' => 'Senha', 'tipo' => 'password', 'obrigatorio' => true, 'ajuda' => 'Mínimo de 8 caracteres.', 'atributos' => 'autocomplete="new-password" minlength="8" maxlength="72"'])
@include('cadastro._campo', ['nome' => 'password_confirmation', 'rotulo' => 'Repita a senha', 'tipo' => 'password', 'obrigatorio' => true, 'atributos' => 'autocomplete="new-password"'])
