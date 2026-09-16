// ==========================================
// BUSCA
// ==========================================

const searchForm = document.getElementById("searchForm");

searchForm.addEventListener("submit", function (event) {

    event.preventDefault();

    const search = document.getElementById("search").value.trim();
    const location = document.getElementById("location").value.trim();

    if (search === "" && location === "") {

        alert("Digite uma especialidade, médico, clínica ou localização.");

        return;
    }

    // Futuramente:
    // Aqui podemos enviar os dados para uma página
    // de resultados ou para o backend/PHP.

    console.log("Pesquisa:", search);
    console.log("Localização:", location);

    window.location.href =
        "resultados.html?busca=" +
        encodeURIComponent(search) +
        "&localizacao=" +
        encodeURIComponent(location);

});


// ==========================================
// CARROSSEL DE ESPECIALIDADES
// ==========================================

const specialtiesList =
    document.getElementById("specialtiesList");

const nextButton =
    document.getElementById("nextSpecialty");

const prevButton =
    document.getElementById("prevSpecialty");


nextButton.addEventListener("click", function () {

    specialtiesList.scrollBy({
        left: 350,
        behavior: "smooth"
    });

});


prevButton.addEventListener("click", function () {

    specialtiesList.scrollBy({
        left: -350,
        behavior: "smooth"
    });

});