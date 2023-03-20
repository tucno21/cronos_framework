// let full = true;
let full = localStorage.getItem("dashboardToggleMenu") === "true";
//preguntar si no existe crearlo y asignarle true
if (localStorage.getItem("dashboardToggleMenu") === null) {
	localStorage.setItem("dashboardToggleMenu", "true");
	full = true;
}

const sidebar = document.getElementById("sidebar");
const titleLogo = document.getElementById("titleLogo");
const singleMenu = document.querySelectorAll(".singleMenu");
const dropdownMenu = document.querySelectorAll(".dropdownMenu");
const sidebarToggleMovil = document.getElementById("sidebarToggleMovil");
const botonuser = document.getElementById("botonuser");
const menulaptop = document.getElementById("menulaptop");

function cambioClassList() {
	sidebar.classList.toggle("w-[14rem]", full);
	sidebar.classList.toggle("sm:w-[4rem]", !full);
	sidebar.classList.toggle("overflow-fix", full);
	// sidebar.classList.toggle("overflow-menu-icon", !full);

	titleLogo.classList.toggle("text-2xl", full);
	titleLogo.classList.toggle("text-sm", !full);
	// titleLogo.classList.toggle("xm:px-2", !full);

	menulaptop.classList.toggle("rotate-180", !full);

	singleMenu.forEach((menu) => {
		menu.classList.toggle("justify-start", full);
		menu.classList.toggle("justify-center", !full);
		menu.lastElementChild.classList.toggle("hidden", !full);
	});

	dropdownMenu.forEach((menu) => {
		menu.firstElementChild.lastElementChild.classList.toggle("hidden", !full);
		menu.lastElementChild.classList.toggle("hidden", !full);
		menu.classList.toggle("justify-between", full);
		menu.classList.toggle("justify-center", !full);

		menu.classList.toggle("menu-comprimido", !full);
		menu.classList.toggle("menu-extendido", full);
	});

	// localStorage.setItem("dashboardToggleMenu", full);
}
cambioClassList();

menulaptop.addEventListener("click", (e) => {
	full = !full;
	localStorage.setItem("dashboardToggleMenu", full);
	cambioClassList();
	removeActiveClass();
	menuComprimido();
});

sidebarToggleMovil.addEventListener("click", () => {
	sidebarToggleMovil.firstElementChild.classList.toggle("hidden");
	sidebarToggleMovil.lastElementChild.classList.toggle("hidden");
	sidebar.classList.toggle("-left-[14rem]");
});

botonuser.addEventListener("click", function () {
	dropdownuser.classList.toggle("hidden");
});

function menuLaptopComprimidoVerDropdownMenu() {
	let activeDropdown = null;

	sidebar.addEventListener("click", (e) => {
		if (e.target.closest(".dropdownMenu") && e.target.closest(".menu-comprimido")) {
			const dropdownMenu = e.target.closest(".dropdownMenu").nextElementSibling;
			if (dropdownMenu === activeDropdown) {
				dropdownMenu.classList.add("hidden");
				dropdownMenu.classList.remove(
					"sm:absolute",
					"top-0",
					"left-14",
					"sm:shadow-md",
					"sm:z-30",
					"sm:bg-gray-900",
					"sm:rounded-md",
					"sm:p-4",
					"border-l",
					"sm:border-none",
					"sm:ml-0",
					"w-28"
				);

				activeDropdown = null;
			} else {
				if (activeDropdown) {
					activeDropdown.classList.add("hidden");
					activeDropdown.classList.remove(
						"sm:absolute",
						"top-0",
						"left-14",
						"sm:shadow-md",
						"sm:z-30",
						"sm:bg-gray-900",
						"sm:rounded-md",
						"sm:p-4",
						"border-l",
						"sm:border-none",
						"sm:ml-0",
						"w-28"
					);
				}

				dropdownMenu.classList.remove("hidden");
				dropdownMenu.classList.add(
					"sm:absolute",
					"top-0",
					"left-14",
					"sm:shadow-md",
					"sm:z-30",
					"sm:bg-gray-900",
					"sm:rounded-md",
					"sm:p-4",
					"border-l",
					"sm:border-none",
					"sm:ml-0",
					"w-28"
				);

				activeDropdown = dropdownMenu;
			}
		} else if (e.target.closest(".dropdownMenu") && e.target.closest(".menu-extendido")) {
			const dropdownMenu = e.target.closest(".dropdownMenu").nextElementSibling;
			if (dropdownMenu === activeDropdown) {
				dropdownMenu.classList.add("hidden");
				dropdownMenu.classList.remove("border-l");
				e.target.closest(".dropdownMenu").lastElementChild.classList.remove("rotate-180");

				activeDropdown = null;
			} else {
				if (activeDropdown) {
					activeDropdown.parentElement.firstElementChild.lastElementChild.classList.remove("rotate-180");
					activeDropdown.classList.add("hidden");
					activeDropdown.classList.remove("border-l");
				}

				e.target.closest(".dropdownMenu").lastElementChild.classList.add("rotate-180");
				dropdownMenu.classList.remove("hidden");
				dropdownMenu.classList.add("border-l");

				activeDropdown = dropdownMenu;
			}
		} else {
			if (activeDropdown) {
				activeDropdown.classList.add("hidden");
				activeDropdown.classList.remove(
					"sm:absolute",
					"top-0",
					"left-20",
					"sm:shadow-md",
					"sm:z-30",
					"sm:bg-gray-900",
					"sm:rounded-md",
					"sm:p-4",
					"border-l",
					"sm:border-none",
					"sm:ml-0",
					"w-28"
				);
				activeDropdown = null;
			}
		}
	});
}

menuLaptopComprimidoVerDropdownMenu();

function tooltipMenuLaptopComprimido() {
	function toggleMenuVisibility(menu) {
		menu.classList.toggle("hidden");
		menu.classList.toggle("block");
		menu.classList.toggle("sm:absolute");
		menu.classList.toggle("-top-2");
		menu.classList.toggle("left-5");
		menu.classList.toggle("sm:border");
		menu.classList.toggle("border-gray-500");
		menu.classList.toggle("sm:text-sm");
		menu.classList.toggle("sm:bg-gray-900");
		menu.classList.toggle("sm:px-2");
		menu.classList.toggle("sm:py-1");
		menu.classList.toggle("sm:rounded-md");
	}
	sidebar.addEventListener("mouseover", (e) => {
		if (e.target.closest(".dropdownMenu") && e.target.closest(".menu-comprimido")) {
			toggleMenuVisibility(e.target.closest(".dropdownMenu").firstElementChild.lastElementChild);
		}

		if (e.target.closest(".singleMenu") && e.target.closest(".justify-center")) {
			toggleMenuVisibility(e.target.closest(".singleMenu").lastElementChild);
		}
	});

	sidebar.addEventListener("mouseout", (e) => {
		if (e.target.closest(".dropdownMenu") && e.target.closest(".menu-comprimido")) {
			toggleMenuVisibility(e.target.closest(".dropdownMenu").firstElementChild.lastElementChild);
		}

		if (e.target.closest(".singleMenu") && e.target.closest(".justify-center")) {
			toggleMenuVisibility(e.target.closest(".singleMenu").lastElementChild);
		}
	});
}
tooltipMenuLaptopComprimido();

// ACTIVAR LINK DEL MENU
// const path = window.location.pathname;
const path = window.location.href;
const activeLink = document.querySelector(`a[href="${path}"]`);

function removeActiveClass() {
	if (activeLink) {
		const menudespeglables = activeLink.closest(".menudespeglables");
		if (menudespeglables) {
			const parentElement = activeLink.parentElement;
			const parentSibling = parentElement.previousElementSibling;
			parentSibling.classList.add("bg-gray-800");
			parentSibling.classList.add("text-white");
			parentElement.classList.toggle("hidden", !full);
			parentElement.classList.toggle("border-l", full);
			activeLink.classList.toggle("text-gray-200", full);
			activeLink.classList.toggle("font-bold", full);
		} else {
			activeLink.classList.add("bg-gray-800");
			activeLink.classList.add("text-white");
		}
	}
}

removeActiveClass();

function menuComprimido() {
	dropdownMenu.forEach((menu) => {
		const menuExtendido = menu.classList.contains("menu-extendido");
		const menuComprimido = menu.classList.contains("menu-comprimido");
		const estaOculto = menu.parentElement.lastElementChild.classList.contains("hidden");

		if (menuComprimido && !estaOculto) {
			menu.parentElement.lastElementChild.classList.toggle("hidden");
			menu.parentElement.lastElementChild.classList.remove("border-l");
		}

		if (menuExtendido && !estaOculto) {
			menu.parentElement.lastElementChild.classList.toggle("hidden");
			menu.parentElement.lastElementChild.classList.remove(
				"sm:absolute",
				"top-0",
				"left-14",
				"sm:shadow-md",
				"sm:z-30",
				"sm:bg-gray-900",
				"sm:rounded-md",
				"sm:p-4",
				"border-l",
				"sm:border-none",
				"sm:ml-0",
				"w-28"
			);
		}
	});
	menuLaptopComprimidoVerDropdownMenu();
}

//FUNCIONES DE LIMPIEZA DE INPUTS
//mostrar mensaje de error
function mensajeErrorInput(input, mensaje) {
	//eliminar mensaje de error si ya existe
	if (input.parentElement.nextElementSibling) {
		input.parentElement.nextElementSibling.remove();
		input.classList.remove("border-red-600");
	}

	//crear mensaje de error
	const mensajeError = document.createElement("p");
	mensajeError.classList.add("text-red-500");

	if (mensaje !== undefined) {
		//agregar mensaje de error
		mensajeError.textContent = mensaje;
		input.parentElement.insertAdjacentElement("afterend", mensajeError);
		input.classList.add("border-red-600");
	}
}
//limpiar mensaje de error
function limpiarErrrorInput(array) {
	array.forEach((input) => {
		input.value = "";
		input.classList.remove("border-red-600");
		if (input.parentElement.nextElementSibling) {
			input.parentElement.nextElementSibling.remove();
		}
	});
}

//CREACION DE TOAST CON TAILWIND
function toastPersonalizado(status, message, timer = 4000) {
	const toast = document.querySelector("#toastTailwind");
	if (toast) {
		toast.remove();
	}

	createHtmlToast(status, message);

	setTimeout(() => {
		const toast = document.querySelector("#toastTailwind");
		if (toast) {
			toast.remove();
		}
	}, timer);
}

const iconStatus = {
	success: iconSuccess,
	error: iconError,
	warning: iconWarning,
	info: iconInfo,
};

const borderStatus = {
	// success: "border-green-600",
	// error: "border-red-600",
	// warning: "border-yellow-600",
	// info: "border-blue-600",
	success: "border-none",
	error: "border-none",
	warning: "border-none",
	info: "border-none",
};

const bgStatus = {
	// success: "bg-green-50",
	// error: "bg-red-50",
	// warning: "bg-yellow-50",
	// info: "bg-blue-50",
	success: "bg-green-500",
	error: "bg-red-500",
	warning: "bg-yellow-500",
	info: "bg-blue-500",
};

const textStatus = {
	// success: "text-green-600",
	// error: "text-red-600",
	// warning: "text-yellow-600",
	// info: "text-blue-600",
	success: "text-white",
	error: "text-white",
	warning: "text-white",
	info: "text-white",
};

function createHtmlToast(status, message) {
	const body = document.querySelector("body");

	// Crear el elemento contenedor principal <div class="fixed top-10 right-2 z-50">
	const mainDiv = document.createElement("div");
	mainDiv.classList.add("fixed", "top-10", "right-2", "z-50");
	//agregar un id
	mainDiv.setAttribute("id", "toastTailwind");

	// Crear el elemento secundario <div class="flex justify-center items-center gap-2 p-3 text-sm text-red-600 border-2 border-red-600 rounded-lg bg-blue-50 max-w-sm shadow-lg">
	const childDiv = document.createElement("div");
	childDiv.classList.add(
		"animation-sacudiendo",
		"flex",
		"justify-center",
		"items-center",
		"gap-2",
		"px-3",
		"py-2",
		"text-sm",
		"border-2",
		"rounded-lg",
		"max-w-sm",
		"shadow-lg",
		//color de texto dinamico de textStatus y status
		textStatus[status],
		//color de borde dinamico de textStatus y status
		borderStatus[status],
		//color de fondo dinamico de textStatus y status
		bgStatus[status]
	);

	const svg = iconStatus[status](20, 20);

	// Crear el elemento <div> para el mensaje
	const messageDiv = document.createElement("div");
	messageDiv.textContent = message;

	// Añadir el elemento <svg> y el elemento <div> al elemento secundario <div>
	childDiv.appendChild(svg);
	childDiv.appendChild(messageDiv);

	// Añadir el elemento secundario
	mainDiv.appendChild(childDiv);

	// Añadir el elemento principal al body
	body.appendChild(mainDiv);
}

function iconSuccess(width, height) {
	const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
	svg.setAttribute("width", width);
	svg.setAttribute("height", height);
	svg.setAttribute("fill", "currentColor");
	svg.setAttribute("class", `bi bi-check-circle`);
	svg.setAttribute("viewBox", "0 0 16 16");

	// Crear el elemento <path> dentro del elemento <svg>
	const path1 = document.createElementNS("http://www.w3.org/2000/svg", "path");
	path1.setAttribute("d", "M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z");

	// Crear el elemento <path> dentro del elemento <svg>
	const path2 = document.createElementNS("http://www.w3.org/2000/svg", "path");
	path2.setAttribute(
		"d",
		"M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"
	);

	// Añadir los elementos <path> al elemento <svg>
	svg.appendChild(path1);
	svg.appendChild(path2);

	return svg;
}

function iconError(width, height) {
	const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
	svg.setAttribute("width", width);
	svg.setAttribute("height", height);
	svg.setAttribute("fill", "currentColor");
	svg.setAttribute("class", `bi bi-x-circle`);
	svg.setAttribute("viewBox", "0 0 16 16");

	// Crear el elemento <path> dentro del elemento <svg>
	const path1 = document.createElementNS("http://www.w3.org/2000/svg", "path");
	path1.setAttribute("d", "M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z");

	// Crear el elemento <path> dentro del elemento <svg>
	const path2 = document.createElementNS("http://www.w3.org/2000/svg", "path");
	path2.setAttribute(
		"d",
		"M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"
	);

	// Añadir los elementos <path> al elemento <svg>
	svg.appendChild(path1);
	svg.appendChild(path2);

	return svg;
}

function iconWarning(width, height) {
	const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
	svg.setAttribute("width", width);
	svg.setAttribute("height", height);
	svg.setAttribute("fill", "currentColor");
	svg.setAttribute("class", `bi bi-exclamation-circle`);
	svg.setAttribute("viewBox", "0 0 16 16");

	// Crear el elemento <path> dentro del elemento <svg>
	const path1 = document.createElementNS("http://www.w3.org/2000/svg", "path");
	path1.setAttribute("d", "M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z");

	// Crear el elemento <path> dentro del elemento <svg>
	const path2 = document.createElementNS("http://www.w3.org/2000/svg", "path");
	path2.setAttribute(
		"d",
		"M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"
	);

	// Añadir los elementos <path> al elemento <svg>
	svg.appendChild(path1);
	svg.appendChild(path2);

	return svg;
}

function iconInfo(width, height) {
	const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
	svg.setAttribute("width", width);
	svg.setAttribute("height", height);
	svg.setAttribute("fill", "currentColor");
	svg.setAttribute("class", `bi bi-info-circle`);
	svg.setAttribute("viewBox", "0 0 16 16");

	// Crear el elemento <path> dentro del elemento <svg>
	const path1 = document.createElementNS("http://www.w3.org/2000/svg", "path");
	path1.setAttribute("d", "M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z");

	// Crear el elemento <path> dentro del elemento <svg>
	const path2 = document.createElementNS("http://www.w3.org/2000/svg", "path");
	path2.setAttribute(
		"d",
		"m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"
	);

	// Añadir los elementos <path> al elemento <svg>
	svg.appendChild(path1);
	svg.appendChild(path2);

	return svg;
}

//ALERTA BASICO CON TAILWIND
class Alerta {
	fire({ icono, titulo, botonConfirmar = "Aceptar", botonCancelar = "Cancelar" }) {
		const colorIcon = {
			success: "text-green-600",
			error: "text-red-600",
			warning: "text-yellow-600",
			info: "text-blue-600",
		};

		// Crea los elementos HTML para la alerta
		const content = document.createElement("div");
		const fondo = document.createElement("div");
		const alerta = document.createElement("div");

		const contentIcono = document.createElement("div");
		const tituloElemento = document.createElement("div");

		const botonesElemento = document.createElement("div");
		const botonCancelarElemento = document.createElement("button");
		const botonConfirmarElemento = document.createElement("button");

		// Aplica las clases de Tailwind CSS a los elementos HTML
		content.classList.add(
			"fixed",
			"top-0",
			"h-screen",
			"w-screen",
			"z-30",
			"flex",
			"justify-center",
			"items-center"
		);
		fondo.classList.add("absolute", "top-0", "w-full", "h-full", "bg-gray-900", "opacity-50");
		alerta.classList.add("bg-white", "p-6", "rounded-lg", "shadow-md", "z-40");

		contentIcono.classList.add("flex", "justify-center", "items-center", colorIcon[icono]);
		tituloElemento.classList.add("text-2xl", "font-medium", "mb-5", "text-center");

		botonesElemento.classList.add("flex", "justify-center", "items-center", "gap-4");

		botonConfirmarElemento.classList.add(
			"bg-blue-500",
			"hover:bg-blue-700",
			"text-white",
			"font-semibold",
			"py-2",
			"px-6",
			"border",
			"border-none",
			"rounded",
			"shadow"
		);
		botonCancelarElemento.classList.add(
			"bg-red-500",
			"hover:bg-red-700",
			"text-white",
			"font-semibold",
			"py-2",
			"px-6",
			"border",
			"border-none",
			"rounded",
			"shadow"
		);

		// Agrega los textos a los elementos HTML
		const svg = iconStatus[icono](50, 50);
		tituloElemento.textContent = titulo;
		botonConfirmarElemento.textContent = botonConfirmar;
		botonCancelarElemento.textContent = botonCancelar;

		// Agrega el icono a su elemento HTML correspondiente
		contentIcono.appendChild(svg);

		// Agrega los elementos HTML a la alerta
		botonesElemento.appendChild(botonConfirmarElemento);
		botonesElemento.appendChild(botonCancelarElemento);

		//agregar elementos a la alerta
		alerta.appendChild(contentIcono);
		alerta.appendChild(tituloElemento);
		alerta.appendChild(botonesElemento);

		// Agrega el fondo y la alerta al contenedor
		content.appendChild(fondo);
		content.appendChild(alerta);

		// Agrega la alerta al DOM
		document.body.appendChild(content);

		// Crea una promesa para esperar la respuesta del usuario
		return new Promise((resolve) => {
			botonCancelarElemento.addEventListener("click", () => {
				document.body.removeChild(content);
				resolve(false);
			});

			botonConfirmarElemento.addEventListener("click", () => {
				document.body.removeChild(content);
				resolve(true);
			});
		});
	}

	then(callback) {
		this.fire().then(callback);
	}
}

const Swal = new Alerta();

//CLASE MODAL
class TailwindModal {
	constructor(selector) {
		this.modal = document.querySelector(selector);
	}

	show() {
		this.modal.classList.remove("opacity-0");
		this.modal.classList.remove("pointer-events-none");
		this.fueraContenido();
		this.closeOverlay();
		this.closeButton();
	}

	hide() {
		this.modal.classList.add("opacity-0");
		this.modal.classList.add("pointer-events-none");
	}

	fueraContenido() {
		// document.onkeydown
		document.addEventListener("keydown", (e) => {
			if (e.key === "Escape") {
				this.hide();
			}
		});
	}

	closeOverlay() {
		const overlay = this.modal.querySelector(".modal-overlay");
		overlay.addEventListener("click", () => {
			this.hide();
		});
	}

	closeButton() {
		const closeButton = this.modal.querySelectorAll(".modal-close");
		closeButton.forEach((button) => {
			button.addEventListener("click", () => {
				this.hide();
			});
		});
	}
}

//previsualizar imagen
function visorFoto(input, size1mb = true) {
	let inputFoto = document.querySelector(input);
	let contenedorFoto = inputFoto.parentElement.parentElement.nextElementSibling.firstElementChild;
	let imagenInicial = contenedorFoto.getAttribute("src");

	let contenedorNombre = inputFoto.parentElement.parentElement.nextElementSibling;
	let nombreFotoElemento = document.createElement("p");
	nombreFotoElemento.classList.add("text-center", "text-gray-600", "text-xs");

	inputFoto.addEventListener("change", function (e) {
		let file = e.target.files[0];
		//nombre de la imagen
		let nombreFoto = file["name"];
		//clase de js hace lectura de archivo
		var datosImagen = new FileReader();
		//leer como dato url la imagen cargada
		datosImagen.readAsDataURL(file);

		//validar que sea una imagen
		if (file["type"] != "image/jpg" && file["type"] != "image/png" && file["type"] != "image/jpeg") {
			inputFoto.value = "";
			nombreFotoElemento.textContent = "";
			contenedorFoto.setAttribute("src", imagenInicial);
			file = null;
			toastPersonalizado("error", "Error de formato de imagen debe ser jpg o png");
			return;
		}

		//validar tamaño de la imagen
		if (size1mb && file["size"] > 1000000) {
			inputFoto.value = "";
			nombreFotoElemento.textContent = "";
			contenedorFoto.setAttribute("src", imagenInicial);
			file = null;
			toastPersonalizado("error", "Error de tamaño de imagen debe ser menor a 1MB");
			return;
		}

		//cuando la imagen este cargada
		datosImagen.addEventListener("load", function (event) {
			//asignar la imagen al elemento img
			contenedorFoto.setAttribute("src", event.target.result);
		});

		//nombre de la imagen
		nombreFotoElemento.textContent = nombreFoto;
		contenedorNombre.appendChild(nombreFotoElemento);
	});
}

//datatable
class DataTable {
	constructor(container, data, headers, cantFiles = [5, 10, 20]) {
		this.container = document.querySelector(container);
		this.data = data;
		this.headers = headers;
		this.page = 1;
		this.rowsPerPage = cantFiles[0];
		this.rowsPerPageOptions = cantFiles;
		this.searchTerm = "";
		this.sortedBy = null;
		this.sortedAsc = true;

		//crear div hijo de container
		const containerTable = document.createElement("div");
		containerTable.classList.add("datatable-container", "flex", "flex-col");
		this.container.append(containerTable);
	}

	init() {
		if (this.container.querySelector(".datatable-header")) {
			this.container.querySelector(".datatable-header").remove();
		}
		if (this.container.querySelector(".datatable-table")) {
			this.container.querySelector(".datatable-table").remove();
		}
		this.renderHeader();
		this.renderTable();
		this.render();
	}

	renderHeader() {
		const headerContainer = document.createElement("div");
		headerContainer.classList.add("datatable-header");

		const searchInput = document.createElement("input");
		searchInput.type = "text";
		searchInput.placeholder = "Buscar...";
		searchInput.classList.add("datatable-header-input");
		searchInput.addEventListener("input", (event) => {
			this.searchTerm = event.target.value;
			this.render();
		});

		const rowsPerPageSelect = document.createElement("select");
		rowsPerPageSelect.addEventListener("change", (event) => {
			this.rowsPerPage = parseInt(event.target.value);
			this.render();
		});

		rowsPerPageSelect.classList.add("datatable-header-input");
		this.rowsPerPageOptions.forEach((option) => {
			const rowsPerPageOption = document.createElement("option");
			rowsPerPageOption.value = option;
			rowsPerPageOption.text = `${option} filas`;
			if (option === this.rowsPerPage) {
				rowsPerPageOption.selected = true;
			}
			rowsPerPageSelect.add(rowsPerPageOption);
		});

		headerContainer.append(rowsPerPageSelect);
		headerContainer.append(searchInput);
		this.container.querySelector(".datatable-container").append(headerContainer);
	}

	renderTable() {
		const table = document.createElement("table");
		table.classList.add("datatable-table");

		//thead
		const thead = document.createElement("thead");
		const tr = document.createElement("tr");
		tr.classList.add("datatable-table-thead-tr");

		for (const key in this.headers) {
			const th = document.createElement("th");
			th.innerText = this.headers[key];
			th.dataset.key = key;
			th.classList.add("datatable-table-thead-th");
			if (key !== "action" && key !== "actions") {
				th.addEventListener("click", () => {
					if (this.sortedBy === key) {
						this.sortedAsc = !this.sortedAsc;
					} else {
						this.sortedBy = key;
						this.sortedAsc = true;
					}
					const table = this.container.querySelector("table");
					for (const header of table.querySelectorAll("th")) {
						header.innerText = this.headers[header.dataset.key];
					}
					const arrow = this.sortedAsc ? "↑" : "↓";
					th.innerText += ` ${arrow}`;
					this.render();
				});
			}
			tr.append(th);
		}

		thead.append(tr);
		table.append(thead);

		const tbody = document.createElement("tbody");
		tbody.classList.add("datatable-table-tbody");
		table.append(tbody);

		const tableResponsive = document.createElement("div");
		tableResponsive.classList.add("table-container-responsive");
		tableResponsive.append(table);

		this.container.querySelector(".datatable-container").append(tableResponsive);
	}

	render() {
		const filteredData = this.filtrarData();

		const sortedData = this.ordenarData(filteredData);

		const totalPages = this.obtenerNumeroPaginas(sortedData);
		const visibleRows = this.obtenerDataPaginaActual(sortedData);

		const table = this.container.querySelector("table");
		const tbody = table.querySelector("tbody");
		tbody.innerHTML = "";
		visibleRows.forEach((row) => {
			const tr = document.createElement("tr");
			//filas
			tr.classList.add("datatable-table-tbody-tr");
			for (const key in this.headers) {
				const td = document.createElement("td");
				td.classList.add("datatable-table-tbody-td");
				td.innerHTML = row[key];
				tr.append(td);
			}
			tbody.append(tr);
		});

		// cambiar paginacion en cada render
		const paginationContainer = this.container.querySelector(".datatable-pagination");
		if (paginationContainer) {
			paginationContainer.remove();
		}
		const pagination = this.renderPagination(totalPages);
		this.container.querySelector(".datatable-container").append(pagination);
	}

	renderPagination(totalPages) {
		const paginationContainer = document.createElement("div");
		paginationContainer.classList.add("datatable-pagination");

		const previousButton = document.createElement("button");
		previousButton.classList.add("table-btn-previous");
		previousButton.innerHTML =
			'<svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';
		previousButton.disabled = this.page === 1;
		previousButton.addEventListener("click", () => {
			this.page--;
			this.render();
		});
		paginationContainer.append(previousButton);

		if (totalPages <= 5) {
			for (let i = 1; i <= totalPages; i++) {
				const pageButton = this.renderPageButton(i);
				if (i === this.page) {
					pageButton.classList.add("active");
				}
				paginationContainer.append(pageButton);
			}
		} else {
			let startPage, endPage;
			if (this.page <= 3) {
				startPage = 1;
				endPage = 5;
			} else if (this.page + 1 >= totalPages) {
				startPage = totalPages - 4;
				endPage = totalPages;
			} else {
				startPage = this.page - 2;
				endPage = this.page + 2;
			}
			if (startPage > 1) {
				const startButton = this.renderPageButton(1);
				paginationContainer.append(startButton);
				if (startPage > 2) {
					const separator = document.createElement("span");
					separator.classList.add("table-btn-points");
					separator.innerHTML = "...";
					paginationContainer.append(separator);
				}
			}
			for (let i = startPage; i <= endPage; i++) {
				const pageButton = this.renderPageButton(i);
				if (i === this.page) {
					pageButton.classList.add("active");
				}
				paginationContainer.append(pageButton);
			}
			if (endPage < totalPages) {
				if (endPage < totalPages - 1) {
					const separator = document.createElement("span");
					separator.innerHTML = "...";
					separator.classList.add("table-btn-points");
					paginationContainer.append(separator);
				}
				const endButton = this.renderPageButton(totalPages);
				paginationContainer.append(endButton);
			}
		}

		const nextButton = document.createElement("button");
		nextButton.classList.add("table-btn-next");
		nextButton.innerHTML =
			"<svg class='w-4 h-4 fill-current' viewBox='0 0 20 20'><path d='M12.828 10l-4.828-4.828 1.414-1.414L16.656 10l-7.757 7.757-1.414-1.414L12.828 10z'/></svg>";
		nextButton.disabled = this.page === totalPages;
		nextButton.addEventListener("click", () => {
			this.page++;
			this.render();
		});
		paginationContainer.append(nextButton);

		return paginationContainer;
	}

	renderPageButton(pageNumber) {
		const button = document.createElement("button");
		button.classList.add("table-btn-number");
		button.innerHTML = pageNumber;
		button.disabled = pageNumber === this.page;
		button.addEventListener("click", () => {
			this.page = pageNumber;
			this.render();
		});
		return button;
	}

	filtrarData() {
		if (this.searchTerm === "") {
			return this.data;
		} else {
			const lowerCaseSearchTerm = this.searchTerm.toLowerCase();
			return this.data.filter((row) =>
				Object.values(row).some(
					(value) => value.toString().toLowerCase().indexOf(lowerCaseSearchTerm) !== -1
				)
			);
		}
	}

	ordenarData(data) {
		if (this.sortedBy === null) {
			return data;
		} else {
			return data.slice().sort((a, b) => {
				const valueA = a[this.sortedBy];
				const valueB = b[this.sortedBy];
				if (typeof valueA === "string") {
					return this.sortedAsc ? valueA.localeCompare(valueB) : valueB.localeCompare(valueA);
				} else {
					return this.sortedAsc ? valueA - valueB : valueB - valueA;
				}
			});
		}
	}

	obtenerNumeroPaginas(data) {
		return Math.ceil(data.length / this.rowsPerPage);
	}

	obtenerDataPaginaActual(data) {
		const startIndex = (this.page - 1) * this.rowsPerPage;
		const endIndex = startIndex + this.rowsPerPage;
		return data.slice(startIndex, endIndex);
	}
}
