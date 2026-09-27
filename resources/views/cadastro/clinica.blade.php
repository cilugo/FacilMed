@extends('layouts.publico')
@section('titulo', 'Cadastro de clínica ou hospital')
@section('conteudo')
    <section class="fm-painel">
        <header class="fm-painel__topo"><h1 class="fm-painel__titulo"><x-icone nome="building" /> Cadastro de clínica ou hospital</h1></header>

        <p class="fm-dica"><x-icone nome="lightbulb" />
            <span>O CNPJ é <strong>conferido na hora na base simulada do FacilMed</strong> (projeto acadêmico: não consulta a Receita Federal de verdade). CNPJ não encontrado ou baixado não é aceito.</span></p>

        <form method="POST" action="{{ route('cadastro.clinica') }}" class="fm-form fm-form--duas" novalidate style="margin-top:14px">
            @csrf
            @include('cadastro._conta', ['rotuloNome' => 'Nome do responsável pela conta'])

            <p class="fm-secao-form">Empresa</p>
            @include('cadastro._campo', ['nome' => 'cnpj', 'rotulo' => 'CNPJ', 'obrigatorio' => true, 'atributos' => 'inputmode="numeric" maxlength="18" placeholder="00.000.000/0000-00"'])
            @include('cadastro._campo', ['nome' => 'telefone', 'rotulo' => 'Telefone', 'tipo' => 'tel', 'atributos' => 'maxlength="15"'])
            @include('cadastro._campo', ['nome' => 'razao_social', 'rotulo' => 'Razão social', 'obrigatorio' => true, 'atributos' => 'maxlength="150"'])
            @include('cadastro._campo', ['nome' => 'nome_fantasia', 'rotulo' => 'Nome fantasia', 'obrigatorio' => true, 'atributos' => 'maxlength="150"'])
            @include('cadastro._campo', ['nome' => 'descricao', 'rotulo' => 'Descrição', 'tipo' => 'textarea', 'largo' => true, 'atributos' => 'maxlength="2000"'])

            <p class="fm-secao-form">Primeira unidade</p>
            @include('cadastro._campo', ['nome' => 'unidade_nome', 'rotulo' => 'Nome da unidade', 'obrigatorio' => true, 'atributos' => 'maxlength="150" placeholder="Ex.: Unidade Centro"'])
            <div class="fm-campo {{ $errors->has('unidade_tipo') ? 'fm-campo--erro' : '' }}">
                <label for="c-unidade_tipo">Tipo *</label>
                <select id="c-unidade_tipo" name="unidade_tipo" required>
                    <option value="clinica" @selected(old('unidade_tipo', 'clinica') === 'clinica')>Clínica</option>
                    <option value="hospital" @selected(old('unidade_tipo') === 'hospital')>Hospital</option>
                </select>
                @error('unidade_tipo') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>
            @include('cadastro._campo', ['nome' => 'unidade_cep', 'rotulo' => 'CEP', 'obrigatorio' => true, 'atributos' => 'inputmode="numeric" maxlength="9"'])
            @include('cadastro._campo', ['nome' => 'unidade_endereco', 'rotulo' => 'Endereço', 'obrigatorio' => true, 'atributos' => 'maxlength="255"'])
            @include('cadastro._campo', ['nome' => 'unidade_numero', 'rotulo' => 'Número', 'obrigatorio' => true, 'atributos' => 'maxlength="20"'])
            @include('cadastro._campo', ['nome' => 'unidade_complemento', 'rotulo' => 'Complemento', 'atributos' => 'maxlength="100"'])
            @include('cadastro._campo', ['nome' => 'unidade_bairro', 'rotulo' => 'Bairro', 'obrigatorio' => true, 'atributos' => 'maxlength="100"'])
            @include('cadastro._campo', ['nome' => 'unidade_cidade', 'rotulo' => 'Cidade', 'obrigatorio' => true, 'atributos' => 'maxlength="100"'])
            @include('cadastro._campo', ['nome' => 'unidade_uf', 'rotulo' => 'UF', 'obrigatorio' => true, 'atributos' => 'maxlength="2" placeholder="SP"'])

            <div class="fm-form__acoes"><button type="submit" class="fm-botao">Criar conta</button></div>
        </form>
    </section>
@endsection
