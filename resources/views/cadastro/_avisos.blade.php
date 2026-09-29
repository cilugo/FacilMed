{{-- Avisos do topo do cadastro: mensagens da sessão e o resumo dos erros. --}}
@if (session('sucesso'))
    <div class="aviso aviso--ok" role="status">{{ session('sucesso') }}</div>
@endif
@if (session('erro'))
    <div class="aviso aviso--erro" role="alert">{{ session('erro') }}</div>
@endif
@if ($errors->any())
    <div class="aviso aviso--erro" role="alert">Confira os campos marcados abaixo.</div>
@endif
