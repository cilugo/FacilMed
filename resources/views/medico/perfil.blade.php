{{--
    Médico → Meu perfil. Dados: Medico\PerfilController@edit (README §7.1).

    Quatro formulários: dados profissionais, especialidades, convênios e senha.

    SENHA TEMPORÁRIA: médico cadastrado pela clínica entra com uma senha
    provisória, e o middleware ExigirTrocaDeSenha só libera esta tela até ele
    trocar. Nesse caso o formulário de senha vem PRIMEIRO, em destaque.

    Trocar CRM/UF passa de novo pela base simulada (texto: "conferido na base
    simulada do FacilMed" — nunca "validado no CFM").
--}}
@extends('layouts.painel')

@section('titulo', 'Meu perfil')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Uf;
    use App\Support\Formatador;
    $user = $medico->user;
    $erroSenha = $errors->updatePassword;
    $minhasEsp = old('especialidades', $medico->especialidades->pluck('id')->all());
    $principal = (int) old('principal', optional($medico->especialidades->firstWhere('pivot.principal', true))->id);
    $meusConv  = old('convenios', $medico->convenios->pluck('id')->all());
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Meu perfil</h1>
            <p class="fm-subtitulo">Como você aparece para os pacientes.</p>
        </div>
        @if ($medico->status_verificacao === 'verificado')
            <a href="{{ route('publico.medico', $medico) }}" class="fm-pilula" target="_blank" rel="noopener">
                Ver meu perfil público <x-icone nome="chevron-right" />
            </a>
        @endif
    </div>

    @if (session('status') === 'password-updated')
        <div class="fm-flash fm-flash--ok" role="status">Senha trocada.</div>
    @endif

    {{-- ===================== SENHA (em destaque se for temporária) ===================== --}}
    @if ($medico->senha_temporaria)
        {{-- Até trocar a senha, o middleware ExigirTrocaDeSenha bloqueia todo o
             resto (inclusive os outros formulários desta tela). Então só a senha aparece. --}}
        <p class="fm-dica" style="margin-top: 18px;">
            <x-icone nome="lightbulb" />
            <span>Você entrou com a <strong>senha provisória</strong> que a clínica te passou. Crie uma senha sua para
                liberar o resto do sistema — depois disso você completa o seu perfil aqui mesmo.</span>
        </p>
        @include('medico.parciais.senha', ['destaque' => true])
    @else

    {{-- ===================== DADOS PROFISSIONAIS ===================== --}}
    <section class="fm-painel" style="margin-top: 18px;">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="user" /> Dados profissionais</h2>
            @if ($medico->status_verificacao === 'verificado')
                <span class="fm-etiqueta fm-etiqueta--verde" title="Conferido na base simulada do FacilMed">CRM conferido</span>
            @endif
        </header>

        <form method="POST" action="{{ route('medico.perfil.atualizar') }}" class="fm-form fm-form--duas">
            @csrf
            @method('PUT')

            <div class="fm-campo {{ $errors->has('name') ? 'fm-campo--erro' : '' }}">
                <label for="name">Nome completo *</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" required minlength="3" maxlength="255">
                @error('name') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-campo">
                <label for="email">E-mail</label>
                <input id="email" value="{{ $user->email }}" disabled>
                <span class="fm-campo__ajuda">É o seu login; não muda por aqui.</span>
            </div>

            <div class="fm-campo {{ $errors->has('crm') ? 'fm-campo--erro' : '' }}">
                <label for="crm">CRM *</label>
                <input id="crm" name="crm" inputmode="numeric" maxlength="10" required value="{{ old('crm', $medico->crm) }}">
                <span class="fm-campo__ajuda">Se mudar, o novo CRM é conferido na base simulada do FacilMed.</span>
                @error('crm') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-campo {{ $errors->has('uf') ? 'fm-campo--erro' : '' }}">
                <label for="uf">UF do CRM *</label>
                <select id="uf" name="uf" required>
                    @foreach (Uf::TODAS as $uf)
                        <option value="{{ $uf }}" @selected(old('uf', $medico->uf) === $uf)>{{ $uf }}</option>
                    @endforeach
                </select>
                @error('uf') <span class="fm-campo__erro">{{ $message }}</span> @enderror
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
                <label for="bio">Sobre você</label>
                <textarea id="bio" name="bio" maxlength="1000" placeholder="Formação, áreas de interesse, como é o seu atendimento.">{{ old('bio', $medico->bio) }}</textarea>
                <span class="fm-campo__ajuda">Aparece no seu perfil público.</span>
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

        <form method="POST" action="{{ route('medico.perfil.especialidades') }}" class="fm-form">
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

            <p class="fm-campo__ajuda">A principal aparece primeiro no seu perfil. Tirar uma especialidade tira também os
                preços dela; não dá para tirar se houver consulta futura marcada nela.</p>

            <div class="fm-form__acoes">
                <button type="submit" class="fm-botao">Salvar especialidades</button>
            </div>
        </form>
    </section>

    {{-- ===================== CONVÊNIOS ===================== --}}
    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="shield" /> Convênios que aceito</h2>
        </header>

        <form method="POST" action="{{ route('medico.perfil.convenios') }}" class="fm-form">
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

            <p class="fm-campo__ajuda">Vale para todos os lugares onde você atende por convênio. Aceitando um convênio, você
                aceita todos os planos dele. Os convênios do FacilMed são fictícios, criados para demonstração.</p>

            <div class="fm-form__acoes">
                <button type="submit" class="fm-botao">Salvar convênios</button>
            </div>
        </form>
    </section>

        @include('medico.parciais.senha', ['destaque' => false])
    @endif

@endsection
