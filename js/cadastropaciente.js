// =====================================================
// CADASTRO DE PACIENTE - FacilMed
// =====================================================

// Aguarda toda a página carregar
document.addEventListener("DOMContentLoaded", function () {

    // ===============================
    // CAMPOS
    // ===============================

    const formulario = document.getElementById("formCadastro");

    const nome = document.getElementById("nome");
    const cpf = document.getElementById("cpf");
    const nascimento = document.getElementById("dataNascimento");
    const telefone = document.getElementById("telefone");
    const email = document.getElementById("email");
    const senha = document.getElementById("senha");
    const confirmarSenha = document.getElementById("confirmarSenha");

    // ===============================
    // DATA MÁXIMA
    // ===============================

    nascimento.max = new Date().toISOString().split("T")[0];

    // ===============================
    // NOME
    // Apenas letras
    // ===============================

    nome.addEventListener("input", function () {

        this.value = this.value.replace(/[^A-Za-zÀ-ÿ\s]/g, "");

    });

    // ===============================
    // CPF
    // ===============================

    cpf.addEventListener("input", function () {

        let valor = this.value;

        // Apenas números
        valor = valor.replace(/\D/g, "");

        // Máximo 11 números
        valor = valor.substring(0,11);

        // Máscara
        valor = valor.replace(/(\d{3})(\d)/, "$1.$2");
        valor = valor.replace(/(\d{3})(\d)/, "$1.$2");
        valor = valor.replace(/(\d{3})(\d{1,2})$/, "$1-$2");

        this.value = valor;

    });

    // ===============================
    // TELEFONE
    // ===============================

    telefone.addEventListener("input", function(){

        let valor = this.value;

        valor = valor.replace(/\D/g,"");

        valor = valor.substring(0,11);

        valor = valor.replace(/^(\d{2})(\d)/,"($1) $2");

        valor = valor.replace(/(\d{5})(\d)/,"$1-$2");

        this.value = valor;

    });

    // ===============================
    // SENHAS
    // ===============================

    confirmarSenha.addEventListener("input", function(){

        if(senha.value === confirmarSenha.value){

            confirmarSenha.style.borderColor = "green";

        }else{

            confirmarSenha.style.borderColor = "red";

        }

    });

    // ===============================
    // ENVIO DO FORMULÁRIO
    // ===============================

    formulario.addEventListener("submit", function(e){

        // Email

        const regexEmail = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;

        if(!regexEmail.test(email.value)){

            alert("Digite um e-mail válido.");

            e.preventDefault();

            return;

        }

        // Senha

        if(senha.value.length < 8 || senha.value.length > 9){

            alert("A senha deve possuir entre 8 e 9 caracteres.");

            e.preventDefault();

            return;

        }

        // Confirmar senha

        if(senha.value !== confirmarSenha.value){

            alert("As senhas não coincidem.");

            e.preventDefault();

            return;

        }

        // Confirmação

        const resposta = confirm("Deseja realmente realizar o cadastro?");

        if(!resposta){

            e.preventDefault();

        }

    });

});