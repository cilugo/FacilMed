{{--
    Página inicial. Dados: HomeController@index.

    Visual e estrutura vêm da home que o grupo fez no protótipo
    (paginas/home.html + css/home.css), agora com dados do banco:
    especialidades reais, médicos verificados e a busca funcionando.

    01/10/2026: o PointMed não agenda mais consultas. Os textos falam em
    encontrar e avaliar locais e médicos; a busca do topo leva para os
    locais perto de você. "Médicos bem avaliados" mostra até 6 médicos do
    banco (com 3 no seeder, apareciam só 3; agora o seeder tem mais).
--}}
@extends('layouts.site')

@section('menu', 'inicio')

@section('conteudo')

    {{-- ========================= HERO ========================= --}}
    <section class="hero">
        <div class="container hero-content">
            <div class="hero-text">
                <h1>Encontre onde se consultar<br>de forma rápida e fácil!</h1>
                <p>Encontre médicos, hospitais e clínicas perto de você.<br>Pesquise por especialidade, veja quem aceita o seu convênio e confira a avaliação de outros usuários.</p>

                <form method="GET" action="{{ route('busca.locais') }}" class="busca-caixa" role="search">
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

                    {{-- 29/09: leva para "Locais perto de você" com a posição do navegador
                         (public/javas/localizacao.js). O navegador pede a permissão. --}}
                    <div class="busca-localizacao">
                        <button type="button" class="busca-localizacao__botao" data-localizacao="{{ route('busca.locais') }}">
                            <x-icone nome="localizar" /> Usar minha localização
                        </button>
                        <span data-localizacao-status class="localizacao-aviso">e ver clínicas e hospitais perto de você</span>
                    </div>
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
                        // 05/10: legendas sobre o que o site faz (achar e comparar), não sobre atendimento
                        ['si1.jpeg', 'Compare antes de escolher'],
                        ['si2.jpeg', 'Médicos de várias especialidades'],
                        ['si3.jpeg', 'Hospitais e clínicas da sua região'],
                        ['si4.jpeg', 'Encontre em poucos cliques'],
                        ['si5.jpeg', 'Encontre a especialidade certa'],
                        ['si6.jpeg', 'Use seu convênio ou particular'],
                        ['si7.jpeg', 'Veja a avaliação de outros usuários'],
                        ['si8.jpeg', 'Clínicas perto de você'],
                        ['si9.jpeg', 'Escolha com a ajuda de quem já foi'],
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
                <p>Encontrar onde se consultar pelo PointMed é simples.</p>
            </div>

            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Busque perto de você</h3>
                    <p>Pesquise por especialidade, cidade ou convênio, ou use a sua localização.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Compare os locais</h3>
                    <p>Veja distância, médicos disponíveis, convênios aceitos, faixa de preço e avaliações.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Avalie depois</h3>
                    <p>Conte como foi: sua nota ajuda outras pessoas a escolher.</p>
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
                                {{ \App\Support\Formatador::iniciais($medico->nome) }}
                                @if ($medico->foto_url)
                                    <img src="{{ $medico->foto_url }}" alt="" onerror="this.remove()">
                                @endif
                            </div>
                            <div class="specialty-info">
                                <span class="nome-medico">{{ $medico->nome }}</span>
                                <span class="nome-especialidade">{{ $medico->especialidades->pluck('nome')->join(' · ') }}</span>
                                @if ($medico->total_avaliacoes > 0)
                                    <span class="medico-nota"><x-icone nome="star" /> {{ number_format((float) $medico->media_avaliacoes, 1, ',', '') }}
                                        <span style="font-weight: 400; color: #666;">({{ $medico->total_avaliacoes }} {{ $medico->total_avaliacoes === 1 ? 'avaliação' : 'avaliações' }})</span></span>
                                @endif
                                {{-- 05/10: o comentário mais recente (comentários são públicos) --}}
                                @if ($r = $medico->avaliacoes->first())
                                    <span class="home-review">“{{ \Illuminate\Support\Str::limit($r->comentario, 70) }}” — {{ \App\Support\Formatador::nomeCurto($r->usuario?->user?->name) }}</span>
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
                <p class="sobre-text">O PointMed é uma plataforma digital que conecta usuários a profissionais de saúde, ajudando a encontrar clínicas, hospitais e médicos perto de você e melhorando o acesso aos serviços de saúde. Surgindo apenas como uma ideia em sala de aula, agora o PointMed está disponível para ajudar você a encontrar o cuidado de saúde que você precisa, quando e onde precisar. Compare locais, veja quem aceita o seu convênio, confira a avaliação de outros usuários e muito mais em um único lugar.</p>
            </div>
        </div>
    </section>

    {{-- ========================= CHAMADA PARA PROFISSIONAIS ========================= --}}
    <section class="chamada">
        <div class="container">
            <div>
                <h2>É clínica ou hospital?</h2>
                {{-- 29/09: o médico não se cadastra sozinho; a clínica cadastra os médicos dela. --}}
                <p>Cadastre a sua clínica ou hospital, depois os seus médicos, e seja encontrado por usuários perto de você.</p>
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
