{{--
    Campos do formulário de plano (novo e editar). Recebe:
      $bag  nome do error bag (novoPlano_{convenio} / plano_{id})
      $p    o Plano sendo editado, ou null
    $valor, $erro, $tipos e $abrangencias vêm da view admin/convenios.
    As opções dos <select> vêm de Plano::TIPOS / Plano::ABRANGENCIAS.
--}}
@php $id = $bag; @endphp

<div class="fm-campo {{ $erro($bag, 'nome') ? 'fm-campo--erro' : '' }}">
    <label for="{{ $id }}-nome">Nome do plano *</label>
    <input id="{{ $id }}-nome" name="nome" type="text" maxlength="150" required
           value="{{ $valor($bag, 'nome', $p?->nome) }}" placeholder="Ex.: SpSaúde Família">
    @if ($erro($bag, 'nome')) <span class="fm-campo__erro">{{ $erro($bag, 'nome') }}</span> @endif
</div>

<div class="fm-campo {{ $erro($bag, 'tipo') ? 'fm-campo--erro' : '' }}">
    <label for="{{ $id }}-tipo">Tipo *</label>
    <select id="{{ $id }}-tipo" name="tipo" required>
        @foreach ($tipos as $chave => $rotulo)
            <option value="{{ $chave }}" @selected($valor($bag, 'tipo', $p?->tipo ?? 'individual') === $chave)>{{ $rotulo }}</option>
        @endforeach
    </select>
    @if ($erro($bag, 'tipo')) <span class="fm-campo__erro">{{ $erro($bag, 'tipo') }}</span> @endif
</div>

<div class="fm-campo {{ $erro($bag, 'abrangencia') ? 'fm-campo--erro' : '' }}">
    <label for="{{ $id }}-abrangencia">Abrangência *</label>
    <select id="{{ $id }}-abrangencia" name="abrangencia" required>
        @foreach ($abrangencias as $chave => $rotulo)
            <option value="{{ $chave }}" @selected($valor($bag, 'abrangencia', $p?->abrangencia ?? 'estadual') === $chave)>{{ $rotulo }}</option>
        @endforeach
    </select>
    @if ($erro($bag, 'abrangencia')) <span class="fm-campo__erro">{{ $erro($bag, 'abrangencia') }}</span> @endif
</div>

<div class="fm-campo fm-campo--largo {{ $erro($bag, 'descricao') ? 'fm-campo--erro' : '' }}">
    <label for="{{ $id }}-descricao">Descrição</label>
    <textarea id="{{ $id }}-descricao" name="descricao" maxlength="1000"
              placeholder="Ex.: Titular e dependentes no mesmo contrato.">{{ $valor($bag, 'descricao', $p?->descricao) }}</textarea>
    @if ($erro($bag, 'descricao')) <span class="fm-campo__erro">{{ $erro($bag, 'descricao') }}</span> @endif
</div>
