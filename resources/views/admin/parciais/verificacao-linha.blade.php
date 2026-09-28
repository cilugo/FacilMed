{{--
    Uma linha da tela "Verificar CRM". Recebe $m (Medico com user) e $pendente.
      - pendente:   Aprovar | Rejeitar
      - verificado: Rejeitar (tirar da plataforma)
      - rejeitado:  mostra o motivo e permite desfazer (aprovar de novo)
--}}
@php
    use App\Support\Formatador;
    $esteForm = old('_form') === 'rejeitar-' . $m->id;
    $situacao = match ($m->status_verificacao) {
        'verificado' => ['Conferido na base simulada', 'verde'],
        'rejeitado'  => ['Rejeitado', 'rosa'],
        default      => ['Pendente', 'ambar'],
    };
@endphp

<div class="fm-conta" x-data="{ rejeitando: {{ $esteForm ? 'true' : 'false' }} }">
    <div class="fm-conta__linha">
        <span class="fm-avatar">{{ Formatador::iniciais($m->user->name) }}</span>
        <div class="fm-conta__info">
            <strong>{{ $m->user->name }}</strong>
            <span>CRM {{ $m->crm }}/{{ $m->uf }}
                @if ($m->verificado_em) · {{ Formatador::dataCurta($m->verificado_em) }} @endif
                @if ($m->relationLoaded('especialidades') && $m->especialidades->isNotEmpty())
                    · {{ $m->especialidades->pluck('nome')->join(', ') }}
                @endif
            </span>
        </div>
        <div class="fm-chips fm-conta__etiquetas">
            <span class="fm-etiqueta fm-etiqueta--{{ $situacao[1] }}">{{ $situacao[0] }}</span>
        </div>
        <div class="fm-conta__acoes">
            @if ($pendente || $m->status_verificacao === 'rejeitado')
                <form method="POST" action="{{ route('admin.verificacoes.aprovar', $m) }}">
                    @csrf
                    <button type="submit" class="fm-botao fm-botao--ok fm-botao--pequeno">{{ $pendente ? 'Aprovar' : 'Desfazer rejeição' }}</button>
                </form>
            @endif
            @if ($m->status_verificacao !== 'rejeitado')
                <button type="button" class="fm-botao fm-botao--perigo fm-botao--pequeno" @click="rejeitando = !rejeitando" :aria-expanded="rejeitando">Rejeitar</button>
            @endif
        </div>
    </div>

    @if ($m->status_verificacao === 'rejeitado' && $m->motivo_rejeicao)
        <p class="fm-conta__extra fm-campo__ajuda">Motivo: {{ $m->motivo_rejeicao }}</p>
    @endif

    @if ($m->status_verificacao !== 'rejeitado')
        <form method="POST" action="{{ route('admin.verificacoes.rejeitar', $m) }}" class="fm-form fm-form--caixa fm-conta__extra" x-show="rejeitando" x-cloak>
            @csrf
            <input type="hidden" name="_form" value="rejeitar-{{ $m->id }}">
            <div class="fm-campo {{ $esteForm && $errors->has('motivo') ? 'fm-campo--erro' : '' }}">
                <label for="motivo-rej-{{ $m->id }}">Motivo da rejeição *</label>
                <input id="motivo-rej-{{ $m->id }}" name="motivo" minlength="10" maxlength="255" required
                       value="{{ $esteForm ? old('motivo') : '' }}" placeholder="Ex.: CRM cancelado depois do cadastro">
                @if ($esteForm) @error('motivo') <span class="fm-campo__erro">{{ $message }}</span> @enderror @endif
            </div>
            <p class="fm-campo__ajuda">O médico some da busca e do perfil público. <strong>As consultas futuras dele são canceladas</strong>
                e os pacientes recebem e-mail.</p>
            <div class="fm-form__acoes">
                <button type="button" class="fm-botao fm-botao--suave fm-botao--pequeno" @click="rejeitando = false">Voltar</button>
                <button type="submit" class="fm-botao fm-botao--perigo fm-botao--pequeno">Confirmar rejeição</button>
            </div>
        </form>
    @endif
</div>
