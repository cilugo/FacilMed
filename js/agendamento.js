/*=========================================================
            AGENDAMENTO DE CONSULTAS
                    FacilMed
=========================================================*/

document.addEventListener("DOMContentLoaded", function () {

    //===========================
    // CAMPOS
    //===========================

    const formulario = document.getElementById("formAgendamento");

    const medico = document.getElementById("medico");

    const especialidade = document.getElementById("especialidade");

    const data = document.getElementById("dataConsulta");

    const horario = document.getElementById("horario");

    const tipoConsulta = document.getElementById("tipoConsulta");

    const observacoes = document.getElementById("observacoes");

    const resumo = document.getElementById("resumo");

    //===========================
    // NÃO PERMITIR DATAS PASSADAS
    //===========================

    const hoje = new Date();

    const ano = hoje.getFullYear();

    const mes = String(hoje.getMonth() + 1).padStart(2, "0");

    const dia = String(hoje.getDate()).padStart(2, "0");

    data.min = `${ano}-${mes}-${dia}`;

    //===========================
    // HORÁRIOS DISPONÍVEIS
    //===========================

    const horariosDisponiveis = [

        "08:00",

        "09:00",

        "10:00",

        "11:00",

        "13:00",

        "14:00",

        "15:00",

        "16:00",

        "17:00"

    ];

    preencherHorarios();

    function preencherHorarios(){

        horario.innerHTML = "";

        let opcaoPadrao = document.createElement("option");

        opcaoPadrao.text = "Selecione";

        opcaoPadrao.value = "";

        horario.appendChild(opcaoPadrao);

        horariosDisponiveis.forEach(function(hora){

            let option = document.createElement("option");

            option.value = hora;

            option.text = hora;

            horario.appendChild(option);

        });

    }

    //===========================
    // GERAR RESUMO
    //===========================

    function atualizarResumo(){

        resumo.innerHTML =

        "<strong>Médico:</strong> " + medico.value +

        "<br><strong>Especialidade:</strong> " + especialidade.value +

        "<br><strong>Data:</strong> " + data.value +

        "<br><strong>Horário:</strong> " + horario.value +

        "<br><strong>Tipo:</strong> " + tipoConsulta.value;

    }

    medico.addEventListener("change", atualizarResumo);

    especialidade.addEventListener("change", atualizarResumo);

    data.addEventListener("change", atualizarResumo);

    horario.addEventListener("change", atualizarResumo);

    tipoConsulta.addEventListener("change", atualizarResumo);

    //===========================
    // ENVIO DO FORMULÁRIO
    //===========================

    formulario.addEventListener("submit", function(e){

        if(medico.value === ""){

            alert("Selecione um médico.");

            medico.focus();

            e.preventDefault();

            return;

        }

        if(especialidade.value === ""){

            alert("Selecione uma especialidade.");

            especialidade.focus();

            e.preventDefault();

            return;

        }

        if(data.value === ""){

            alert("Selecione uma data.");

            data.focus();

            e.preventDefault();

            return;

        }

        if(horario.value === ""){

            alert("Selecione um horário.");

            horario.focus();

            e.preventDefault();

            return;

        }

        if(tipoConsulta.value === ""){

            alert("Selecione o tipo de atendimento.");

            tipoConsulta.focus();

            e.preventDefault();

            return;

        }

        let confirmar = confirm(

            "Deseja confirmar o agendamento?"

        );

        if(!confirmar){

            e.preventDefault();

            return;

        }

    });

});