{{--
    Página inicial. Dados: HomeController@index.

    Visual e estrutura vêm da home que o grupo fez no protótipo
    (paginas/home.html + css/home.css), agora com dados do banco:
    especialidades reais, médicos verificados e a busca funcionando.
--}}
@extends('layouts.site')

@section('menu', 'inicio')

@section('conteudo')

    {{-- ========================= HERO ========================= --}}
    <section class="hero">
        <div class="container hero-content">
            <div class="hero-text">
                {{-- 01/10/2026 (plano novo do grupo): a home começa pela busca de
                     clínicas perto do paciente - especialidade + onde ele está. --}}
                <h1>Encontre clínicas e hospitais<br>perto de você</h1>
                <p>Diga a especialidade e onde você está. Veja quem atende perto, a nota de quem já foi,
                    os planos aceitos e os horários livres — e marque sem telefonar.</p>

                @include('busca._onde', ['origem' => null, 'filtros' => [], 'raioPadrao' => 5])

                @php $maisBuscadas = $especialidades->where('destaque', true)->take(6); @endphp
                @if ($maisBuscadas->isNotEmpty())
                    <div class="mais-buscadas">
                        <span>Mais buscadas:</span>
                        @foreach ($maisBuscadas as $esp)
                            <a href="{{ route('busca.locais', ['especialidade' => $esp->slug]) }}" class="filtro">{{ $esp->nome }}</a>
                        @endforeach
                    </div>
                @endif

            <div class="hero-carousel" id="heroCarousel">
                {{-- Fotos em public/imgs/sliderinicio/. [arquivo, legenda] --}}
                @php
                    $slides = [
                        ['si1.jpeg', 'Atendimento rápido'],
                        ['si2.jpeg', 'Equipe especializada'],
                        ['si3.jpeg', 'Atendimento de confiança'],
                        ['si4.jpeg', 'Agende em poucos cliques'],
                        ['si5.jpeg', 'Encontre a especialidade certa'],
                        ['si6.jpeg', 'Use seu convênio ou particular'],
                        ['si7.jpeg', 'Veja os horários disponíveis'],
                        ['si8.jpeg', 'Clínicas perto de você'],
                        ['si9.jpeg', 'Isso é FacilMed'],
                    ];
                @endphp
                <div class="carousel-track" id="carouselTrack">
                    @foreach ($slides as [$arquivo, $legenda])
                        <div class="carousel-slide">
                            <img src="{{ asset('imgs/sliderinicio/' . $arquivo) }}" alt="">
                            <span class="carousel-caption">{{ $legenda }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="carousel-dots" id="carouselDots"></div>
            </div>
        </div>
    </section>

    {{-- ========================= COMO FUNCIONA ========================= --}}
    <section class="how-it-works" id="como-funciona">
        <div class="container">
            <div class="section-header center">
                <h2>Como funciona?</h2>
                <p>Encontrar onde se consultar pelo FacilMed é simples.</p>
            </div>

            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Diga o que e onde</h3>
                    <p>Escolha a especialidade e use sua localização ou digite o CEP.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Escolha o local</h3>
                    <p>Clínicas e hospitais do mais perto ao mais longe, com nota, planos e preço.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Marque com o médico</h3>
                    <p>Veja os médicos disponíveis e os horários livres de verdade. Depois, avalie o local.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ========================= ESPECIALIDADES ========================= --}}
    <section class="specialties" id="especialidades">
        <div class="container">
            <div class="section-header">
                <h2>Especialidades</h2>
                <a href="{{ route('busca.locais') }}" class="view-all">Ver clínicas perto de você</a>
            </div>

            <div class="specialties-wrapper">
                <button class="arrow-button left" id="prevSpecialty" type="button" aria-label="Voltar">‹</button>

                <div class="specialties-list" id="specialtiesList">
                    @foreach ($especialidades as $esp)
                        <a href="{{ route('busca.locais', ['especialidade' => $esp->slug]) }}" class="specialty-card">
                            <div class="especialidade-icone"><x-icone :nome="$esp->icone ?? 'stethoscope'" /></div>
                            <div class="specialty-info">
                                <span class="nome-medico">{{ $esp->nome }}</span>
                                <span class="nome-especialidade">
                                    @if ($esp->medicos_count > 0)
                                        {{ $esp->medicos_count }} {{ $esp->medicos_count === 1 ? 'médico' : 'médicos' }}
                                    @else
                                        Em breve
                                    @endif
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>

                <button class="arrow-button right" id="nextSpecialty" type="button" aria-label="Avançar">›</button>
            </div>
        </div>
    </section>

    {{-- ========================= MÉDICOS EM DESTAQUE ========================= --}}
    @if ($medicosDestaque->isNotEmpty())
        <section class="how-it-works">
            <div class="container">
                <div class="section-header">
                    <h2>Médicos bem avaliados</h2>
                    <a href="{{ route('busca.index') }}" class="view-all">Ver todos</a>
                </div>

                <div class="specialties-list">
                    @foreach ($medicosDestaque as $i => $medico)
                        <a href="{{ route('publico.medico', $medico) }}" class="specialty-card" style="min-width: 200px;">
                            {{-- A foto cobre as iniciais; se o arquivo não carregar, o onerror
                                 tira a imagem e as iniciais aparecem no lugar --}}
                            <div class="specialty-photo" style="background-color: {{ \App\Support\Formatador::corAvatar($i) }};">
                                {{ \App\Support\Formatador::iniciais($medico->user->name) }}
                                @if ($fotoMedico = $medico->fotoUrl())
                                    <img src="{{ $fotoMedico }}" alt="" onerror="this.remove()">
                                @endif
                            </div>
                            <div class="specialty-info">
                                <span class="nome-medico">{{ $medico->user->name }}</span>
                                <span class="nome-especialidade">{{ $medico->especialidades->pluck('nome')->join(' · ') }}</span>
                                @if ($medico->total_avaliacoes > 0)
                                    <span class="medico-nota"><x-icone nome="star" /> {{ number_format((float) $medico->media_avaliacoes, 1, ',', '') }}</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ========================= HOSPITAIS E CLÍNICAS ========================= --}}
    {{-- 01/10/2026: do banco (HomeController::locaisDestaque), com a foto, a nota
         e o link para a página de cada um. --}}
    @if ($locaisDestaque->isNotEmpty())
        <section class="specialties" id="hospitais-clinicas">
            <div class="container">
                <div class="section-header">
                    <h2>Hospitais e Clínicas</h2>
                    <a href="{{ route('busca.locais') }}" class="view-all">Ver todos</a>
                </div>

                <div class="specialties-wrapper">
                    <button class="arrow-button left" id="prevClinic" type="button" aria-label="Clínicas anteriores">‹</button>

                    <div class="clinicas-list" id="clinicsList">
                        @foreach ($locaisDestaque as $item)
                            @php $l = $item->local; $foto = $l->fotos->first(); @endphp
                            <a href="{{ route('publico.local', $l) }}" class="clinica-card">
                                @if ($foto)
                                    <img src="{{ $foto->url() }}" alt="" loading="lazy">
                                @else
                                    <span class="clinica-card__sem-foto"><x-icone :nome="$l->tipo === 'hospital' ? 'hospital' : 'building'" /></span>
                                @endif
                                <div class="clinica-info">
                                    <span class="nome-clinica">{{ $l->nome }}</span>
                                    <span class="local-clinica">{{ $l->endereco_completo }}</span>
                                    @if ($item->nota)
                                        <span class="medico-nota"><x-icone nome="star" /> {{ number_format((float) $item->nota->media, 1, ',', '') }} ({{ $item->nota->total }})</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <button class="arrow-button right" id="nextClinic" type="button" aria-label="Próximas clínicas">›</button>
                </div>
            </div>
        </section>
    @endif

    {{-- ========================= SOBRE ========================= --}}
    <section id="sobre">
        <div class="container">
            <div class="section-header center">
                <h2>Sobre nós</h2>
                <p class="sobre-text">O FacilMed é uma plataforma digital que conecta pacientes a profissionais de saúde, facilitando o agendamento de consultas e melhorando o acesso aos serviços médicos. Surgindo apenas como uma ideia em sala de aula, agora o FacilMed está disponível para ajudar você a encontrar o cuidado de saúde que você precisa, quando e onde precisar. Marque consultas, acompanhe seu histórico de consultas, veja a avaliação de outros pacientes e muito mais em um único lugar.</p>
            </div>
        </div>
    </section>

    {{-- ========================= CHAMADA PARA PROFISSIONAIS ========================= --}}
    <section class="chamada">
        <div class="container">
            <div>
                <h2>É clínica ou hospital?</h2>
                {{-- 29/09: o médico não se cadastra sozinho; a clínica cadastra os médicos dela. --}}
                <p>Cadastre a sua clínica ou hospital, depois os seus médicos, e receba agendamentos pelo FacilMed.</p>
            </div>
            <div class="acoes">
                <a href="{{ route('cadastro.clinica') }}" class="btn btn-azul btn-grande">Sou clínica ou hospital</a>
            </div>
        </div>
    </section>

@endsection

@push('scripts')
    <script src="{{ asset('javas/home.js') }}"></script>
    <script src="{{ asset('javas/localizacao.js') }}" defer></script>
@endpush
