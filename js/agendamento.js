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
    const local = document.getElementById("local");
    const data = document.getElementById("dataConsulta");
    const horario = document.getElementById("horario");
    const tipoConsulta = document.getElementById("tipoConsulta");
    const tipoAtendimento = document.getElementById("tipoAtendimento");
    const blocoConvenio = document.getElementById("blocoConvenio");
    const convenio = document.getElementById("convenio");
    const plano = document.getElementById("plano");
    const valor = document.getElementById("valor");
    const observacoes = document.getElementById("observacoes");
    const resumo = document.getElementById("resumo");

    // planosPorConvenio vem de um <script> inline gerado pelo PHP na página
    const planos = (typeof planosPorConvenio !== "undefined") ? planosPorConvenio : {};

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
        "08:00", "09:00", "10:00", "11:00",
        "13:00", "14:00", "15:00", "16:00", "17:00"
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
    // FORMA DE ATENDIMENTO
    // (mostra/esconde convênio+plano e ajusta o valor)
    //===========================

    tipoAtendimento.addEventListener("change", function(){
        if(tipoAtendimento.value === "convenio"){
            blocoConvenio.style.display = "";
            convenio.required = true;
            plano.required = true;
            valor.readOnly = true;
        } else {
            blocoConvenio.style.display = "none";
            convenio.required = false;
            plano.required = false;
            convenio.value = "";
            preencherPlanos();
            if(tipoAtendimento.value === "SUS"){
                valor.value = "0.00";
                valor.readOnly = true;
            } else {
                // particular: usuário digita o valor livremente
                valor.readOnly = false;
            }
        }
        atualizarResumo();
    });

    //===========================
    // CONVÊNIO -> PLANOS DISPONÍVEIS
    //===========================

    convenio.addEventListener("change", function(){
        preencherPlanos();
        atualizarResumo();
    });

    function preencherPlanos(){
        plano.innerHTML = "";
        const convenioId = convenio.value;
        const lista = planos[convenioId] || [];

        if(!convenioId){
            let opcao = document.createElement("option");
            opcao.value = "";
            opcao.text = "Selecione o convênio primeiro";
            plano.appendChild(opcao);
            return;
        }

        let opcaoPadrao = document.createElement("option");
        opcaoPadrao.value = "";
        opcaoPadrao.text = "Selecione";
        plano.appendChild(opcaoPadrao);

        lista.forEach(function(p){
            let opcao = document.createElement("option");
            opcao.value = p.id;
            opcao.text = p.nome;
            opcao.dataset.valor = p.valor;
            plano.appendChild(opcao);
        });
    }

    plano.addEventListener("change", function(){
        const selecionado = plano.options[plano.selectedIndex];
        if(selecionado && selecionado.dataset.valor !== undefined){
            valor.value = parseFloat(selecionado.dataset.valor).toFixed(2);
        }
        atualizarResumo();
    });

    //===========================
    // GERAR RESUMO
    //===========================

    function atualizarResumo(){
        const nomeMedico = medico.options[medico.selectedIndex] ? medico.options[medico.selectedIndex].text : "";
        const nomeLocal = local.options[local.selectedIndex] ? local.options[local.selectedIndex].text : "";

        resumo.innerHTML =
            "<strong>Médico:</strong> " + nomeMedico +
            "<br><strong>Local:</strong> " + nomeLocal +
            "<br><strong>Data:</strong> " + data.value +
            "<br><strong>Horário:</strong> " + horario.value +
            "<br><strong>Modalidade:</strong> " + tipoConsulta.value +
            "<br><strong>Forma de atendimento:</strong> " + tipoAtendimento.value +
            "<br><strong>Valor:</strong> R$ " + (valor.value || "0.00");
    }

    medico.addEventListener("change", atualizarResumo);
    local.addEventListener("change", atualizarResumo);
    data.addEventListener("change", atualizarResumo);
    horario.addEventListener("change", atualizarResumo);
    tipoConsulta.addEventListener("change", atualizarResumo);
    valor.addEventListener("input", atualizarResumo);

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
            alert("Selecione a modalidade da consulta.");
            tipoConsulta.focus();
            e.preventDefault();
            return;
        }

        if(tipoAtendimento.value === ""){
            alert("Selecione a forma de atendimento.");
            tipoAtendimento.focus();
            e.preventDefault();
            return;
        }

        if(tipoAtendimento.value === "convenio" && (convenio.value === "" || plano.value === "")){
            alert("Selecione o convênio e o plano.");
            e.preventDefault();
            return;
        }

        let confirmar = confirm("Deseja confirmar o agendamento?");

        if(!confirmar){
            e.preventDefault();
            return;
        }

    });

});
