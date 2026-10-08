{{--
    Clínica → Editar perfil do médico (01/10/2026).
    Dados: Clinica\MedicoController@editar. Grava: @atualizar
    (AtualizarMedicoPelaClinicaRequest; quem pode: MedicoPolicy).

    O médico não tem conta: o perfil que o usuário vê é mantido pela clínica.
    Nome, CRM e UF não mudam aqui (foram conferidos na base simulada).
--}}
@extends('layouts.painel')

@section('titulo', 'Editar médico')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Formatador;
    $espMarcadas = array_map('intval', (array) old('especialidades', $medico->especialidades->pluck('id')->all()));
    $principalAtual = (int) old('principal', $medico->especialidades->firstWhere('pivot.principal', true)?->id);
    $convMarcados = array_map('intval', (array) old('convenios', $medico->convenios->pluck('id')->all()));
@endphp

@section('conteudo')

    <a href="{{ route('clinica.medicos') }}" class="fm-voltar"><x-icone nome="chevron-left" /> Meus médicos</a>

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">{{ $medico->nome }}</h1>
            <p class="fm-subtitulo">CRM {{ $medico->crm }}/{{ $medico->uf }} · conferido na base simulada do PointMed</p>
        </div>
        <a href="{{ route('publico.medico', $medico) }}" class="fm-botao fm-botao--suave" target="_blank" rel="noopener">Ver perfil público</a>
    </div>

    <p class="fm-dica">
        <x-icone nome="lightbulb" />
        <span>O médico não tem login no PointMed: este é o perfil que os usuários veem. Se ele atende em outra clínica
            também, as duas editam o mesmo perfil.</span>
    </p>

    <form method="POST" action="{{ route('clinica.medicos.atualizar', $medico) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- ============ Foto e apresentação ============ --}}
        <section class="fm-painel" style="margin-top: 18px;">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="user" /> Foto e apresentação</h2>
            </header>

            <div class="fm-foto-perfil">
                <x-avatar :nome="$medico->nome" :foto="$medico->foto_url" class="fm-avatar--foto-perfil fm-avatar--cor"
                          style="background: {{ Formatador::corAvatar($medico->id) }};" />

                <div class="fm-form fm-form--duas" style="flex: 1 1 320px;">
                    <div class="fm-campo {{ $errors->has('foto') ? 'fm-campo--erro' : '' }}">
                        <label for="foto">{{ $medico->foto ? 'Trocar a foto' : 'Foto' }}</label>
                        <input id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp">
                        <span class="fm-campo__ajuda">JPG, PNG ou WEBP, até 2 MB.</span>
                        @error('foto') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                    </div>

                    @if ($medico->foto)
                        <label class="fm-marcar fm-marcar--linha">
                            <input type="checkbox" name="remover_foto" value="1" @checked(old('remover_foto'))>
                            <span>Remover a foto atual</span>
                        </label>
                    @endif

                    <div class="fm-campo {{ $errors->has('anos_atuacao') ? 'fm-campo--erro' : '' }}">
                        <label for="anos_atuacao">Anos de carreira</label>
                        <input id="anos_atuacao" name="anos_atuacao" type="number" min="0" max="70" value="{{ old('anos_atuacao', $medico->anos_atuacao) }}">
                        @error('anos_atuacao') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                    </div>

                    <div class="fm-campo {{ $errors->has('telefone_profissional') ? 'fm-campo--erro' : '' }}">
                        <label for="telefone_profissional">Telefone profissional</label>
                        <input id="telefone_profissional" name="telefone_profissional" inputmode="numeric" maxlength="15"
                               value="{{ old('telefone_profissional', $medico->telefone_profissional) }}" placeholder="(12) 99999-0000">
                        @error('telefone_profissional') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                    </div>

                    <div class="fm-campo fm-campo--largo {{ $errors->has('bio') ? 'fm-campo--erro' : '' }}">
                        <label for="bio">Apresentação</label>
                        <textarea id="bio" name="bio" maxlength="1000" placeholder="Formação, áreas de atuação, público atendido...">{{ old('bio', $medico->bio) }}</textarea>
                        @error('bio') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- ============ Especialidades ============ --}}
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="tag" /> Especialidades</h2>
                <a href="{{ route('clinica.especialidades') }}" class="fm-pilula fm-pilula--pequena">Criar especialidade <x-icone nome="chevron-right" /></a>
            </header>

            <div class="fm-opcoes">
                @foreach ($especialidades as $esp)
                    <div class="fm-opcao" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                        <label class="fm-marcar fm-marcar--linha">
                            <input type="checkbox" name="especialidades[]" value="{{ $esp->id }}" @checked(in_array($esp->id, $espMarcadas, true))>
                            <span>{{ $esp->nome }}</span>
                        </label>
                        <label class="fm-campo__ajuda" style="display: inline-flex; gap: 4px; align-items: center;">
                            <input type="radio" name="principal" value="{{ $esp->id }}" @checked($principalAtual === $esp->id)> principal
                        </label>
                    </div>
                @endforeach
            </div>
            @error('especialidades') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            @error('especialidades.*') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            @error('principal') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            <p class="fm-campo__ajuda" style="margin-top: 10px;">As especialidades marcadas aqui aparecem em todas as unidades onde ele atende.</p>
        </section>

        {{-- ============ Convênios ============ --}}
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="shield" /> Convênios que o médico aceita</h2>
            </header>

            @if ($convenios->isEmpty())
                <p class="fm-vazio">Nenhum convênio ativo na plataforma.</p>
            @else
                <div class="fm-opcoes">
                    @foreach ($convenios as $conv)
                        <label class="fm-marcar fm-marcar--linha fm-opcao">
                            <input type="checkbox" name="convenios[]" value="{{ $conv->id }}" @checked(in_array($conv->id, $convMarcados, true))>
                            <span>{{ $conv->nome }}</span>
                        </label>
                    @endforeach
                </div>
            @endif
            @error('convenios.*') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            <p class="fm-campo__ajuda" style="margin-top: 10px;">
                Em cada unidade, a clínica ainda liga ou desliga "atende convênio" em <em>Convênios</em>. Convênios fictícios:
                a plataforma não tem contrato com nenhuma operadora.
            </p>
        </section>

        <div class="fm-form__acoes">
            <a href="{{ route('clinica.medicos') }}" class="fm-botao fm-botao--suave">Cancelar</a>
            <button type="submit" class="fm-botao">Salvar perfil</button>
        </div>
    </form>

@endsection
