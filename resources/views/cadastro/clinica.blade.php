{{--
    Cadastro de CLÍNICA ou HOSPITAL. Visual da Mariana (protótipo
    cadpac/cadastro-clinica.html), com os campos que o banco usa.
    Validação: CadastroClinicaRequest (CNPJ conferido na base simulada).

    Diferenças do protótipo, de propósito:
    - Saem CNES, cargo e usuário: não existem no banco, e o login é pelo e-mail.
    - O "Endereço" único virou o cartão da PRIMEIRA UNIDADE (CEP, rua,
      número, bairro, cidade, UF): é o local onde os médicos vão atender.
    - Entra o nome fantasia (é o nome que o usuário vê na busca).
--}}
@extends('layouts.cadastro')

@section('titulo', 'Cadastro de clínica ou hospital')

@section('conteudo')
    <form method="POST" action="{{ route('cadastro.clinica') }}" class="formulario formulario--largo" novalidate>
        @csrf

        <h1>Cadastro de clínica ou hospital</h1>
        <p class="subtitulo">Preencha os dados abaixo para criar sua conta na plataforma. Depois, você cadastra os seus médicos.</p>

        @include('cadastro._avisos')

        <x-cadastro.card titulo="Tipo de estabelecimento" icone="predio" :grade="false">
            @php $tipoEscolhido = old('unidade_tipo', 'clinica'); @endphp
            <div class="tipos {{ $errors->has('unidade_tipo') ? 'tipos--invalido' : '' }}" role="radiogroup" aria-label="Tipo de estabelecimento">
                <label class="tipo">
                    <input type="radio" name="unidade_tipo" value="clinica" @checked($tipoEscolhido === 'clinica')>
                    <span class="tipo__conteudo">
                        @include('cadastro._icone', ['nome' => 'clinica', 'classe' => 'tipo__icone'])
                        <span><strong>Clínica</strong><small>Consultórios, clínicas e centros médicos</small></span>
                    </span>
                </label>
                <label class="tipo">
                    <input type="radio" name="unidade_tipo" value="hospital" @checked($tipoEscolhido === 'hospital')>
                    <span class="tipo__conteudo">
                        @include('cadastro._icone', ['nome' => 'hospital', 'classe' => 'tipo__icone'])
                        <span><strong>Hospital</strong><small>Hospitais, prontos-socorros e similares</small></span>
                    </span>
                </label>
            </div>
            @error('unidade_tipo') <p class="erro">{{ $message }}</p> @enderror
        </x-cadastro.card>

        <x-cadastro.card titulo="Dados da instituição" icone="instituicao">
            @include('cadastro._campo', ['nome' => 'razao_social', 'rotulo' => 'Razão social', 'icone' => 'instituicao', 'obrigatorio' => true,
                'atributos' => 'placeholder="Como está no CNPJ" maxlength="150"'])
            @include('cadastro._campo', ['nome' => 'cnpj', 'rotulo' => 'CNPJ', 'icone' => 'lista', 'obrigatorio' => true, 'mascara' => 'cnpj',
                'ajuda' => 'Conferido na base simulada do PointMed.',
                'atributos' => 'inputmode="numeric" placeholder="00.000.000/0000-00" maxlength="18"'])
            @include('cadastro._campo', ['nome' => 'nome_fantasia', 'rotulo' => 'Nome fantasia', 'icone' => 'predio', 'obrigatorio' => true,
                'ajuda' => 'É o nome que o usuário vê na busca.', 'atributos' => 'placeholder="Ex.: Hospital São Lucas" maxlength="150"'])
            @include('cadastro._campo', ['nome' => 'telefone', 'rotulo' => 'Telefone', 'icone' => 'telefone', 'tipo' => 'tel', 'mascara' => 'telefone',
                'atributos' => 'placeholder="(12) 3333-4444" maxlength="15" autocomplete="tel"'])
            @include('cadastro._campo', ['nome' => 'descricao', 'rotulo' => 'Descrição', 'icone' => 'texto', 'tipo' => 'textarea', 'inteiro' => true,
                'atributos' => 'maxlength="2000" placeholder="Opcional. Aparece na página pública da clínica."'])
        </x-cadastro.card>

        <x-cadastro.card titulo="Primeira unidade" nota="Onde os médicos vão atender. Outras unidades você cadastra depois." icone="local">
            @include('cadastro._campo', ['nome' => 'unidade_nome', 'rotulo' => 'Nome da unidade', 'icone' => 'predio', 'obrigatorio' => true,
                'atributos' => 'placeholder="Ex.: Unidade Centro" maxlength="150"'])
            @include('cadastro._campo', ['nome' => 'unidade_cep', 'rotulo' => 'CEP', 'icone' => 'local', 'obrigatorio' => true, 'mascara' => 'cep',
                'atributos' => 'inputmode="numeric" placeholder="00000-000" maxlength="9" autocomplete="postal-code"'])
            @include('cadastro._campo', ['nome' => 'unidade_endereco', 'rotulo' => 'Endereço', 'icone' => 'local', 'obrigatorio' => true, 'inteiro' => true,
                'atributos' => 'placeholder="Rua, avenida..." maxlength="255" autocomplete="address-line1"'])
            @include('cadastro._campo', ['nome' => 'unidade_numero', 'rotulo' => 'Número', 'obrigatorio' => true, 'atributos' => 'maxlength="20"'])
            @include('cadastro._campo', ['nome' => 'unidade_complemento', 'rotulo' => 'Complemento', 'atributos' => 'maxlength="100" placeholder="Opcional"'])
            @include('cadastro._campo', ['nome' => 'unidade_bairro', 'rotulo' => 'Bairro', 'obrigatorio' => true, 'atributos' => 'maxlength="100"'])
            @include('cadastro._campo', ['nome' => 'unidade_cidade', 'rotulo' => 'Cidade', 'obrigatorio' => true, 'atributos' => 'maxlength="100" autocomplete="address-level2"'])
            @include('cadastro._campo', ['nome' => 'unidade_uf', 'rotulo' => 'UF', 'tipo' => 'select', 'obrigatorio' => true, 'padrao' => 'SP',
                'opcoes' => array_combine(\App\Support\Uf::TODAS, \App\Support\Uf::TODAS)])
        </x-cadastro.card>

        <x-cadastro.card titulo="Responsável e acesso" nota="É com este e-mail e esta senha que a clínica entra no PointMed." icone="cadeado">
            @include('cadastro._campo', ['nome' => 'name', 'rotulo' => 'Nome do responsável', 'icone' => 'pessoa', 'obrigatorio' => true, 'inteiro' => true,
                'atributos' => 'placeholder="Ex.: Maria Silva Santos" autocomplete="name" maxlength="255"'])
            @include('cadastro._campo', ['nome' => 'email', 'rotulo' => 'E-mail', 'icone' => 'email', 'tipo' => 'email', 'obrigatorio' => true, 'inteiro' => true,
                'atributos' => 'placeholder="contato@instituicao.com.br" autocomplete="email"'])
            @include('cadastro._campo', ['nome' => 'password', 'rotulo' => 'Senha', 'icone' => 'cadeado', 'tipo' => 'password', 'obrigatorio' => true,
                'ajuda' => 'Mínimo de 8 caracteres.', 'atributos' => 'placeholder="Digite a senha" autocomplete="new-password" minlength="8" maxlength="72"'])
            @include('cadastro._campo', ['nome' => 'password_confirmation', 'rotulo' => 'Confirmar senha', 'icone' => 'cadeado', 'tipo' => 'password', 'obrigatorio' => true,
                'atributos' => 'placeholder="Confirme a senha" autocomplete="new-password" maxlength="72"'])
        </x-cadastro.card>

        <div class="rodape">
            <p class="entrar">Já tem uma conta? <a href="{{ route('login') }}">Entrar</a></p>
            <button type="submit" class="botao">
                Criar conta
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h16M14 6l6 6-6 6"/></svg>
            </button>
        </div>
    </form>
@endsection
