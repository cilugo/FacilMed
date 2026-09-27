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
                <h1>Agende sua consulta<br>de forma rápida e fácil</h1>
                <p>Encontre médicos, hospitais e clínicas perto de você.</p>

                <form method="GET" action="{{ route('busca.index') }}" class="busca-caixa" role="search">
                    <div class="busca-campo">
                        <label for="h-especialidade">Especialidade</label>
                        <select id="h-especialidade" name="especialidade">
                            <option value="">Todas</option>
                            @foreach ($especialidades as $esp)
                                <option value="{{ $esp->slug }}">{{ $esp->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="busca-campo">
                        <label for="h-cidade">Cidade</label>
                        <select id="h-cidade" name="cidade">
                            <option value="">Todas</option>
                            @foreach ($cidades as $c)
                                <option value="{{ $c->cidade }}">{{ $c->cidade }} - {{ $c->uf }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-grande"><x-icone nome="search" /> Buscar</button>
                </form>

                <div class="hero-numeros">
                    <div><strong>{{ $totais['medicos'] }}</strong><span>médicos verificados</span></div>
                    <div><strong>{{ $totais['unidades'] }}</strong><span>clínicas e hospitais</span></div>
                    <div><strong>{{ $totais['cidades'] }}</strong><span>{{ $totais['cidades'] === 1 ? 'cidade' : 'cidades' }}</span></div>
                </div>
            </div>

            <div class="hero-carousel" id="heroCarousel">
                <div class="carousel-track" id="carouselTrack">
                    <div class="carousel-slide">
                        <img src="{{ asset('imgs/teste.jpg') }}" alt="Médica atendendo paciente em consulta">
                        <span class="carousel-caption">Atendimento humanizado</span>
                    </div>
                    <div class="carousel-slide">
                        <img src="{{ asset('imgs/teste.jpg') }}" alt="Equipe médica especializada">
                        <span class="carousel-caption">Equipe especializada</span>
                    </div>
                    <div class="carousel-slide">
                        <img src="{{ asset('imgs/teste.jpg') }}" alt="Clínicas e hospitais perto de você">
                        <span class="carousel-caption">Clínicas e hospitais perto de você</span>
                    </div>
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
                <p>Agendar sua consulta pelo FacilMed é simples.</p>
            </div>

            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Encontre um médico</h3>
                    <p>Pesquise por especialidade, cidade ou convênio.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Escolha o horário</h3>
                    <p>Veja só os horários que estão livres de verdade na agenda do médico.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Agende sua consulta</h3>
                    <p>Confirme, pague particular ou use a carteirinha do seu convênio.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ========================= ESPECIALIDADES ========================= --}}
    <section class="specialties" id="especialidades">
        <div class="container">
            <div class="section-header">
                <h2>Especialidades</h2>
                <a href="{{ route('busca.index') }}" class="view-all">Ver todos os médicos</a>
            </div>

            <div class="specialties-wrapper">
                <button class="arrow-button left" id="prevSpecialty" type="button" aria-label="Voltar">‹</button>

                <div class="specialties-list" id="specialtiesList">
                    @foreach ($especialidades as $esp)
                        <a href="{{ route('busca.index', ['especialidade' => $esp->slug]) }}" class="specialty-card">
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
                            <div class="specialty-photo" style="background-color: {{ \App\Support\Formatador::corAvatar($i) }};">
                                {{ \App\Support\Formatador::iniciais($medico->user->name) }}
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

    {{-- ========================= CHAMADA PARA PROFISSIONAIS ========================= --}}
    <section class="chamada">
        <div class="container">
            <div>
                <h2>É médico, clínica ou hospital?</h2>
                <p>Cadastre-se, informe onde e quando atende e receba agendamentos pelo FacilMed.</p>
            </div>
            <div class="acoes">
                <a href="{{ route('cadastro.medico') }}" class="btn btn-outline btn-grande">Sou médico</a>
                <a href="{{ route('cadastro.clinica') }}" class="btn btn-azul btn-grande">Sou clínica ou hospital</a>
            </div>
        </div>
    </section>

@endsection

@push('scripts')
    <script src="{{ asset('javas/home.js') }}"></script>
@endpush
