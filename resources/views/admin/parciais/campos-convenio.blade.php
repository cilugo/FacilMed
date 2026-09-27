{{--
    Campos do formulário de convênio (usado no "Novo convênio" e no
    "Editar"). Recebe:
      $bag     nome do error bag (novoConvenio / convenio_{id})
      $c       o Convenio sendo editado, ou null
      $refNome true para o Alpine focar o campo nome ao abrir
    $valor e $erro vêm da view admin/convenios.
--}}
@php $id = $bag; @endphp

<div class="fm-campo {{ $erro($bag, 'nome') ? 'fm-campo--erro' : '' }}">
    <label for="{{ $id }}-nome">Nome do convênio *</label>
    <input id="{{ $id }}-nome" name="nome" type="text" maxlength="150" required
           value="{{ $valor($bag, 'nome', $c?->nome) }}" placeholder="Ex.: SpSaúde"
           @if ($refNome) x-ref="nome" @endif>
    @if ($erro($bag, 'nome')) <span class="fm-campo__erro">{{ $erro($bag, 'nome') }}</span> @endif
</div>

<div class="fm-campo {{ $erro($bag, 'cnpj') ? 'fm-campo--erro' : '' }}">
    <label for="{{ $id }}-cnpj">CNPJ</label>
    <input id="{{ $id }}-cnpj" name="cnpj" type="text" maxlength="18" inputmode="numeric"
           value="{{ $valor($bag, 'cnpj', $c?->cnpj ? \App\Support\Documento::cnpj($c->cnpj) : '') }}" placeholder="00.000.000/0000-00">
    @if ($erro($bag, 'cnpj')) <span class="fm-campo__erro">{{ $erro($bag, 'cnpj') }}</span> @endif
</div>

<div class="fm-campo {{ $erro($bag, 'telefone') ? 'fm-campo--erro' : '' }}">
    <label for="{{ $id }}-telefone">Telefone</label>
    <input id="{{ $id }}-telefone" name="telefone" type="tel" maxlength="20"
           value="{{ $valor($bag, 'telefone', $c?->telefone) }}" placeholder="(12) 4002-1000">
    @if ($erro($bag, 'telefone')) <span class="fm-campo__erro">{{ $erro($bag, 'telefone') }}</span> @endif
</div>

<div class="fm-campo {{ $erro($bag, 'email') ? 'fm-campo--erro' : '' }}">
    <label for="{{ $id }}-email">E-mail</label>
    <input id="{{ $id }}-email" name="email" type="email" maxlength="150"
           value="{{ $valor($bag, 'email', $c?->email) }}" placeholder="contato@convenio.test">
    @if ($erro($bag, 'email')) <span class="fm-campo__erro">{{ $erro($bag, 'email') }}</span> @endif
</div>

<div class="fm-campo fm-campo--largo {{ $erro($bag, 'descricao') ? 'fm-campo--erro' : '' }}">
    <label for="{{ $id }}-descricao">Descrição</label>
    <textarea id="{{ $id }}-descricao" name="descricao" maxlength="1000"
              placeholder="Ex.: Convênio regional com rede própria.">{{ $valor($bag, 'descricao', $c?->descricao) }}</textarea>
    @if ($erro($bag, 'descricao')) <span class="fm-campo__erro">{{ $erro($bag, 'descricao') }}</span> @endif
</div>
