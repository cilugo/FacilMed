{{--
    Clínica → Cadastrar médico. Dados: Clinica\MedicoController@form (README §7.2).

    Dois casos, decididos pelo back-end (CadastrarMedicoPelaClinicaRequest):
      - CRM/UF já existe no PointMed → só cria o vínculo (nome, CPF e
        especialidades são ignorados);
      - CRM novo → conferido na base simulada; os dados passam a ser obrigatórios.
    01/10/2026: o médico é só um PERFIL (sem e-mail de login nem senha).
    Foto, bio, anos de carreira e convênios: em "Editar perfil", depois.
    Depois de salvar, vai para Preços.
--}}
@extends('layouts.painel')

@section('titulo', 'Cadastrar médico')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Uf;
    $espMarcadas = array_map('intval', (array) old('especialidades', []));
@endphp

@section('conteudo')

    <a href="{{ route('clinica.medicos') }}" class="fm-voltar"><x-icone nome="chevron-left" /> Meus médicos</a>

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Cadastrar médico</h1>
            <p class="fm-subtitulo">Vincule um médico a uma unidade da clínica.</p>
        </div>
    </div>

    @if ($unidades->isEmpty())
        <section class="fm-painel" style="margin-top: 18px;">
            <p class="fm-vazio fm-vazio--acao">
                A clínica ainda não tem unidade ativa.
                <a href="{{ route('clinica.unidades') }}" class="fm-pilula fm-pilula--pequena">Cadastrar unidade <x-icone nome="chevron-right" /></a>
            </p>
        </section>
    @else
        <form method="POST" action="{{ route('clinica.medicos.salvar') }}">
            @csrf

            {{-- ============ 1. CRM e unidade ============ --}}
            <section class="fm-painel" style="margin-top: 18px;">
                <header class="fm-painel__topo">
                    <h2 class="fm-painel__titulo"><x-icone nome="badge" /> CRM e unidade</h2>
                </header>

                <div class="fm-form fm-form--tres">
                    <div class="fm-campo {{ $errors->has('crm') ? 'fm-campo--erro' : '' }}">
                        <label for="crm">CRM *</label>
                        <input id="crm" name="crm" inputmode="numeric" maxlength="10" required value="{{ old('crm') }}">
                        @error('crm') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                    </div>

                    <div class="fm-campo {{ $errors->has('uf') ? 'fm-campo--erro' : '' }}">
                        <label for="uf">UF do CRM *</label>
                        <select id="uf" name="uf" required>
                            @foreach (Uf::TODAS as $uf)
                                <option value="{{ $uf }}" @selected(old('uf', 'SP') === $uf)>{{ $uf }}</option>
                            @endforeach
                        </select>
                        @error('uf') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                    </div>

                    <div class="fm-campo {{ $errors->has('local_id') ? 'fm-campo--erro' : '' }}">
                        <label for="local_id">Unidade *</label>
                        <select id="local_id" name="local_id" required>
                            @foreach ($unidades as $u)
                                <option value="{{ $u->id }}" @selected(old('local_id') == $u->id)>{{ $u->nome }}</option>
                            @endforeach
                        </select>
                        @error('local_id') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                    </div>

                    <input type="hidden" name="aceita_particular" value="0">
                    <label class="fm-marcar fm-marcar--linha">
                        <input type="checkbox" name="aceita_particular" value="1" @checked(old('aceita_particular', '1') === '1')>
                        <span>Atende particular nesta unidade</span>
                    </label>

                    <input type="hidden" name="aceita_convenio" value="0">
                    <label class="fm-marcar fm-marcar--linha">
                        <input type="checkbox" name="aceita_convenio" value="1" @checked(old('aceita_convenio') === '1')>
                        <span>Atende por convênio nesta unidade</span>
                    </label>
                </div>

                <p class="fm-dica">
                    <x-icone nome="lightbulb" />
                    <span>O CRM é <strong>conferido na hora na base simulada do PointMed</strong> (projeto acadêmico: não consulta o
                        CFM de verdade), junto com o <strong>nome completo</strong> do médico. Se o médico <strong>já está</strong>
                        no PointMed com esse CRM (atende em outra clínica), ele só é vinculado à unidade — os dados abaixo são ignorados.</span>
                </p>
            </section>

            {{-- ============ 2. Dados do médico (só conta nova) ============ --}}
            <section class="fm-painel">
                <header class="fm-painel__topo">
                    <h2 class="fm-painel__titulo"><x-icone nome="user" /> Dados do médico <small class="fm-campo__ajuda">(só se ainda não estiver no PointMed)</small></h2>
                </header>

                <div class="fm-form fm-form--tres">
                    <div class="fm-campo {{ $errors->has('name') ? 'fm-campo--erro' : '' }}">
                        <label for="name">Nome completo</label>
                        <input id="name" name="name" maxlength="255" value="{{ old('name') }}" placeholder="Ex.: Dra. Fulana de Tal">
                        @error('name') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                    </div>

                    <div class="fm-campo {{ $errors->has('cpf') ? 'fm-campo--erro' : '' }}">
                        <label for="cpf">CPF</label>
                        <input id="cpf" name="cpf" inputmode="numeric" maxlength="14" value="{{ old('cpf') }}" placeholder="000.000.000-00">
                        @error('cpf') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                    </div>

                    <div class="fm-campo fm-campo--largo">
                        <label>Especialidades <span class="fm-campo__ajuda">(a primeira marcada, na ordem da lista, vira a principal — dá para trocar depois em "Editar perfil")</span></label>
                        <div class="fm-opcoes">
                            @foreach ($especialidades as $esp)
                                <label class="fm-marcar fm-marcar--linha fm-opcao">
                                    <input type="checkbox" name="especialidades[]" value="{{ $esp->id }}" @checked(in_array($esp->id, $espMarcadas, true))>
                                    <span>{{ $esp->nome }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('especialidades') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                        @error('especialidades.*') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                    </div>
                </div>

                <p class="fm-campo__ajuda" style="margin-top: 12px;">
                    O médico <strong>não ganha login</strong>: é a clínica que mantém o perfil dele. Foto, apresentação, anos de
                    carreira e convênios aceitos você completa depois, em <em>Meus médicos → Editar perfil</em>.
                </p>
            </section>

            <div class="fm-form__acoes">
                <a href="{{ route('clinica.medicos') }}" class="fm-botao fm-botao--suave">Cancelar</a>
                <button type="submit" class="fm-botao">Salvar e definir preços</button>
            </div>
        </form>
    @endif

@endsection
