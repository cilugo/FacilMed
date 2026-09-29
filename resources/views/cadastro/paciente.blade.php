{{--
    Cadastro de PACIENTE. Visual da Mariana (protótipo cadpac/cadastro-paciente.html),
    com os campos que o banco usa. Validação: CadastroPacienteRequest.

    Diferenças do protótipo, de propósito:
    - Data de nascimento é campo de data do navegador (o servidor já valida
      "no passado"); o texto DD/MM/AAAA precisaria de conversão a mais.
    - Sexo, telefone e nascimento seguem opcionais, como no banco.
    - Entra o cartão de ACESSIBILIDADE, que o protótipo não tinha: dado
      sensível (LGPD art. 11), só gravado com consentimento marcado.
--}}
@extends('layouts.cadastro')

@section('titulo', 'Cadastro de paciente')

@section('conteudo')
    <form method="POST" action="{{ route('cadastro.paciente') }}" class="formulario" novalidate
          x-data="{ deficiencia: {{ old('possui_deficiencia') ? 'true' : 'false' }} }">
        @csrf

        <h1>Crie sua conta</h1>
        <p class="subtitulo">Preencha os dados abaixo para se cadastrar na plataforma.</p>

        @include('cadastro._avisos')

        <x-cadastro.card titulo="Dados pessoais" icone="pessoa">
            @include('cadastro._campo', ['nome' => 'name', 'rotulo' => 'Nome completo', 'icone' => 'pessoa', 'obrigatorio' => true, 'inteiro' => true,
                'atributos' => 'placeholder="Ex.: Maria Silva Santos" autocomplete="name" maxlength="255"'])
            @include('cadastro._campo', ['nome' => 'cpf', 'rotulo' => 'CPF', 'icone' => 'documento', 'obrigatorio' => true, 'mascara' => 'cpf',
                'atributos' => 'inputmode="numeric" placeholder="000.000.000-00" maxlength="14"'])
            @include('cadastro._campo', ['nome' => 'data_nascimento', 'rotulo' => 'Data de nascimento', 'icone' => 'calendario', 'tipo' => 'date',
                'atributos' => 'max="' . now()->subDay()->toDateString() . '" autocomplete="bday"'])
            @include('cadastro._campo', ['nome' => 'sexo', 'rotulo' => 'Sexo', 'tipo' => 'select', 'vazio' => 'Não informar',
                'opcoes' => ['Feminino' => 'Feminino', 'Masculino' => 'Masculino', 'Prefiro nao informar' => 'Prefiro não informar']])
            @include('cadastro._campo', ['nome' => 'telefone', 'rotulo' => 'Celular', 'icone' => 'telefone', 'tipo' => 'tel', 'mascara' => 'telefone',
                'atributos' => 'placeholder="(12) 99999-9999" maxlength="15" autocomplete="tel"'])
            @include('cadastro._campo', ['nome' => 'email', 'rotulo' => 'E-mail', 'icone' => 'email', 'tipo' => 'email', 'obrigatorio' => true, 'inteiro' => true,
                'atributos' => 'placeholder="exemplo@email.com" autocomplete="email"'])
            @include('cadastro._campo', ['nome' => 'password', 'rotulo' => 'Senha', 'icone' => 'cadeado', 'tipo' => 'password', 'obrigatorio' => true,
                'ajuda' => 'Mínimo de 8 caracteres.', 'atributos' => 'placeholder="Digite sua senha" autocomplete="new-password" minlength="8" maxlength="72"'])
            @include('cadastro._campo', ['nome' => 'password_confirmation', 'rotulo' => 'Confirmar senha', 'icone' => 'cadeado', 'tipo' => 'password', 'obrigatorio' => true,
                'atributos' => 'placeholder="Confirme sua senha" autocomplete="new-password" maxlength="72"'])
        </x-cadastro.card>

        <x-cadastro.card titulo="Acessibilidade" nota="Opcional" icone="maos" :grade="false">
            <label class="marcar marcar--forte">
                <input type="checkbox" name="possui_deficiencia" value="1" x-model="deficiencia" @checked(old('possui_deficiencia'))>
                <span>Preciso de algum recurso de acessibilidade no atendimento</span>
            </label>

            <template x-if="deficiencia">
                <div style="margin-top: 16px">
                    @include('cadastro._campo', ['nome' => 'descricao_deficiencia', 'rotulo' => 'Do que você precisa?', 'tipo' => 'textarea', 'icone' => 'texto',
                        'atributos' => 'maxlength="1000" placeholder="Ex.: uso cadeira de rodas; preciso de intérprete de Libras."'])
                    <label class="marcar">
                        <input type="checkbox" name="consentimento_acessibilidade" value="1" @checked(old('consentimento_acessibilidade'))>
                        <span>Autorizo o FacilMed a guardar essa informação e mostrá-la só aos profissionais com quem eu tiver consulta.</span>
                    </label>
                    @error('consentimento_acessibilidade') <p class="erro">{{ $message }}</p> @enderror
                </div>
            </template>
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
