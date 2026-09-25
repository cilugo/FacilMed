// ==========================================
// CARROSSEL DE FOTOS DO HERO
// ==========================================

const carouselTrack = document.getElementById("carouselTrack");
const carouselDotsContainer = document.getElementById("carouselDots");

if (carouselTrack && carouselDotsContainer) {

    const slides = carouselTrack.querySelectorAll(".carousel-slide");
    let indiceAtual = 0;

    // Cria um botão de bolinha para cada slide
    slides.forEach(function (slide, indice) {
        const dot = document.createElement("button");
        dot.type = "button";
        dot.className = "carousel-dot" + (indice === 0 ? " active" : "");
        dot.addEventListener("click", function () {
            irParaSlide(indice);
        });
        carouselDotsContainer.appendChild(dot);
    });

    const dots = carouselDotsContainer.querySelectorAll(".carousel-dot");

    function irParaSlide(indice) {
        indiceAtual = indice;
        carouselTrack.style.transform = "translateX(-" + (indiceAtual * 100) + "%)";

        dots.forEach(function (dot, i) {
            dot.classList.toggle("active", i === indiceAtual);
        });
    }

    function proximoSlide() {
        irParaSlide((indiceAtual + 1) % slides.length);
    }

    // Avança automaticamente a cada 5 segundos
    setInterval(proximoSlide, 5000);
}


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


//=slider do bgl de hospital=//

const clinicsList = document.getElementById("clinicsList");
const nextClinicButton = document.getElementById("nextClinic");
const prevClinicButton = document.getElementById("prevClinic");

if (clinicsList && nextClinicButton && prevClinicButton) {

    function larguraDoPasso() {
        const card = clinicsList.querySelector(".clinica-card");
        return card ? card.offsetWidth + 16 : clinicsList.clientWidth;
    }

    nextClinicButton.addEventListener("click", function () {
        clinicsList.scrollBy({ left: larguraDoPasso(), behavior: "smooth" });
    });

    prevClinicButton.addEventListener("click", function () {
        clinicsList.scrollBy({ left: -larguraDoPasso(), behavior: "smooth" });
    });
}