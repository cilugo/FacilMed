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
/*
    Mesma regra do servidor (php/lib/helpers.php): mínimo 8 caracteres,
    máximo 72 (limite real do bcrypt).

    O limite antigo aqui era "> 9", ou seja, qualquer senha com 10
    caracteres ou mais era recusada — o contrário do que se espera de uma
    validação de senha, e em desacordo com o maxlength="32" do formulário.
*/
function validarSenha(senha){

    return senha.length >= 8 && senha.length <= 72;

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