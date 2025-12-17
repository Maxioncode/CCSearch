// ===== BOOK GRID SCROLL =====
const bookGrid = document.querySelector('.book-grid');
let position = 0;
const cardWidth = 195; // width + gap

document.getElementById('nextBtn').addEventListener('click', () => {
  const maxScroll = -(bookGrid.scrollWidth - bookGrid.parentElement.offsetWidth);
  position -= cardWidth * 2; // move 2 cards per click
  if (position < maxScroll) position = maxScroll;
  bookGrid.style.transform = `translateX(${position}px)`;
});

document.getElementById('prevBtn').addEventListener('click', () => {
  position += cardWidth * 2;
  if (position > 0) position = 0;
  bookGrid.style.transform = `translateX(${position}px)`;
});

