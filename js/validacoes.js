/*=====================================================
        VALIDAÇÕES DO SISTEMA - FACILMED
======================================================*/


/*
    Validação de Email
*/
function validarEmail(email){

    const regex =

    /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;

    return regex.test(email);

}



/*
    Senha
*/
function validarSenha(senha){

    if(senha.length < 8){

        return false;

    }

    if(senha.length > 9){

        return false;

    }

    return true;

}



/*
    Confirmar senha
*/
function confirmarSenha(senha,confirmacao){

    return senha === confirmacao;

}



/*
    Data de nascimento
*/
function limitarData(campo){

    campo.max = new Date().toISOString().split("T")[0];

}



/*
    Confirmação do cadastro
*/
function confirmarCadastro(){

    return confirm(

        "Deseja realmente realizar o cadastro?"

    );

}



/*
    Mensagem de erro
*/
function mostrarErro(campo,mensagem){

    campo.style.border = "2px solid red";

    alert(mensagem);

}



/*
    Campo correto
*/
function campoCorreto(campo){

    campo.style.border = "2px solid green";

}