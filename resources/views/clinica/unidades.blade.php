{{--
    Clínica → Unidades. Dados: Clinica\UnidadeController@index (README §7.2).

    Lista as unidades com o horário de FUNCIONAMENTO (não confundir com os
    horários de atendimento de cada médico, que ficam dentro dele) e permite
    editar esse horário e cadastrar unidade nova.

    Várias unidades = vários formulários de horário na mesma tela. Para o
    old() e os erros voltarem só no formulário certo, cada um manda um campo
    escondido "_form" (o back-end ignora).
--}}
@extends('layouts.painel')

@section('titulo', 'Unidades')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Formatador;
    use App\Support\Uf;
    $tipos = ['clinica' => 'Clínica', 'hospital' => 'Hospital', 'consultorio' => 'Consultório'];
    $formVolta = old('_form');
    $nomeDia = fn (string $dia) => Formatador::DIAS_CURTOS[array_search($dia, $dias, true)] ?? $dia;
    $ordem = array_merge(array_slice($dias, 1), [$dias[0]]);
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Unidades</h1>
            <p class="fm-subtitulo">Os endereços da clínica e quando cada um funciona.</p>
        </div>
    </div>

    @if ($locais->isNotEmpty())
        <div class="fm-locais">
            @foreach ($locais as $local)
                @php
                    $atuais = $local->horarios->mapWithKeys(fn ($h) => [$h->dia_semana => [
                        'abre' => Formatador::hora($h->abre), 'fecha' => Formatador::hora($h->fecha),
                    ]])->all();
                    $esteForm = $formVolta === 'horarios-' . $local->id;
                @endphp
                <article class="fm-painel fm-local {{ $local->ativo ? '' : 'is-inativo' }}" x-data="{ editando: {{ $esteForm ? 'true' : 'false' }} }">
                    <header class="fm-painel__topo">
                        <h2 class="fm-painel__titulo"><x-icone nome="building" /> {{ $local->nome }}</h2>
                        @if ($local->ativo)
                            <span class="fm-etiqueta fm-etiqueta--azul">{{ $tipos[$local->tipo] ?? $local->tipo }}</span>
                        @else
                            <span class="fm-etiqueta fm-etiqueta--cinza">Desativada</span>
                        @endif
                    </header>

                    <p class="fm-local__endereco"><x-icone nome="pin" /> {{ $local->endereco_completo }}</p>
                    @if ($local->telefone)
                        <p class="fm-local__endereco"><x-icone nome="phone" /> {{ Formatador::telefone($local->telefone) }}</p>
                    @endif

                    <p class="fm-campo__ajuda" style="margin-top: 8px;">
                        {{ $local->vinculos_count }} {{ $local->vinculos_count === 1 ? 'médico atende' : 'médicos atendem' }} aqui
                    </p>

                    <ul class="fm-horario-lista" x-show="!editando">
                        @foreach ($ordem as $dia)
                            <li>
                                <span>{{ $nomeDia($dia) }}</span>
                                @if (isset($atuais[$dia]))
                                    <strong>{{ $atuais[$dia]['abre'] }}–{{ $atuais[$dia]['fecha'] }}</strong>
                                @else
                                    <em>Fechado</em>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    <div class="fm-acoes-topo" style="margin-top: 12px;" x-show="!editando">
                        <button type="button" class="fm-pilula fm-pilula--pequena" @click="editando = true"><x-icone nome="pencil" /> Editar horário</button>
                        <a href="{{ route('clinica.medicos', ['unidade' => $local->id]) }}" class="fm-pilula fm-pilula--pequena">Médicos <x-icone nome="chevron-right" /></a>
                    </div>

                    <form method="POST" action="{{ route('clinica.unidades.horarios', $local) }}" class="fm-form" x-show="editando" x-cloak style="margin-top: 12px;">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_form" value="horarios-{{ $local->id }}">
                        @include('painel.parciais.horarios-funcionamento', ['atuais' => $atuais, 'comOld' => $esteForm])
                        <p class="fm-campo__ajuda">Dia em branco = fechado. Se o horário diminuir, a parte dos médicos que ficar
                            de fora deixa de ser oferecida aos pacientes (consultas já marcadas continuam).</p>
                        <div class="fm-form__acoes">
                            <button type="button" class="fm-botao fm-botao--suave fm-botao--pequeno" @click="editando = false">Cancelar</button>
                            <button type="submit" class="fm-botao fm-botao--pequeno">Salvar horário</button>
                        </div>
                    </form>
                </article>
            @endforeach
        </div>
    @endif

    {{-- ===================== NOVA UNIDADE ===================== --}}
    @php $novaVolta = $formVolta === 'nova-unidade'; @endphp
    <section class="fm-painel" style="margin-top: 18px;" x-data="{ aberto: {{ $novaVolta || $locais->isEmpty() ? 'true' : 'false' }} }">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="plus" /> Nova unidade</h2>
            <button type="button" class="fm-pilula fm-pilula--pequena" @click="aberto = !aberto" x-text="aberto ? 'Fechar' : 'Abrir'" :aria-expanded="aberto">Abrir</button>
        </header>

        <form method="POST" action="{{ route('clinica.unidades.salvar') }}" class="fm-form fm-form--tres" x-show="aberto" x-cloak>
            @csrf
            <input type="hidden" name="_form" value="nova-unidade">
            @php $e = fn (string $campo) => $novaVolta && $errors->has($campo); $v = fn (string $campo, $padrao = '') => $novaVolta ? old($campo, $padrao) : $padrao; @endphp

            <div class="fm-campo {{ $e('nome') ? 'fm-campo--erro' : '' }}" style="grid-column: span 2;">
                <label for="nome">Nome da unidade *</label>
                <input id="nome" name="nome" maxlength="150" required value="{{ $v('nome') }}" placeholder="Ex.: Unidade Centro">
                @if ($e('nome')) <span class="fm-campo__erro">{{ $errors->first('nome') }}</span> @endif
            </div>

            <div class="fm-campo {{ $e('tipo') ? 'fm-campo--erro' : '' }}">
                <label for="tipo">Tipo *</label>
                <select id="tipo" name="tipo" required>
                    <option value="clinica" @selected($v('tipo', 'clinica') === 'clinica')>Clínica</option>
                    <option value="hospital" @selected($v('tipo') === 'hospital')>Hospital</option>
                </select>
                @if ($e('tipo')) <span class="fm-campo__erro">{{ $errors->first('tipo') }}</span> @endif
            </div>

            @foreach ([
                ['cep', 'CEP *', 'inputmode="numeric" maxlength="9" required placeholder="12345-678"'],
                ['endereco', 'Endereço *', 'maxlength="200" required'],
                ['numero', 'Número *', 'maxlength="20" required'],
                ['complemento', 'Complemento', 'maxlength="100"'],
                ['bairro', 'Bairro *', 'maxlength="100" required'],
                ['cidade', 'Cidade *', 'maxlength="100" required'],
            ] as [$campo, $rotulo, $attrs])
                <div class="fm-campo {{ $e($campo) ? 'fm-campo--erro' : '' }}">
                    <label for="u-{{ $campo }}">{{ $rotulo }}</label>
                    <input id="u-{{ $campo }}" name="{{ $campo }}" value="{{ $v($campo) }}" {!! $attrs !!}>
                    @if ($e($campo)) <span class="fm-campo__erro">{{ $errors->first($campo) }}</span> @endif
                </div>
            @endforeach

            <div class="fm-campo {{ $e('uf') ? 'fm-campo--erro' : '' }}">
                <label for="u-uf">UF *</label>
                <select id="u-uf" name="uf" required>
                    @foreach (Uf::TODAS as $uf)
                        <option value="{{ $uf }}" @selected($v('uf', 'SP') === $uf)>{{ $uf }}</option>
                    @endforeach
                </select>
                @if ($e('uf')) <span class="fm-campo__erro">{{ $errors->first('uf') }}</span> @endif
            </div>

            <div class="fm-campo {{ $e('telefone') ? 'fm-campo--erro' : '' }}">
                <label for="u-telefone">Telefone</label>
                <input id="u-telefone" name="telefone" type="tel" inputmode="numeric" maxlength="15" value="{{ $v('telefone') }}">
                @if ($e('telefone')) <span class="fm-campo__erro">{{ $errors->first('telefone') }}</span> @endif
            </div>

            <fieldset class="fm-campo--largo fm-funcionamento">
                <legend>Horário de funcionamento</legend>
                <p class="fm-campo__ajuda">Deixe tudo em branco para usar segunda a sexta, 08:00–18:00. Dia em branco = fechado.</p>
                @include('painel.parciais.horarios-funcionamento', ['comOld' => $novaVolta])
            </fieldset>

            <div class="fm-form__acoes">
                <button type="submit" class="fm-botao">Cadastrar unidade</button>
            </div>
        </form>
    </section>

@endsection
