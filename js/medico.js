// ==========================================
// PAINEL MÉDICO
// ==========================================

console.log("Painel médico carregado.");


// ==========================================
// DATA E HORA
// ==========================================

function atualizarHora() {

    const agora = new Date();

    console.log(
        "Horário atual:",
        agora.toLocaleTimeString("pt-BR")
    );

}


setInterval(
    atualizarHora,
    1000
);