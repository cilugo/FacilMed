{{--
    Médico → Onde eu atendo. Dados: Medico\LocalController@index (README §7.1).

    Dois casos:
      - unidade de clínica: só leitura (quem vincula e define preço é a clínica);
      - consultório próprio: o médico cadastra aqui. Depois de salvar, o
        back-end manda para Preços (é o passo seguinte do cadastro).
--}}
@extends('layouts.painel')

@section('titulo', 'Onde eu atendo')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Formatador;
    use App\Support\Uf;
    use App\Models\Disponibilidade;
    $tipos = ['clinica' => 'Clínica', 'hospital' => 'Hospital', 'consultorio' => 'Consultório próprio'];
    $diasForm = array_merge(array_slice(Disponibilidade::DIAS, 1), [Disponibilidade::DIAS[0]]);
    $abrirForm = $errors->any() || $vinculos->isEmpty();
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Onde eu atendo</h1>
            <p class="fm-subtitulo">Os lugares onde os pacientes podem marcar consulta com você.</p>
        </div>
    </div>

    {{-- ===================== LUGARES ===================== --}}
    @if ($vinculos->isNotEmpty())
        <div class="fm-locais">
            @foreach ($vinculos as $v)
                <article class="fm-painel fm-local {{ $v->ativo ? '' : 'is-inativo' }}">
                    <header class="fm-painel__topo">
                        <h2 class="fm-painel__titulo">
                            <x-icone :nome="$v->local->ehConsultorioProprio() ? 'home' : 'building'" />
                            {{ $v->local->nome }}
                        </h2>
                        <span class="fm-etiqueta fm-etiqueta--{{ $v->local->ehConsultorioProprio() ? 'roxo' : 'azul' }}">
                            {{ $tipos[$v->local->tipo] ?? $v->local->tipo }}
                        </span>
                    </header>

                    @if ($v->local->clinica)
                        <p class="fm-local__dono">{{ $v->local->clinica->nome_fantasia }}</p>
                    @endif
                    <p class="fm-local__endereco"><x-icone nome="pin" /> {{ $v->local->endereco_completo }}</p>

                    <div class="fm-chips" style="margin-top: 10px;">
                        @if ($v->aceita_particular)
                            <span class="fm-chip">Particular</span>
                        @endif
                        @if ($v->aceita_convenio)
                            <span class="fm-chip">Convênio</span>
                        @else
                            <span class="fm-chip fm-chip--cinza">Sem convênio</span>
                        @endif
                        @unless ($v->ativo)
                            <span class="fm-chip fm-chip--cinza">Vínculo encerrado</span>
                        @endunless
                    </div>

                    <p class="fm-campo__ajuda" style="margin-top: 10px;">
                        {{ $v->precos->where('ativo', true)->count() }}
                        {{ $v->precos->where('ativo', true)->count() === 1 ? 'preço ativo' : 'preços ativos' }}
                        @if ($v->local->ehConsultorioProprio())
                            · você define os preços e o funcionamento deste lugar.
                        @else
                            · os preços daqui são definidos pela clínica.
                        @endif
                    </p>

                    <div class="fm-acoes-topo" style="margin-top: 12px;">
                        <a href="{{ route('medico.precos') }}" class="fm-pilula fm-pilula--pequena">Preços <x-icone nome="chevron-right" /></a>
                        <a href="{{ route('medico.disponibilidade') }}" class="fm-pilula fm-pilula--pequena">Meus horários <x-icone nome="chevron-right" /></a>
                    </div>
                </article>
            @endforeach
        </div>

        <p class="fm-dica">
            <x-icone nome="lightbulb" />
            <span>Para atender em uma <strong>clínica ou hospital</strong>, a própria clínica faz o vínculo pelo painel dela,
                usando o seu CRM. Aqui você só cadastra o seu <strong>consultório próprio</strong>.</span>
        </p>
    @endif

    {{-- ===================== NOVO CONSULTÓRIO ===================== --}}
    <section class="fm-painel" style="margin-top: 18px;" x-data="{ aberto: {{ $abrirForm ? 'true' : 'false' }} }">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="plus" /> Cadastrar consultório próprio</h2>
            <button type="button" class="fm-pilula fm-pilula--pequena" @click="aberto = !aberto" x-text="aberto ? 'Fechar' : 'Abrir'" :aria-expanded="aberto">Abrir</button>
        </header>

        @if ($vinculos->isEmpty())
            <p class="fm-campo__ajuda" style="margin-bottom: 12px;">Você ainda não tem lugar de atendimento. Cadastre seu consultório
                ou peça para a clínica onde você atende te vincular.</p>
        @endif

        <form method="POST" action="{{ route('medico.locais.salvar') }}" class="fm-form fm-form--tres" x-show="aberto" x-cloak>
            @csrf

            <div class="fm-campo fm-campo--largo {{ $errors->has('nome') ? 'fm-campo--erro' : '' }}">
                <label for="nome">Nome do consultório *</label>
                <input id="nome" name="nome" maxlength="150" required value="{{ old('nome') }}" placeholder="Ex.: Consultório Dra. Helena — Centro">
                @error('nome') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-campo {{ $errors->has('cep') ? 'fm-campo--erro' : '' }}">
                <label for="cep">CEP *</label>
                <input id="cep" name="cep" inputmode="numeric" maxlength="9" required value="{{ old('cep') }}" placeholder="12345-678">
                @error('cep') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-campo {{ $errors->has('endereco') ? 'fm-campo--erro' : '' }}" style="grid-column: span 2;">
                <label for="endereco">Endereço *</label>
                <input id="endereco" name="endereco" maxlength="200" required value="{{ old('endereco') }}" placeholder="Rua, avenida...">
                @error('endereco') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-campo {{ $errors->has('numero') ? 'fm-campo--erro' : '' }}">
                <label for="numero">Número *</label>
                <input id="numero" name="numero" maxlength="20" required value="{{ old('numero') }}">
                @error('numero') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-campo {{ $errors->has('complemento') ? 'fm-campo--erro' : '' }}">
                <label for="complemento">Complemento</label>
                <input id="complemento" name="complemento" maxlength="100" value="{{ old('complemento') }}" placeholder="Sala, andar">
                @error('complemento') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-campo {{ $errors->has('bairro') ? 'fm-campo--erro' : '' }}">
                <label for="bairro">Bairro *</label>
                <input id="bairro" name="bairro" maxlength="100" required value="{{ old('bairro') }}">
                @error('bairro') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-campo {{ $errors->has('cidade') ? 'fm-campo--erro' : '' }}">
                <label for="cidade">Cidade *</label>
                <input id="cidade" name="cidade" maxlength="100" required value="{{ old('cidade') }}">
                @error('cidade') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-campo {{ $errors->has('uf') ? 'fm-campo--erro' : '' }}">
                <label for="uf">UF *</label>
                <select id="uf" name="uf" required>
                    @foreach (Uf::TODAS as $uf)
                        <option value="{{ $uf }}" @selected(old('uf', 'SP') === $uf)>{{ $uf }}</option>
                    @endforeach
                </select>
                @error('uf') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-campo {{ $errors->has('telefone') ? 'fm-campo--erro' : '' }}">
                <label for="telefone">Telefone</label>
                <input id="telefone" name="telefone" type="tel" inputmode="numeric" maxlength="15" value="{{ old('telefone') }}" placeholder="(12) 3456-7890">
                @error('telefone') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <input type="hidden" name="aceita_convenio" value="0">
            <label class="fm-marcar fm-campo--largo">
                <input type="checkbox" name="aceita_convenio" value="1" @checked(old('aceita_convenio'))>
                <span><strong>Atendo por convênio neste consultório</strong><br>
                    Valem os convênios marcados no seu perfil.</span>
            </label>

            {{-- Funcionamento: opcional. Vazio = segunda a sexta, 08:00–18:00. --}}
            <fieldset class="fm-campo--largo fm-funcionamento">
                <legend>Horário de funcionamento do consultório</legend>
                <p class="fm-campo__ajuda">Deixe tudo em branco para usar segunda a sexta, 08:00–18:00. Dia em branco = fechado.
                    Os seus horários de consulta são cadastrados depois, em "Meus horários".</p>

                <div class="fm-funcionamento__grade">
                    @foreach ($diasForm as $i => $dia)
                        @php $idx = array_search($dia, Disponibilidade::DIAS, true); @endphp
                        <div class="fm-funcionamento__dia {{ $errors->has("horarios.$dia.fecha") || $errors->has("horarios.$dia.abre") ? 'fm-campo--erro' : '' }}">
                            <span>{{ Formatador::DIAS_CURTOS[$idx] }}</span>
                            <input type="time" name="horarios[{{ $dia }}][abre]" value="{{ old("horarios.$dia.abre") }}" aria-label="{{ Formatador::DIAS[$idx] }}: abre">
                            <input type="time" name="horarios[{{ $dia }}][fecha]" value="{{ old("horarios.$dia.fecha") }}" aria-label="{{ Formatador::DIAS[$idx] }}: fecha">
                            @if ($errors->has("horarios.$dia.fecha") || $errors->has("horarios.$dia.abre"))
                                <span class="fm-campo__erro">{{ $errors->first("horarios.$dia.fecha") ?: $errors->first("horarios.$dia.abre") }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </fieldset>

            <div class="fm-form__acoes">
                <button type="submit" class="fm-botao">Salvar e definir preços</button>
            </div>
        </form>
    </section>

@endsection
