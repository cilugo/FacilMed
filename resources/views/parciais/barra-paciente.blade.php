{{--
    Barra de baixo do celular para o PACIENTE logado (01/10/2026, plano novo
    do grupo: "a barra inferior do app tem sempre dois botões: Início e
    Perfil"). Aparece só em tela de celular (até 760 px) e só para paciente.
    Usada nos layouts site e painel. "Início" é a busca de clínicas (home).
--}}
@php $usuarioBarra = auth()->user(); @endphp
@if ($usuarioBarra?->ehPaciente())
    <style>
        .barra-paciente { display: none; }
        @media (max-width: 760px) {
            body { padding-bottom: 64px; }
            .barra-paciente {
                position: fixed; left: 0; right: 0; bottom: 0; z-index: 40;
                display: grid; grid-template-columns: 1fr 1fr;
                background: #fff; border-top: 1px solid #d9e2e8;
                box-shadow: 0 -4px 16px rgba(24, 62, 159, .08);
                padding-bottom: env(safe-area-inset-bottom);
            }
            .barra-paciente a {
                display: grid; justify-items: center; gap: 2px; padding: 9px 0 8px;
                font-size: 12px; font-weight: 700; color: #6b80a8; text-decoration: none;
            }
            .barra-paciente a .fm-icone { width: 22px; height: 22px; }
            .barra-paciente a.is-ativo { color: #183e9f; }
        }
    </style>
    <nav class="barra-paciente" aria-label="Atalhos">
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home', 'busca.*', 'publico.*', 'agendamento.*') ? 'is-ativo' : '' }}">
            <x-icone nome="home" /> Início
        </a>
        <a href="{{ route('paciente.perfil') }}" class="{{ request()->routeIs('paciente.*') ? 'is-ativo' : '' }}">
            <x-icone nome="user" /> Perfil
        </a>
    </nav>
@endif
