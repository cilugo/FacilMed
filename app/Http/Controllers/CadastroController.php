<?php

namespace App\Http\Controllers;

use App\Http\Requests\CadastroClinicaRequest;
use App\Http\Requests\CadastroPacienteRequest;
use App\Models\Clinica;
use App\Models\HorarioFuncionamento;
use App\Models\Local;
use App\Models\Paciente;
use App\Models\PacienteAcessibilidade;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CadastroController extends Controller
{
    /**
     * Cadastro de PACIENTE e de CLINICA/HOSPITAL.
     *
     * 29/09/2026: o autocadastro de medico saiu (plano do grupo de 28/09).
     * O medico entra pela clinica - Clinica\MedicoController@salvar - que
     * confere o CRM na mesma base simulada e cria a conta com senha
     * temporaria. O endereco /cadastro/medico so redireciona (routes/web.php).
     *
     * TODA validacao mora em FormRequest - nunca so no JavaScript.
     * O prototipo antigo validava senha apenas no JS: quem desabilitava
     * o JS ou mandava POST direto cadastrava com a senha "1".
     *
     * Senha: minimo 8, maximo 72 (limite do bcrypt). NAO existe limite
     * de 10 caracteres - limitar o maximo enfraquece sem ganho nenhum.
     *
     * O CNPJ e conferido na BASE SIMULADA dentro do FormRequest (Rule
     * CnpjNaBaseSimulada): se chegou aqui, ja bateu.
     *
     * Transacao em todos: user + perfil nascem juntos. Sem isso, um erro
     * no meio deixa conta orfa (user sem paciente) que trava o login.
     */
    public function escolher()
    {
        return view('cadastro.escolher');
    }

    public function formPaciente()
    {
        return view('cadastro.paciente');
    }

    public function salvarPaciente(CadastroPacienteRequest $request)
    {
        $dados = $request->validated();

        $user = DB::transaction(function () use ($dados) {
            $user = User::create([
                'name'     => $dados['name'],
                'email'    => $dados['email'],
                'password' => $dados['password'], // o cast 'hashed' do User faz o hash
                'tipo'     => User::TIPO_PACIENTE,
                'telefone' => ($dados['telefone'] ?? '') ?: null,
                'status'   => 'ativo',
            ]);

            $paciente = Paciente::create([
                'user_id'         => $user->id,
                'cpf'             => $dados['cpf'],
                'data_nascimento' => $dados['data_nascimento'] ?? null,
                'sexo'            => $dados['sexo'] ?? null,
            ]);

            // Acessibilidade: OPCIONAL, dado sensivel (LGPD art. 11).
            // So grava com consentimento - o FormRequest ja exige.
            if (! empty($dados['possui_deficiencia'])) {
                PacienteAcessibilidade::create([
                    'paciente_id'          => $paciente->id,
                    'possui_deficiencia'   => true,
                    'descricao'            => $dados['descricao_deficiencia'],
                    'consentimento_em'     => now(),
                    'consentimento_versao' => '1.0',
                ]);
            }

            return $user;
        });

        return $this->entrar($user, 'Conta criada! Se tiver plano de saúde, cadastre a carteirinha em "Meu plano".');
    }

    public function formClinica()
    {
        return view('cadastro.clinica');
    }

    /**
     * Clinica OU hospital (unidade_tipo). CNPJ ja conferido na base
     * simulada. A primeira unidade nasce junto, com horario padrao
     * seg-sex 08-18 e sab 08-12, que a clinica ajusta depois.
     */
    public function salvarClinica(CadastroClinicaRequest $request)
    {
        $dados = $request->validated();

        $user = DB::transaction(function () use ($dados) {
            $user = User::create([
                'name'     => $dados['name'],
                'email'    => $dados['email'],
                'password' => $dados['password'],
                'tipo'     => User::TIPO_CLINICA,
                'telefone' => ($dados['telefone'] ?? '') ?: null,
                'status'   => 'ativo',
            ]);

            $clinica = Clinica::create([
                'user_id'       => $user->id,
                'cnpj'          => $dados['cnpj'],
                'razao_social'  => $dados['razao_social'],
                'nome_fantasia' => $dados['nome_fantasia'],
                'descricao'     => $dados['descricao'] ?? null,
                'telefone'      => ($dados['telefone'] ?? '') ?: null,
            ]);

            $local = Local::create([
                'clinica_id'  => $clinica->id,
                'nome'        => $dados['unidade_nome'],
                'tipo'        => $dados['unidade_tipo'],
                'cep'         => $dados['unidade_cep'],
                'endereco'    => $dados['unidade_endereco'],
                'numero'      => $dados['unidade_numero'],
                'complemento' => $dados['unidade_complemento'] ?? null,
                'bairro'      => $dados['unidade_bairro'],
                'cidade'      => $dados['unidade_cidade'],
                'uf'          => mb_strtoupper($dados['unidade_uf']),
                'telefone'    => ($dados['telefone'] ?? '') ?: null,
                'ativo'       => true,
            ]);

            foreach (['segunda', 'terca', 'quarta', 'quinta', 'sexta'] as $dia) {
                HorarioFuncionamento::create(['local_id' => $local->id, 'dia_semana' => $dia, 'abre' => '08:00', 'fecha' => '18:00']);
            }
            HorarioFuncionamento::create(['local_id' => $local->id, 'dia_semana' => 'sabado', 'abre' => '08:00', 'fecha' => '12:00']);

            return $user;
        });

        return $this->entrar($user, 'Cadastro concluído! O CNPJ foi conferido na base simulada do FacilMed. Agora cadastre os seus médicos.');
    }

    /** Loga a conta recém-criada e manda para o painel do tipo dela. */
    private function entrar(User $user, string $mensagem)
    {
        event(new Registered($user));
        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->route($user->tipo . '.dashboard')->with('sucesso', $mensagem);
    }
}
