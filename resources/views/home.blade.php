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
                {{-- 30/09: a proposta do site mudou - não tem mais agendamento; o
                     foco é achar hospitais e clínicas perto. --}}
                <h1>Encontre hospitais e clínicas<br>perto de você!</h1>
                <p>Escolha a especialidade e veja os lugares mais próximos de você. Confira a nota de outros pacientes e se o local atende pelo seu convênio ou particular.</p>

                {{-- 30/09: só dois campos. Sem cadastro: especialidade e cidade.
                     Logado: especialidade e CEP (BuscaController@locais descobre a
                     cidade do CEP em config/localizacao.php). Os dois levam para
                     "Locais perto de você", do mais perto para o mais longe. --}}
                <form method="GET" action="{{ route('busca.locais') }}" class="busca-caixa" role="search">
                    <div class="busca-campo">
                        <label for="h-especialidade">Especialidade</label>
                        <select id="h-especialidade" name="especialidade">
                            <option value="">Escolha a especialidade</option>
                            @foreach ($especialidades as $esp)
                                <option value="{{ $esp->slug }}">{{ $esp->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    @auth
                        <div class="busca-campo">
                            <label for="h-cep">Seu CEP</label>
                            <input id="h-cep" name="cep" type="text" inputmode="numeric" autocomplete="postal-code"
                                   placeholder="00000-000" maxlength="9" required>
                        </div>
                    @else
                        <div class="busca-campo">
                            <label for="h-cidade">Cidade</label>
                            <select id="h-cidade" name="cidade">
                                <option value="">Escolha a sua cidade</option>
                                @foreach ($cidades as $c)
                                    <option value="{{ $c->cidade }}">{{ $c->cidade }} - {{ $c->uf }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endauth
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
                        ['si4.jpeg', 'Tudo em poucos cliques'],
                        ['si5.jpeg', 'Encontre a especialidade certa'],
                        ['si6.jpeg', 'Use seu convênio ou particular'],
                        ['si7.jpeg', 'Veja a nota de outros pacientes'],
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
                <p>Achar onde se consultar perto de você leva só três passos.</p>
            </div>

            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Diga o que você precisa</h3>
                    <p>Escolha a especialidade e a cidade onde você mora. Se já tem cadastro, é só colocar o seu CEP.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Veja quem está perto</h3>
                    <p>Mostramos médicos, clínicas e hospitais do mais perto para o mais longe, com a nota dada por outros pacientes.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Escolha o melhor para você</h3>
                    <p>Compare a distância e a nota de cada lugar e veja se ele aceita o seu convênio ou atende particular.</p>
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

    {{-- ========================= CLÍNICAS E HOSPITAIS EM DESTAQUE ========================= --}}
    {{-- 30/09: no lugar da vitrine fixa do protótipo. Vem do banco
         (HomeController::locaisDestaque), no mesmo card dos médicos: foto da
         fachada (locais.foto) e a nota. O id fica, por causa do menu do topo. --}}
    @if ($locaisDestaque->isNotEmpty())
        <section class="how-it-works" id="hospitais-clinicas">
            <div class="container">
                <div class="section-header">
                    <h2>Clínicas e hospitais bem avaliados</h2>
                    <a href="{{ route('busca.locais') }}" class="view-all">Ver todos</a>
                </div>

                <div class="specialties-list">
                    @foreach ($locaisDestaque as $item)
                        <a href="{{ route('publico.local', $item->local) }}" class="specialty-card" style="min-width: 200px;">
                            {{-- Sem foto (ou se o arquivo não carregar), fica o ícone de prédio --}}
                            <div class="specialty-photo local-foto">
                                <x-icone nome="building" />
                                @if ($item->local->foto)
                                    <img src="{{ asset($item->local->foto) }}" alt="" onerror="this.remove()">
                                @endif
                            </div>
                            <div class="specialty-info">
                                <span class="nome-medico">{{ $item->local->clinica?->nome_fantasia ?? $item->local->nome }}</span>
                                <span class="nome-especialidade">{{ $item->local->tipo === 'hospital' ? 'Hospital' : 'Clínica' }} · {{ $item->local->cidade }}</span>
                                @if ($item->nota)
                                    <span class="medico-nota"><x-icone nome="star" /> {{ number_format((float) $item->nota->media, 1, ',', '') }}</span>
                                @else
                                    <span class="nome-especialidade">Sem avaliações ainda</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
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
                {{-- 29/09: o médico não se cadastra sozinho; a clínica cadastra os médicos dela.
                     30/09: sem agendamento na proposta nova - o texto não promete mais isso. --}}
                <p>Cadastre a sua clínica ou hospital e os seus médicos, e apareça para os pacientes da sua região.</p>
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
