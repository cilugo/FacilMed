@extends('layouts.publico')
@section('titulo', 'Cadastro de médico')
@section('conteudo')
    <section class="fm-painel">
        <header class="fm-painel__topo"><h1 class="fm-painel__titulo"><x-icone nome="doctors" /> Cadastro de médico</h1></header>

        <p class="fm-dica"><x-icone nome="lightbulb" />
            <span>O CRM é <strong>conferido na hora na base simulada do FacilMed</strong> (o FacilMed é um projeto acadêmico e não consulta o CFM de verdade). CRM não encontrado, suspenso ou cassado não é aceito.</span></p>

        <form method="POST" action="{{ route('cadastro.medico') }}" class="fm-form fm-form--duas" novalidate style="margin-top:14px">
            @csrf
            @include('cadastro._conta')

            <p class="fm-secao-form">Registro profissional</p>
            @include('cadastro._campo', ['nome' => 'cpf', 'rotulo' => 'CPF', 'obrigatorio' => true, 'atributos' => 'inputmode="numeric" maxlength="14" placeholder="000.000.000-00"'])
            @include('cadastro._campo', ['nome' => 'crm', 'rotulo' => 'Número do CRM', 'obrigatorio' => true, 'atributos' => 'inputmode="numeric" maxlength="10"'])
            <div class="fm-campo {{ $errors->has('uf') ? 'fm-campo--erro' : '' }}">
                <label for="c-uf">Estado do CRM *</label>
                <select id="c-uf" name="uf" required>
                    <option value="">Escolha</option>
                    @foreach (['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                        <option @selected(old('uf', 'SP') === $uf)>{{ $uf }}</option>
                    @endforeach
                </select>
                @error('uf') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>
            @include('cadastro._campo', ['nome' => 'anos_atuacao', 'rotulo' => 'Anos de atuação', 'tipo' => 'number', 'atributos' => 'min="0" max="70"'])

            <div class="fm-campo fm-campo--largo {{ $errors->has('especialidades') ? 'fm-campo--erro' : '' }}">
                <label>Especialidades * <small>(a primeira marcada vira a principal)</small></label>
                <div class="fm-checks">
                    @foreach ($especialidades as $e)
                        <label><input type="checkbox" name="especialidades[]" value="{{ $e->id }}" @checked(in_array($e->id, old('especialidades', [])))> {{ $e->nome }}</label>
                    @endforeach
                </div>
                @error('especialidades') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            @include('cadastro._campo', ['nome' => 'telefone_profissional', 'rotulo' => 'Telefone profissional', 'tipo' => 'tel', 'atributos' => 'maxlength="15"'])
            @include('cadastro._campo', ['nome' => 'bio', 'rotulo' => 'Sobre você', 'tipo' => 'textarea', 'largo' => true, 'atributos' => 'maxlength="1000"'])

            <div class="fm-form__acoes"><button type="submit" class="fm-botao">Criar conta</button></div>
        </form>
    </section>
@endsection
