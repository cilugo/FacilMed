const carouselTrack = document.getElementById("carouselTrack");
const carouselDotsContainer = document.getElementById("carouselDots");

if (carouselTrack && carouselDotsContainer) {

    const slidesOriginais = carouselTrack.querySelectorAll(".carousel-slide");

    let indiceAtual = 0;

    // ==========================================
    // CRIA UMA CÓPIA DA PRIMEIRA IMAGEM
    // ==========================================

    const primeiraCopia = slidesOriginais[0].cloneNode(true);
    carouselTrack.appendChild(primeiraCopia);

    const slides = carouselTrack.querySelectorAll(".carousel-slide");


    // ==========================================
    // CRIA AS BOLINHAS
    // ==========================================

    slidesOriginais.forEach(function (slide, indice) {

        const dot = document.createElement("button");

        dot.type = "button";
        dot.className = "carousel-dot" + (indice === 0 ? " active" : "");

        dot.addEventListener("click", function () {
            irParaSlide(indice);
        });

        carouselDotsContainer.appendChild(dot);
    });


    const dots = carouselDotsContainer.querySelectorAll(".carousel-dot");


    // ==========================================
    // MUDA O SLIDE
    // ==========================================

    function irParaSlide(indice) {

        indiceAtual = indice;

        carouselTrack.style.transition = "transform 0.5s ease";

        carouselTrack.style.transform =
            "translateX(-" + (indiceAtual * 100) + "%)";


        dots.forEach(function (dot, i) {
            dot.classList.toggle("active", i === indiceAtual);
        });
    }


    // ==========================================
    // PRÓXIMO SLIDE
    // ==========================================

    function proximoSlide() {

        indiceAtual++;

        carouselTrack.style.transition = "transform 0.5s ease";

        carouselTrack.style.transform =
            "translateX(-" + (indiceAtual * 100) + "%)";


        // Atualiza as bolinhas
        dots.forEach(function (dot, i) {
            dot.classList.toggle(
                "active",
                i === (indiceAtual % slidesOriginais.length)
            );
        });


        // Quando chegar na cópia da primeira imagem
        if (indiceAtual === slidesOriginais.length) {

            setTimeout(function () {

                // Remove temporariamente a animação
                carouselTrack.style.transition = "none";

                // Volta para a primeira imagem
                indiceAtual = 0;

                carouselTrack.style.transform = "translateX(0)";

            }, 500);
        }
    }


    // ==========================================
    // AVANÇA A CADA 5 SEGUNDOS
    // ==========================================

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

// manu de navegação //
const menulinks = document.querySelectorAll(".menu-link");

menulinks.forEach(function (link) {

    link.addEventListener("click", function () {

        // Remove o "active" de todos os links
        menulinks.forEach(function (item) {
            item.classList.remove("active");
        });

        // Coloca "active" apenas no link clicado
        this.classList.add("active");
    });

});