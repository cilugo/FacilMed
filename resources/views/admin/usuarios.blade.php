{{--
    Admin → Usuários. Dados: Admin\UsuarioController@index (README §7.3).

    Filtros (tipo, situação, busca por nome/e-mail) e, por conta:
      - Bloquear: motivo obrigatório (mín. 10). Com consulta futura, precisa
        marcar "cancelar as consultas" — senão o back-end devolve session('erro')
        e nada é bloqueado. Não aparece para administrador.
      - Desbloquear: só para quem está bloqueado.

    O motivo do bloqueio é interno: a pessoa bloqueada só vê "sua conta está
    bloqueada", sem detalhe (AGENTS.md §3).

    Cada formulário manda "_form" = "bloquear-ID" para o erro de validação
    voltar aberto na linha certa.
--}}
@extends('layouts.painel')

@section('titulo', 'Usuários')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Formatador;
    $tipos = ['paciente' => 'Paciente', 'medico' => 'Médico', 'clinica' => 'Clínica', 'admin' => 'Administrador'];
    $situacoes = ['ativo' => ['Ativa', 'verde'], 'bloqueado' => ['Bloqueada', 'rosa'], 'inativo' => ['Inativa', 'cinza']];
    $formVolta = old('_form');
    $filtrando = request()->filled('tipo') || request()->filled('status') || request()->filled('busca');
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Usuários</h1>
            <p class="fm-subtitulo">Todas as contas da plataforma. Bloqueie quem não deve mais acessar.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.usuarios') }}" class="fm-filtros">
        <div class="fm-campo" style="flex-grow: 2;">
            <label for="busca">Buscar</label>
            <input id="busca" name="busca" value="{{ request('busca') }}" placeholder="Nome ou e-mail" maxlength="100">
        </div>
        <div class="fm-campo">
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo">
                <option value="">Todos</option>
                @foreach ($tipos as $valor => $rotulo)
                    <option value="{{ $valor }}" @selected(request('tipo') === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select>
        </div>
        <div class="fm-campo">
            <label for="status">Situação</label>
            <select id="status" name="status">
                <option value="">Todas</option>
                @foreach ($situacoes as $valor => [$rotulo])
                    <option value="{{ $valor }}" @selected(request('status') === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="fm-botao fm-botao--pequeno">Filtrar</button>
        @if ($filtrando)
            <a href="{{ route('admin.usuarios') }}" class="fm-botao fm-botao--suave fm-botao--pequeno">Limpar</a>
        @endif
    </form>

    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="users" /> Contas</h2>
            <span class="fm-painel__periodo">{{ $usuarios->total() }} {{ $usuarios->total() === 1 ? 'conta' : 'contas' }}</span>
        </header>

        @if ($usuarios->isNotEmpty())
            <ul class="fm-lista">
                @foreach ($usuarios as $u)
                    @php
                        [$rotuloSit, $tomSit] = $u->foiExcluida()
                            ? ['Excluída', 'cinza']   // 30/09: o próprio paciente excluiu (dados anonimizados)
                            : ($situacoes[$u->status] ?? [ucfirst($u->status), 'cinza']);
                        $esteForm = $formVolta === 'bloquear-' . $u->id;
                    @endphp
                    <li class="fm-conta" x-data="{ bloqueando: {{ $esteForm ? 'true' : 'false' }} }">
                        <div class="fm-conta__linha">
                            <span class="fm-avatar">{{ Formatador::iniciais($u->name) }}</span>
                            <div class="fm-conta__info">
                                <strong>{{ $u->name }}</strong>
                                <span>{{ $u->email }}</span>
                            </div>
                            <div class="fm-chips fm-conta__etiquetas">
                                <span class="fm-chip fm-chip--cinza">{{ $tipos[$u->tipo] ?? $u->tipo }}</span>
                                <span class="fm-etiqueta fm-etiqueta--{{ $tomSit }}">{{ $rotuloSit }}</span>
                            </div>
                            <div class="fm-conta__acoes">
                                @if ($u->status === 'bloqueado')
                                    <form method="POST" action="{{ route('admin.usuarios.desbloquear', $u) }}">
                                        @csrf
                                        <button type="submit" class="fm-botao fm-botao--ok fm-botao--pequeno">Desbloquear</button>
                                    </form>
                                @elseif ($u->tipo !== 'admin' && ! $u->foiExcluida())
                                    <button type="button" class="fm-botao fm-botao--perigo fm-botao--pequeno" @click="bloqueando = !bloqueando" :aria-expanded="bloqueando">Bloquear</button>
                                @endif
                            </div>
                        </div>

                        @if ($u->status === 'bloqueado')
                            <p class="fm-conta__extra fm-campo__ajuda">
                                Bloqueada{{ $u->bloqueado_em ? ' em ' . Formatador::dataCurta($u->bloqueado_em) : '' }}.
                                Motivo (interno): {{ $u->motivo_bloqueio ?: '—' }}
                            </p>
                        @endif

                        @if ($u->foiExcluida())
                            <p class="fm-conta__extra fm-campo__ajuda">
                                Excluída pelo próprio paciente em {{ Formatador::dataCurta($u->excluida_em) }}. Os dados pessoais foram apagados.
                            </p>
                        @endif

                        @if ($u->status !== 'bloqueado' && $u->tipo !== 'admin' && ! $u->foiExcluida())
                            <form method="POST" action="{{ route('admin.usuarios.bloquear', $u) }}" class="fm-form fm-form--caixa fm-conta__extra" x-show="bloqueando" x-cloak>
                                @csrf
                                <input type="hidden" name="_form" value="bloquear-{{ $u->id }}">
                                <div class="fm-campo {{ $esteForm && $errors->has('motivo') ? 'fm-campo--erro' : '' }}">
                                    <label for="motivo-{{ $u->id }}">Motivo do bloqueio *</label>
                                    <input id="motivo-{{ $u->id }}" name="motivo" minlength="10" maxlength="255" required
                                           value="{{ $esteForm ? old('motivo') : '' }}" placeholder="Ex.: conta usada para marcar consultas falsas">
                                    <span class="fm-campo__ajuda">Fica registrado aqui. A pessoa só vê que a conta está bloqueada, sem o motivo.</span>
                                    @if ($esteForm) @error('motivo') <span class="fm-campo__erro">{{ $message }}</span> @enderror @endif
                                </div>
                                <label class="fm-marcar">
                                    <input type="checkbox" name="cancelar_consultas" value="1">
                                    <span><strong>Cancelar as consultas futuras ligadas a esta conta</strong><br>
                                        Obrigatório se houver consulta marcada. Os envolvidos recebem e-mail.</span>
                                </label>
                                <div class="fm-form__acoes">
                                    <button type="button" class="fm-botao fm-botao--suave fm-botao--pequeno" @click="bloqueando = false">Voltar</button>
                                    <button type="submit" class="fm-botao fm-botao--perigo fm-botao--pequeno">Confirmar bloqueio</button>
                                </div>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>

            {{ $usuarios->links('painel.parciais.paginacao') }}
        @else
            <p class="fm-vazio">Nenhuma conta{{ $filtrando ? ' com esses filtros' : '' }}.</p>
        @endif
    </section>

@endsection
