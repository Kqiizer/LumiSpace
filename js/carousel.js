document.querySelectorAll(".carousel-container").forEach(carousel => {
  const track = carousel.querySelector(".carousel-track");
  const prev = carousel.querySelector(".prev");
  const next = carousel.querySelector(".next");
  let index = 0;

  const moveCarousel = () => {
    track.style.transform = `translateX(${-index * 270}px)`; // ancho tarjeta + gap
  };

  next.addEventListener("click", () => {
    if (index < track.children.length - 1) index++;
    moveCarousel();
  });

  prev.addEventListener("click", () => {
    if (index > 0) index--;
    moveCarousel();
  });
});
