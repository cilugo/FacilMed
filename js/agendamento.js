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
    const dataConsulta = document.getElementById("dataConsulta");
    const horario = document.getElementById("horario");
    const tipoConsulta = document.getElementById("tipoConsulta");
    const tipoAtendimento = document.getElementById("tipoAtendimento");
    const blocoConvenio = document.getElementById("blocoConvenio");
    const convenio = document.getElementById("convenio");
    const plano = document.getElementById("plano");
    const valor = document.getElementById("valor");
    const observacoes = document.getElementById("observacoes");
    const resumo = document.getElementById("resumo");

    const avisoSelecioneMedico = document.getElementById("avisoSelecioneMedico");
    const blocoCalendario = document.getElementById("blocoCalendario");
    const calendarioMesAno = document.getElementById("calendarioMesAno");
    const calendarioGrade = document.getElementById("calendarioGrade");
    const horariosGrade = document.getElementById("horariosGrade");
    const botaoMesAnterior = document.getElementById("mesAnterior");
    const botaoMesProximo = document.getElementById("mesProximo");

    // planosPorConvenio e diasDisponiveisPorMedico vêm de um <script> inline
    // gerado pelo PHP na página.
    const planos = (typeof planosPorConvenio !== "undefined") ? planosPorConvenio : {};
    const diasPorMedico = (typeof diasDisponiveisPorMedico !== "undefined") ? diasDisponiveisPorMedico : {};

    const NOMES_DIA_SEMANA = ["domingo", "segunda", "terca", "quarta", "quinta", "sexta", "sabado"];
    const NOMES_MES = [
        "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho",
        "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"
    ];
    const MESES_A_FRENTE_LIMITE = 3;

    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);

    let mesExibido = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
    let diaSelecionado = null; // string "YYYY-MM-DD"

    //===========================
    // FORMATA DATA COMO YYYY-MM-DD (sem depender de fuso horário)
    //===========================

    function formatarData(d) {
        const ano = d.getFullYear();
        const mes = String(d.getMonth() + 1).padStart(2, "0");
        const dia = String(d.getDate()).padStart(2, "0");
        return `${ano}-${mes}-${dia}`;
    }

    //===========================
    // CALENDÁRIO
    //===========================

    function medicoTemDiaDisponivel(diaSemanaIndex) {
        const medicoId = medico.value;
        if (!medicoId) return false;
        const dias = diasPorMedico[medicoId] || [];
        return dias.includes(NOMES_DIA_SEMANA[diaSemanaIndex]);
    }

    function renderizarCalendario() {
        calendarioMesAno.textContent = `${NOMES_MES[mesExibido.getMonth()]} de ${mesExibido.getFullYear()}`;
        calendarioGrade.innerHTML = "";

        const primeiroDiaSemana = new Date(mesExibido.getFullYear(), mesExibido.getMonth(), 1).getDay();
        const totalDiasMes = new Date(mesExibido.getFullYear(), mesExibido.getMonth() + 1, 0).getDate();

        for (let i = 0; i < primeiroDiaSemana; i++) {
            const vazio = document.createElement("span");
            vazio.className = "calendario-dia calendario-dia-vazio";
            calendarioGrade.appendChild(vazio);
        }

        for (let dia = 1; dia <= totalDiasMes; dia++) {
            const dataDia = new Date(mesExibido.getFullYear(), mesExibido.getMonth(), dia);
            const dataStr = formatarData(dataDia);
            const ehPassado = dataDia < hoje;
            const disponivel = !ehPassado && medicoTemDiaDisponivel(dataDia.getDay());

            const botaoDia = document.createElement("button");
            botaoDia.type = "button";
            botaoDia.textContent = String(dia);
            botaoDia.className = "calendario-dia";

            if (!disponivel) {
                botaoDia.classList.add("calendario-dia-desabilitado");
                botaoDia.disabled = true;
            } else {
                botaoDia.addEventListener("click", function () {
                    selecionarDia(dataStr, botaoDia);
                });
            }

            if (dataStr === diaSelecionado) {
                botaoDia.classList.add("calendario-dia-selecionado");
            }

            calendarioGrade.appendChild(botaoDia);
        }

        // Não deixa navegar para meses anteriores ao atual
        const primeiroMesPermitido = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
        botaoMesAnterior.disabled = mesExibido <= primeiroMesPermitido;

        const ultimoMesPermitido = new Date(hoje.getFullYear(), hoje.getMonth() + MESES_A_FRENTE_LIMITE, 1);
        botaoMesProximo.disabled = mesExibido >= ultimoMesPermitido;
    }

    function selecionarDia(dataStr, botaoClicado) {
        diaSelecionado = dataStr;
        dataConsulta.value = dataStr;
        horario.value = "";

        document.querySelectorAll(".calendario-dia-selecionado").forEach(function (el) {
            el.classList.remove("calendario-dia-selecionado");
        });
        botaoClicado.classList.add("calendario-dia-selecionado");

        carregarHorarios(dataStr);
        atualizarResumo();
    }

    function carregarHorarios(dataStr) {
        horariosGrade.innerHTML = '<p class="aviso-calendario">Carregando horários...</p>';

        const url = `../php/horariosdisponiveis.php?medico_id=${encodeURIComponent(medico.value)}&data=${encodeURIComponent(dataStr)}`;

        fetch(url)
            .then(function (resposta) { return resposta.json(); })
            .then(function (dados) {
                horariosGrade.innerHTML = "";

                if (!dados.horarios || dados.horarios.length === 0) {
                    horariosGrade.innerHTML = '<p class="aviso-calendario">Nenhum horário disponível nesse dia.</p>';
                    return;
                }

                dados.horarios.forEach(function (h) {
                    const botaoHorario = document.createElement("button");
                    botaoHorario.type = "button";
                    botaoHorario.textContent = h;
                    botaoHorario.className = "horario-slot";
                    botaoHorario.addEventListener("click", function () {
                        horario.value = h;
                        document.querySelectorAll(".horario-slot-selecionado").forEach(function (el) {
                            el.classList.remove("horario-slot-selecionado");
                        });
                        botaoHorario.classList.add("horario-slot-selecionado");
                        atualizarResumo();
                    });
                    horariosGrade.appendChild(botaoHorario);
                });
            })
            .catch(function () {
                horariosGrade.innerHTML = '<p class="aviso-calendario">Não foi possível carregar os horários. Tente novamente.</p>';
            });
    }

    function reiniciarCalendario() {
        diaSelecionado = null;
        dataConsulta.value = "";
        horario.value = "";
        mesExibido = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
        horariosGrade.innerHTML = '<p class="aviso-calendario">Escolha um dia no calendário acima.</p>';
        renderizarCalendario();
    }

    medico.addEventListener("change", function () {
        if (medico.value) {
            avisoSelecioneMedico.style.display = "none";
            blocoCalendario.style.display = "";
        } else {
            avisoSelecioneMedico.style.display = "";
            blocoCalendario.style.display = "none";
        }
        reiniciarCalendario();
        atualizarResumo();
    });

    botaoMesAnterior.addEventListener("click", function () {
        mesExibido = new Date(mesExibido.getFullYear(), mesExibido.getMonth() - 1, 1);
        renderizarCalendario();
    });

    botaoMesProximo.addEventListener("click", function () {
        mesExibido = new Date(mesExibido.getFullYear(), mesExibido.getMonth() + 1, 1);
        renderizarCalendario();
    });

    renderizarCalendario();

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
            "<br><strong>Data:</strong> " + (dataConsulta.value || "—") +
            "<br><strong>Horário:</strong> " + (horario.value || "—") +
            "<br><strong>Modalidade:</strong> " + tipoConsulta.value +
            "<br><strong>Forma de atendimento:</strong> " + tipoAtendimento.value +
            "<br><strong>Valor:</strong> R$ " + (valor.value || "0.00");
    }

    local.addEventListener("change", atualizarResumo);
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

        if(dataConsulta.value === ""){
            alert("Selecione um dia no calendário.");
            e.preventDefault();
            return;
        }

        if(horario.value === ""){
            alert("Selecione um horário.");
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
