// =====================================================
// CADASTRO DE MÉDICO
// =====================================================

document.addEventListener("DOMContentLoaded", function(){

    const formulario = document.getElementById("formCadastro");

    const nome = document.getElementById("nome");
    const cpf = document.getElementById("cpf");
    const crm = document.getElementById("crm");
    const uf = document.getElementById("uf");
    const telefone = document.getElementById("telefone");
    const email = document.getElementById("email");
    const senha = document.getElementById("senha");
    const confirmarSenha = document.getElementById("confirmarSenha");

    // ===============================
    // Nome
    // ===============================

    nome.addEventListener("input",function(){

        this.value = this.value.replace(/[^A-Za-zÀ-ÿ\s]/g,'');

    });

    // ===============================
    // CPF
    // ===============================

    cpf.addEventListener("input",function(){

        let valor = this.value;

        valor = valor.replace(/\D/g,'');

        valor = valor.substring(0,11);

        valor = valor.replace(/(\d{3})(\d)/,"$1.$2");

        valor = valor.replace(/(\d{3})(\d)/,"$1.$2");

        valor = valor.replace(/(\d{3})(\d{1,2})$/,"$1-$2");

        this.value = valor;

    });

    // ===============================
    // TELEFONE
    // ===============================

    telefone.addEventListener("input",function(){

        let valor = this.value;

        valor = valor.replace(/\D/g,'');

        valor = valor.substring(0,11);

        valor = valor.replace(/^(\d{2})(\d)/,"($1) $2");

        valor = valor.replace(/(\d{5})(\d)/,"$1-$2");

        this.value = valor;

    });

    // ===============================
    // CRM
    // Apenas números
    // ===============================

    crm.addEventListener("input",function(){

        this.value = this.value.replace(/\D/g,'');

    });

    // ===============================
    // Senhas
    // ===============================

    confirmarSenha.addEventListener("input",function(){

        if(senha.value === confirmarSenha.value){

            confirmarSenha.style.borderColor = "green";

        }

        else{

            confirmarSenha.style.borderColor = "red";

        }

    });

    // ===============================
    // Envio
    // ===============================

    formulario.addEventListener("submit",function(e){

        const regexEmail = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;

        if(!regexEmail.test(email.value)){

            alert("Digite um e-mail válido.");

            e.preventDefault();

            return;

        }

        if(senha.value.length < 8 || senha.value.length > 9){

            alert("A senha deve possuir entre 8 e 9 caracteres.");

            e.preventDefault();

            return;

        }

        if(senha.value !== confirmarSenha.value){

            alert("As senhas não coincidem.");

            e.preventDefault();

            return;

        }

        if(crm.value === ""){

            alert("Informe o CRM.");

            e.preventDefault();

            return;

        }

        if(uf.value === ""){

            alert("Selecione a UF do CRM.");

            e.preventDefault();

            return;

        }

        if(!confirm("Deseja realmente realizar o cadastro?")){

            e.preventDefault();

        }

    });

});