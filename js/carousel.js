// === Carrusel tipo Cards ===
const cardsTrack = document.getElementById("carouselTrack");
let cardIndex = 0;

function autoSlideCards() {
  if (!cardsTrack) return;
  const cards = document.querySelectorAll(".card");
  if (cards.length === 0) return;

  const cardWidth = cards[0].offsetWidth + 20; // ancho + gap
  const visibleCards = Math.floor(cardsTrack.parentElement.offsetWidth / cardWidth);

  cardIndex++;
  if (cardIndex > cards.length - visibleCards) {
    cardIndex = 0; // reinicia
  }

  cardsTrack.style.transform = `translateX(-${cardIndex * cardWidth}px)`;
}

setInterval(autoSlideCards, 3000);
