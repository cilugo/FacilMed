/*
|--------------------------------------------------------------------------
| Cronômetro da recuperação de senha
|--------------------------------------------------------------------------
*/

const cronometro =
    document.getElementById("cronometro");


const botaoVerificar =
    document.getElementById("botaoVerificar");


const mensagemExpiracao =
    document.getElementById(
        "mensagemExpiracao"
    );


function atualizarCronometro(){

    /*
     * Obtém o horário atual
     */

    const agora = new Date();


    /*
     * Calcula quanto tempo falta
     */

    const diferenca =
        dataExpiracao - agora;


    /*
     * Se o tempo acabou
     */

    if(diferenca <= 0){

        cronometro.textContent =
            "00:00";


        mensagemExpiracao.textContent =
            "Este código expirou. Solicite um novo código.";


        botaoVerificar.disabled =
            true;


        botaoVerificar.textContent =
            "Código expirado";


        return;

    }


    /*
     * Converte milissegundos
     * para segundos
     */

    const segundosTotais =
        Math.floor(
            diferenca / 1000
        );


    /*
     * Calcula minutos
     */

    const minutos =
        Math.floor(
            segundosTotais / 60
        );


    /*
     * Calcula segundos restantes
     */

    const segundos =
        segundosTotais % 60;


    /*
     * Adiciona zero à esquerda
     */

    const minutosFormatados =
        String(minutos)
        .padStart(2, "0");


    const segundosFormatados =
        String(segundos)
        .padStart(2, "0");


    /*
     * Mostra no navegador
     */

    cronometro.textContent =
        `${minutosFormatados}:${segundosFormatados}`;

}


/*
|--------------------------------------------------------------------------
| Atualiza a cada segundo
|--------------------------------------------------------------------------
*/

atualizarCronometro();


const intervalo =
    setInterval(
        atualizarCronometro,
        1000
    );