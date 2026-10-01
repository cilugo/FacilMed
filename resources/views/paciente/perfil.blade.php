{{--
    Paciente → Meu perfil. Dados: Paciente\PerfilController@edit.
    Foto (01/10, no banco, só o paciente vê), dados pessoais, senha (rota
    password.update do Breeze), "Minhas avaliações" (01/10: editar/excluir as
    que ele fez), acessibilidade (dado sensível, só com consentimento — LGPD
    art. 11) e excluir a conta (30/09, LGPD).
--}}
@extends('layouts.painel')

@section('titulo', 'Meu perfil')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('javas/foto.js') }}" defer></script>
@endpush

@php
    use App\Support\Documento;
    $user = $paciente->user;
    $acess = $paciente->acessibilidade;
    $erroSenha = $errors->updatePassword;
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Meu perfil</h1>
            <p class="fm-subtitulo">Seus dados de cadastro.</p>
        </div>
    </div>

    @if (session('status') === 'password-updated')
        <div class="fm-flash fm-flash--ok" role="status">Senha trocada.</div>
    @endif

    {{-- ===================== FOTO (01/10) ===================== --}}
    <section class="fm-painel fm-foto-perfil" style="margin-top: 18px;">
        <div class="fm-foto-perfil__imagem">
            @if ($paciente->foto)
                <img src="{{ $paciente->foto->url() }}" alt="Sua foto" data-previa-foto>
            @else
                <span class="fm-avatar fm-avatar--grande" aria-hidden="true">{{ \App\Support\Formatador::iniciais($user->name) }}</span>
                <img src="" alt="Prévia da foto" data-previa-foto hidden>
            @endif
        </div>
        <div class="fm-foto-perfil__acoes">
            <h2 class="fm-painel__titulo"><x-icone nome="user" /> Sua foto</h2>
            <p class="fm-campo__ajuda">Só você vê a sua foto. JPG, PNG ou WebP; a imagem é reduzida antes de enviar.</p>
            <form method="POST" action="{{ route('paciente.perfil.foto') }}" enctype="multipart/form-data" class="fm-form fm-form--linha">
                @csrf
                <div class="fm-campo {{ $errors->has('foto') ? 'fm-campo--erro' : '' }}">
                    <label for="foto" class="sr-only">Escolher foto</label>
                    <input id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp" data-reduzir-foto required>
                    @error('foto') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="fm-botao fm-botao--pequeno">{{ $paciente->foto ? 'Trocar foto' : 'Enviar foto' }}</button>
            </form>
            @if ($paciente->foto)
                <form method="POST" action="{{ route('paciente.perfil.foto.remover') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="fm-botao fm-botao--suave fm-botao--pequeno">Remover foto</button>
                </form>
            @endif
        </div>
    </section>

    {{-- ===================== DADOS PESSOAIS ===================== --}}
    <section class="fm-painel" style="margin-top: 18px;">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="user" /> Dados pessoais</h2>
        </header>

        <form method="POST" action="{{ route('paciente.perfil.atualizar') }}" class="fm-form fm-form--duas">
            @csrf
            @method('PUT')

            <div class="fm-campo {{ $errors->has('name') ? 'fm-campo--erro' : '' }}">
                <label for="name">Nome completo *</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255">
                @error('name') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-campo">
                <label for="email">E-mail</label>
                <input id="email" value="{{ $user->email }}" disabled>
                <span class="fm-campo__ajuda">É o seu login; não muda por aqui.</span>
            </div>

            <div class="fm-campo">
                <label for="cpf">CPF</label>
                <input id="cpf" value="{{ Documento::cpf($paciente->cpf) }}" disabled>
            </div>

            <div class="fm-campo {{ $errors->has('telefone') ? 'fm-campo--erro' : '' }}">
                <label for="telefone">Telefone</label>
                <input id="telefone" name="telefone" type="tel" inputmode="numeric" maxlength="15"
                       value="{{ old('telefone', \App\Support\Formatador::telefone($user->telefone)) }}" placeholder="(12) 99999-9999">
                @error('telefone') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-campo {{ $errors->has('data_nascimento') ? 'fm-campo--erro' : '' }}">
                <label for="data_nascimento">Data de nascimento</label>
                <input id="data_nascimento" name="data_nascimento" type="date"
                       value="{{ old('data_nascimento', $paciente->data_nascimento?->toDateString()) }}">
                @error('data_nascimento') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-campo">
                <label for="sexo">Sexo</label>
                <select id="sexo" name="sexo">
                    <option value="">Não informar</option>
                    @foreach (['Masculino', 'Feminino', 'Prefiro nao informar'] as $opcao)
                        <option value="{{ $opcao }}" @selected(old('sexo', $paciente->sexo) === $opcao)>{{ $opcao === 'Prefiro nao informar' ? 'Prefiro não informar' : $opcao }}</option>
                    @endforeach
                </select>
            </div>

            <div class="fm-form__acoes">
                <button type="submit" class="fm-botao">Salvar dados</button>
            </div>
        </form>
    </section>

    {{-- ===================== SENHA ===================== --}}
    <section class="fm-painel" style="margin-top: 18px;">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="shield" /> Trocar senha</h2>
        </header>

        <form method="POST" action="{{ route('password.update') }}" class="fm-form fm-form--duas">
            @csrf
            @method('PUT')

            <div class="fm-campo {{ $erroSenha->has('current_password') ? 'fm-campo--erro' : '' }}">
                <label for="current_password">Senha atual *</label>
                <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                @if ($erroSenha->has('current_password')) <span class="fm-campo__erro">{{ $erroSenha->first('current_password') }}</span> @endif
            </div>
            <div></div>

            <div class="fm-campo {{ $erroSenha->has('password') ? 'fm-campo--erro' : '' }}">
                <label for="password">Senha nova *</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required>
                <span class="fm-campo__ajuda">Mínimo de 8 caracteres.</span>
                @if ($erroSenha->has('password')) <span class="fm-campo__erro">{{ $erroSenha->first('password') }}</span> @endif
            </div>

            <div class="fm-campo">
                <label for="password_confirmation">Repita a senha nova *</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
            </div>

            <div class="fm-form__acoes">
                <button type="submit" class="fm-botao">Trocar senha</button>
            </div>
        </form>
    </section>

    {{-- ===================== MINHAS AVALIAÇÕES (01/10) ===================== --}}
    {{-- Dos locais (clínicas, hospitais) e dos médicos (consultas realizadas).
         O comentário aparece aqui porque quem lê é o próprio autor. Para
         qualquer outro paciente e nas páginas públicas, só as estrelas. --}}
    @php $totalAvaliacoes = $avaliacoes->count() + $avaliacoesLocais->count(); @endphp
    <section class="fm-painel" style="margin-top: 18px;" id="avaliacoes">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="star" /> Minhas avaliações</h2>
            <span class="fm-painel__periodo">{{ $totalAvaliacoes }} {{ $totalAvaliacoes === 1 ? 'avaliação' : 'avaliações' }}</span>
        </header>

        @if ($totalAvaliacoes === 0)
            <p class="fm-vazio">Você ainda não avaliou nada. Avalie um local na página dele, e um médico depois de uma consulta realizada (em "Minhas consultas").</p>
        @endif

        @if ($avaliacoesLocais->isNotEmpty())
            <h3 class="fm-subtitulo-lista"><x-icone nome="building" /> Locais</h3>
            <ul class="fm-lista">
                @foreach ($avaliacoesLocais as $a)
                    @include('paciente.parciais.avaliacao-item', [
                        'chave'      => 'local-' . $a->id,
                        'titulo'     => $a->local->nome,
                        'tituloUrl'  => $a->local->estaPublico() ? route('publico.local', $a->local) : null,
                        'detalhe'    => trim(($a->local->clinica?->nome_fantasia ?? '') . ' · ' . $a->local->cidade, ' ·'),
                        'avaliacao'  => $a,
                        'rotaSalvar' => route('publico.local.avaliar', $a->local),
                        'metodo'     => 'POST',
                        'rotaExcluir'=> route('paciente.avaliacoes-locais.excluir', $a),
                        'quemLe'     => 'Só a clínica e a administração leem o comentário.',
                    ])
                @endforeach
            </ul>
        @endif

        @if ($avaliacoes->isNotEmpty())
            <h3 class="fm-subtitulo-lista"><x-icone nome="doctors" /> Médicos</h3>
            <ul class="fm-lista">
                @foreach ($avaliacoes as $a)
                    @include('paciente.parciais.avaliacao-item', [
                        'chave'      => 'medico-' . $a->id,
                        'titulo'     => $a->medico->user->name,
                        'tituloUrl'  => null,
                        'detalhe'    => trim(($a->consulta?->especialidade?->nome ?? '') . ($a->consulta?->vinculo?->local ? ' · ' . $a->consulta->vinculo->local->nome : ''), ' ·'),
                        'avaliacao'  => $a,
                        'rotaSalvar' => route('paciente.avaliacoes.atualizar', $a),
                        'metodo'     => 'PUT',
                        'rotaExcluir'=> route('paciente.avaliacoes.excluir', $a),
                        'quemLe'     => 'Só o médico, a clínica e a administração leem o comentário.',
                    ])
                @endforeach
            </ul>
        @endif
    </section>

    {{-- ===================== ACESSIBILIDADE ===================== --}}
    <section class="fm-painel" style="margin-top: 18px;" x-data="{ precisa: {{ old('possui_deficiencia', $acess ? 1 : 0) ? 'true' : 'false' }} }">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="user-check" /> Acessibilidade</h2>
        </header>

        <p class="fm-dica" style="margin-bottom: 14px;">
            <x-icone nome="lightbulb" />
            <span>Opcional. Só o médico das suas consultas vê essa informação, para preparar o atendimento.
                Você pode apagar quando quiser.</span>
        </p>

        <form method="POST" action="{{ route('paciente.perfil.acessibilidade') }}" class="fm-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="possui_deficiencia" :value="precisa ? 1 : 0">

            <label style="display: flex; gap: 8px; align-items: center; font-weight: 600;">
                <input type="checkbox" x-model="precisa" style="width: 18px; height: 18px;">
                Preciso de algum recurso de acessibilidade no atendimento
            </label>

            <div x-show="precisa" x-cloak style="display: grid; gap: 12px;">
                <div class="fm-campo {{ $errors->has('descricao') ? 'fm-campo--erro' : '' }}">
                    <label for="descricao">Do que você precisa?</label>
                    <textarea id="descricao" name="descricao" maxlength="500" placeholder="Ex.: uso cadeira de rodas; preciso de intérprete de Libras">{{ old('descricao', $acess?->descricao) }}</textarea>
                    @error('descricao') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                </div>
                <label style="display: flex; gap: 8px; align-items: flex-start; font-size: 14px;">
                    <input type="checkbox" name="consentimento" value="1" style="width: 18px; height: 18px; margin-top: 2px;" @checked(old('consentimento', (bool) $acess))>
                    Autorizo o FacilMed a guardar essa informação e mostrá-la ao médico das minhas consultas.
                </label>
                @error('consentimento') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-form__acoes">
                <button type="submit" class="fm-botao" x-text="precisa ? 'Salvar' : '{{ $acess ? 'Apagar informação' : 'Salvar' }}'">Salvar</button>
            </div>
        </form>
    </section>

    {{-- ===================== EXCLUIR CONTA (30/09, LGPD) =====================
         Regra em Paciente::excluirConta(); confirmação no ExcluirContaRequest,
         com erros no saco próprio "excluirConta" (não se mistura com a senha). --}}
    @php $erroExclusao = $errors->excluirConta; @endphp
    <section class="fm-painel" style="margin-top: 18px;" id="excluir-conta">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="user-x" /> Excluir minha conta</h2>
        </header>

        <p style="margin: 0 0 10px;">Se você excluir a conta:</p>
        <ul style="margin: 0 0 16px; padding-left: 20px; display: grid; gap: 6px; list-style: disc;">
            <li>suas consultas marcadas para o futuro são canceladas, e o médico é avisado;</li>
            <li>seu nome, e-mail, telefone, CPF, data de nascimento, os números das suas carteirinhas, as informações de acessibilidade e o que você escreveu nas consultas são apagados;</li>
            <li>as notas que você deu continuam valendo para a média do médico, mas sem o seu nome e sem o comentário;</li>
            <li>as consultas que já aconteceram continuam no histórico do médico, sem nenhum dado seu;</li>
            <li><strong>não dá para desfazer.</strong> Se quiser voltar, é só criar uma conta nova (pode ser com o mesmo e-mail e CPF).</li>
        </ul>

        <form method="POST" action="{{ route('paciente.perfil.excluir') }}" class="fm-form fm-form--duas">
            @csrf
            @method('DELETE')

            {{-- name="current_password": o Laravel nunca guarda esse campo na sessão (ver ExcluirContaRequest) --}}
            <div class="fm-campo {{ $erroExclusao->has('current_password') ? 'fm-campo--erro' : '' }}">
                <label for="excluir_senha">Sua senha *</label>
                <input id="excluir_senha" name="current_password" type="password" autocomplete="current-password" required>
                @if ($erroExclusao->has('current_password')) <span class="fm-campo__erro">{{ $erroExclusao->first('current_password') }}</span> @endif
            </div>
            <div></div>

            <div style="grid-column: 1 / -1;">
                <label style="display: flex; gap: 8px; align-items: flex-start; font-size: 14px;">
                    <input type="checkbox" name="confirmacao" value="1" required style="width: 18px; height: 18px; margin-top: 2px;">
                    Entendo que a exclusão não pode ser desfeita.
                </label>
                @if ($erroExclusao->has('confirmacao')) <span class="fm-campo__erro">{{ $erroExclusao->first('confirmacao') }}</span> @endif
            </div>

            <div class="fm-form__acoes">
                <button type="submit" class="fm-botao fm-botao--perigo">Excluir minha conta</button>
            </div>
        </form>
    </section>

@endsection
