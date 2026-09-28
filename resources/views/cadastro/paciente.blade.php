@extends('layouts.publico')
@section('titulo', 'Cadastro de paciente')
@section('conteudo')
    <section class="fm-painel" x-data="{ deficiencia: {{ old('possui_deficiencia') ? 'true' : 'false' }} }">
        <header class="fm-painel__topo"><h1 class="fm-painel__titulo"><x-icone nome="user" /> Cadastro de paciente</h1></header>

        <form method="POST" action="{{ route('cadastro.paciente') }}" class="fm-form fm-form--duas" novalidate>
            @csrf
            @include('cadastro._conta')

            <p class="fm-secao-form">Seus dados</p>
            @include('cadastro._campo', ['nome' => 'cpf', 'rotulo' => 'CPF', 'obrigatorio' => true, 'atributos' => 'inputmode="numeric" maxlength="14" placeholder="000.000.000-00"'])
            @include('cadastro._campo', ['nome' => 'telefone', 'rotulo' => 'Celular', 'tipo' => 'tel', 'atributos' => 'maxlength="15" placeholder="(12) 99999-9999"'])
            @include('cadastro._campo', ['nome' => 'data_nascimento', 'rotulo' => 'Data de nascimento', 'tipo' => 'date'])
            <div class="fm-campo">
                <label for="c-sexo">Sexo</label>
                <select id="c-sexo" name="sexo">
                    <option value="">Não informar</option>
                    @foreach (['Feminino', 'Masculino', 'Prefiro nao informar'] as $op)
                        <option value="{{ $op }}" @selected(old('sexo') === $op)>{{ $op === 'Prefiro nao informar' ? 'Prefiro não informar' : $op }}</option>
                    @endforeach
                </select>
            </div>

            <p class="fm-secao-form">Acessibilidade (opcional)</p>
            <div class="fm-campo fm-campo--largo">
                <label class="fm-marcar fm-marcar--linha"><input type="checkbox" name="possui_deficiencia" value="1" x-model="deficiencia" @checked(old('possui_deficiencia'))> <span>Preciso de algum recurso de acessibilidade no atendimento</span></label>
            </div>
            <template x-if="deficiencia">
                <div class="fm-campo fm-campo--largo" style="display:grid;gap:14px">
                    @include('cadastro._campo', ['nome' => 'descricao_deficiencia', 'rotulo' => 'Do que você precisa?', 'tipo' => 'textarea', 'largo' => true, 'atributos' => 'maxlength="1000"'])
                    <label class="fm-marcar"><input type="checkbox" name="consentimento_acessibilidade" value="1" @checked(old('consentimento_acessibilidade'))>
                        <span>Autorizo o FacilMed a guardar essa informação e mostrá-la só aos profissionais com quem eu tiver consulta.</span></label>
                    @error('consentimento_acessibilidade') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                </div>
            </template>

            <div class="fm-form__acoes"><button type="submit" class="fm-botao">Criar conta</button></div>
        </form>
    </section>
@endsection
