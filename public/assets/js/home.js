const navLinks = document.querySelector("#navLinks");
const btnMenu = document.querySelector("#btnMenu");

btnMenu.addEventListener("click", () => {
	btnMenu.firstElementChild.classList.toggle("hidden");
	btnMenu.lastElementChild.classList.toggle("hidden");
	navLinks.classList.toggle("right-[0%]");
});
