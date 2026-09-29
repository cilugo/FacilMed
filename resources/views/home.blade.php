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
                <h1>Agende sua consulta<br>de forma rápida e fácil!</h1>
                <p>Encontre médicos, hospitais e clínicas perto de você.<br>Pesquise por especialidade, confira os horários disponíveis e encontre a melhor opção para cuidar da sua saúde.</p>

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
                {{-- Fotos em public/imgs/sliderinicio/. [arquivo, legenda] --}}
                @php
                    $slides = [
                        ['si1.jpeg', 'Atendimento humanizado'],
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
                            {{-- A foto cobre as iniciais; se o arquivo não carregar, o onerror
                                 tira a imagem e as iniciais aparecem no lugar --}}
                            <div class="specialty-photo" style="background-color: {{ \App\Support\Formatador::corAvatar($i) }};">
                                {{ \App\Support\Formatador::iniciais($medico->user->name) }}
                                @if ($medico->foto)
                                    <img src="{{ asset($medico->foto) }}" alt="" onerror="this.remove()">
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
    {{-- Vitrine fixa (nomes fictícios, endereços reais), trazida do protótipo.
         Fotos em public/imgs/sliderhospcli/. --}}
    @php
        $estabelecimentos = [
            ['h1.jpg', 'Hospital Santa Clara', 'Av. Tiradentes, 280 - Centro, Taubaté - SP'],
            ['h2.jpg', 'Clínica Vida Plena', 'Av. Cassiano Ricardo, 319 - Jardim Aquarius, São José dos Campos - SP'],
            ['h3.jpg', 'Hospital Vale Sereno', 'Av. Lineu de Moura, 995 - Urbanova, São José dos Campos - SP'],
            ['h4.jpg', 'Centro Médico Aurora', 'Rua Major Francisco de Paula Elias, 217 - Vila Adyana, São José dos Campos - SP'],
        ];
    @endphp
    <section class="specialties" id="hospitais-clinicas">
        <div class="container">
            <div class="section-header">
                <h2>Hospitais e Clínicas</h2>
                <a href="{{ route('busca.index') }}" class="view-all">Ver todos</a>
            </div>

            <div class="specialties-wrapper">
                <button class="arrow-button left" id="prevClinic" type="button" aria-label="Clínicas anteriores">‹</button>

                <div class="clinicas-list" id="clinicsList">
                    @foreach ($estabelecimentos as [$foto, $nome, $endereco])
                        <div class="clinica-card">
                            <img src="{{ asset('imgs/sliderhospcli/' . $foto) }}" alt="">
                            <div class="clinica-info">
                                <span class="nome-clinica">{{ $nome }}</span>
                                <span class="local-clinica">{{ $endereco }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button class="arrow-button right" id="nextClinic" type="button" aria-label="Próximas clínicas">›</button>
            </div>
        </div>
    </section>

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
@endpush
