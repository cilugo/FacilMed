/*=====================================================
        MÁSCARAS DO SISTEMA - FACILMED
======================================================*/

/*
    Nome
    Permite somente letras e espaços
*/
function somenteLetras(campo){

    campo.addEventListener("input", function(){

        this.value = this.value.replace(/[^A-Za-zÀ-ÿ\s]/g, "");

    });

}


/*
    CPF
    Apenas números
    Máscara automática
*/
function mascaraCPF(campo){

    campo.addEventListener("input", function(){

        let valor = this.value;

        // Remove tudo que não for número
        valor = valor.replace(/\D/g, "");

        // Limite de 11 números
        valor = valor.substring(0,11);

        // Máscara
        valor = valor.replace(/(\d{3})(\d)/, "$1.$2");
        valor = valor.replace(/(\d{3})(\d)/, "$1.$2");
        valor = valor.replace(/(\d{3})(\d{1,2})$/, "$1-$2");

        this.value = valor;

    });

}


/*
    Telefone
*/
function mascaraTelefone(campo){

    campo.addEventListener("input", function(){

        let valor = this.value;

        valor = valor.replace(/\D/g, "");

        valor = valor.substring(0,11);

        valor = valor.replace(/^(\d{2})(\d)/, "($1) $2");

        valor = valor.replace(/(\d{5})(\d)/, "$1-$2");

        this.value = valor;

    });

}


/*
    CRM
*/
function mascaraCRM(campo){

    campo.addEventListener("input", function(){

        this.value = this.value.replace(/\D/g,"");

    });

}