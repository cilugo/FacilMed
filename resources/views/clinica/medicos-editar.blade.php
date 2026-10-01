{{--
    Clínica → Meus médicos → Editar médico. Dados: Clinica\MedicoController@editar.

    01/10/2026 (plano novo do grupo): o médico só vê a agenda; o perfil dele
    passou para a clínica. MedicoPolicy::gerenciar: só médico com vínculo ativo
    numa unidade desta clínica. Nome e CRM ficam só para leitura — foram
    conferidos juntos na base simulada do FacilMed no cadastro.

    Se o médico atende em mais de uma clínica, o perfil é um só: o que esta
    clínica salva aparece também nas outras (a tela avisa).
--}}
@extends('layouts.painel')

@section('titulo', 'Editar médico')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Formatador;
    $minhasEsp = old('especialidades', $medico->especialidades->pluck('id')->all());
    $principal = (int) old('principal', optional($medico->especialidades->firstWhere('pivot.principal', true))->id);
    $meusConv  = old('convenios', $medico->convenios->pluck('id')->all());
    $outrosLugares = $medico->vinculos->where('ativo', true)
        ->filter(fn ($v) => $v->local->clinica_id !== auth()->user()->clinica->id);
@endphp

@section('conteudo')

    <a href="{{ route('clinica.medicos') }}" class="fm-voltar"><x-icone nome="chevron-left" /> Meus médicos</a>

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">{{ $medico->user->name }}</h1>
            <p class="fm-subtitulo">CRM {{ $medico->crm }}/{{ $medico->uf }} · como ele aparece para os pacientes.</p>
        </div>
        @if ($medico->status_verificacao === 'verificado')
            <a href="{{ route('publico.medico', $medico) }}" class="fm-pilula" target="_blank" rel="noopener">
                Ver perfil público <x-icone nome="chevron-right" />
            </a>
        @endif
    </div>

    @if ($outrosLugares->isNotEmpty())
        <p class="fm-dica" style="margin-top: 18px;">
            <x-icone nome="lightbulb" />
            <span>{{ $medico->user->name }} também atende em {{ $outrosLugares->pluck('local.nome')->join(', ', ' e ') }}.
                O perfil é um só: o que você salvar aqui aparece lá também.</span>
        </p>
    @endif

    {{-- ===================== DADOS PROFISSIONAIS ===================== --}}
    <section class="fm-painel" style="margin-top: 18px;">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="user" /> Dados profissionais</h2>
            @if ($medico->status_verificacao === 'verificado')
                <span class="fm-etiqueta fm-etiqueta--verde" title="Conferido na base simulada do FacilMed">CRM conferido</span>
            @endif
        </header>

        <form method="POST" action="{{ route('clinica.medicos.atualizar', $medico) }}" class="fm-form fm-form--duas">
            @csrf
            @method('PUT')

            <div class="fm-campo">
                <label for="name">Nome completo</label>
                <input id="name" value="{{ $medico->user->name }}" disabled>
                <span class="fm-campo__ajuda">Nome e CRM foram conferidos juntos na base simulada do FacilMed e não mudam por aqui.</span>
            </div>

            <div class="fm-campo">
                <label for="crm">CRM</label>
                <input id="crm" value="{{ $medico->crm }}/{{ $medico->uf }}" disabled>
            </div>

            <div class="fm-campo {{ $errors->has('anos_atuacao') ? 'fm-campo--erro' : '' }}">
                <label for="anos_atuacao">Anos de atuação</label>
                <input id="anos_atuacao" name="anos_atuacao" type="number" min="0" max="70" value="{{ old('anos_atuacao', $medico->anos_atuacao) }}">
                @error('anos_atuacao') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-campo {{ $errors->has('telefone_profissional') ? 'fm-campo--erro' : '' }}">
                <label for="telefone_profissional">Telefone profissional</label>
                <input id="telefone_profissional" name="telefone_profissional" type="tel" inputmode="numeric" maxlength="15"
                       value="{{ old('telefone_profissional', Formatador::telefone($medico->telefone_profissional)) }}" placeholder="(12) 99999-9999">
                @error('telefone_profissional') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-campo fm-campo--largo {{ $errors->has('bio') ? 'fm-campo--erro' : '' }}">
                <label for="bio">Sobre o médico</label>
                <textarea id="bio" name="bio" maxlength="1000" placeholder="Formação, áreas de interesse, como é o atendimento.">{{ old('bio', $medico->bio) }}</textarea>
                <span class="fm-campo__ajuda">Aparece no perfil público e na lista de médicos disponíveis.</span>
                @error('bio') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-form__acoes">
                <button type="submit" class="fm-botao">Salvar dados</button>
            </div>
        </form>
    </section>

    {{-- ===================== ESPECIALIDADES ===================== --}}
    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="stethoscope" /> Especialidades</h2>
        </header>

        <form method="POST" action="{{ route('clinica.medicos.especialidades', $medico) }}" class="fm-form">
            @csrf
            @method('PUT')

            <div class="fm-opcoes">
                @foreach ($especialidades as $esp)
                    <div class="fm-opcao">
                        <label class="fm-marcar fm-marcar--linha">
                            <input type="checkbox" name="especialidades[]" value="{{ $esp->id }}" @checked(in_array($esp->id, array_map('intval', (array) $minhasEsp), true))>
                            <span>{{ $esp->nome }}</span>
                        </label>
                        <label class="fm-opcao__principal" title="Especialidade principal">
                            <input type="radio" name="principal" value="{{ $esp->id }}" @checked($principal === $esp->id)>
                            principal
                        </label>
                    </div>
                @endforeach
            </div>
            @error('especialidades') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            @error('especialidades.*') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            @error('principal') <span class="fm-campo__erro">{{ $message }}</span> @enderror

            <p class="fm-campo__ajuda">A principal aparece primeiro no perfil dele. Tirar uma especialidade tira também os
                preços dela; não dá para tirar se houver consulta futura marcada nela.</p>

            <div class="fm-form__acoes">
                <button type="submit" class="fm-botao">Salvar especialidades</button>
            </div>
        </form>
    </section>

    {{-- ===================== CONVÊNIOS ===================== --}}
    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="shield" /> Convênios que o médico aceita</h2>
        </header>

        <form method="POST" action="{{ route('clinica.medicos.convenios', $medico) }}" class="fm-form">
            @csrf
            @method('PUT')

            @if ($convenios->isNotEmpty())
                <div class="fm-opcoes">
                    @foreach ($convenios as $conv)
                        <label class="fm-marcar fm-marcar--linha fm-opcao">
                            <input type="checkbox" name="convenios[]" value="{{ $conv->id }}" @checked(in_array($conv->id, array_map('intval', (array) $meusConv), true))>
                            <span>{{ $conv->nome }}</span>
                        </label>
                    @endforeach
                </div>
            @else
                <p class="fm-vazio">Nenhum convênio ativo na plataforma.</p>
            @endif
            @error('convenios.*') <span class="fm-campo__erro">{{ $message }}</span> @enderror

            <p class="fm-campo__ajuda">Vale para todos os lugares onde ele atende por convênio (inclusive em outra clínica). Aceitando um
                convênio, ele aceita todos os planos dele. Os convênios do FacilMed são fictícios, criados para demonstração.</p>

            <div class="fm-form__acoes">
                <button type="submit" class="fm-botao">Salvar convênios</button>
            </div>
        </form>
    </section>

@endsection
