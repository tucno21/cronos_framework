const btnCrear = document.querySelector("#btnCrear");
const modal = new TailwindModal("#contentModal");
const formulario = document.querySelector("#formulario");
const btnSubmit = document.querySelector("#btnSubmit");
const textTitle = document.querySelector("#title");
const textSlug = document.querySelector("#slug");
const textContent = document.querySelector("#content");
const simpleDatatable = document.querySelector("#simpleDatatable-wrapper");

//cuando el documento este listo
document.addEventListener("DOMContentLoaded", function () {
	btnRegistrarBlog();
	registrarBotones();
	generarSlug();
});

// Función de inicialización que el componente llamará automáticamente
window.__dt_init_simpleDatatable = async function () {
	const response = await fetch(`${baseLink}/dashboard/blogs`);
	const data = await response.json();
	data.forEach((element) => {
		element.action = `
						<button class="btn-action-view btnShow" data-id="${element.slug}" title="ver post">
							<i class="bi bi-file-earmark-text"></i>
						</button>
						<button class="btn-action-edit btnEdita" data-id="${element.id}" title="editar post">
							<i class="bi bi-pencil"></i>
						</button>
						<button class="btn-action-delete btnEliminar" data-id="${element.id}" title="eliminar post">
							<i class="bi bi-trash"></i>
						</button>
						`;
	});

	const headers = {
		id: "ID",
		name: "Autor",
		title: "Titulo",
		action: "Acciones",
	};
	const table = new DataTable("#simpleDatatable-wrapper", data, headers, [10, 15, 20]);
	table.init();

	// Registrar la instancia para acceso externo
	window.__datatables['simpleDatatable'] = table;
};

// Wrapper function para recargar la tabla
async function renderTable() {
	const response = await fetch(`${baseLink}/dashboard/blogs`);
	const data = await response.json();
	data.forEach((element) => {
		element.action = `
						<button class="btn-action-view btnShow" data-id="${element.slug}" title="ver post">
							<i class="bi bi-file-earmark-text"></i>
						</button>
						<button class="btn-action-edit btnEdita" data-id="${element.id}" title="editar post">
							<i class="bi bi-pencil"></i>
						</button>
						<button class="btn-action-delete btnEliminar" data-id="${element.id}" title="eliminar post">
							<i class="bi bi-trash"></i>
						</button>
						`;
	});

	const headers = {
		id: "ID",
		name: "Autor",
		title: "Titulo",
		action: "Acciones",
	};

	// Recrear la instancia de DataTable
	const table = new DataTable("#simpleDatatable-wrapper", data, headers, [10, 15, 20]);
	table.init();

	// Actualizar la referencia global
	window.__datatables['simpleDatatable'] = table;
}

function btnRegistrarBlog() {
	if (!btnCrear) return;
	btnCrear.addEventListener("click", () => {
		limpiarErrrorInput([textTitle, textSlug, textContent]);
		btnSubmit.textContent = "Crear";
		modal.show();
		// modal.hide();
		registrarBlog();
	});
}

function registrarBlog() {
	formulario.addEventListener("submit", async (e) => {
		e.preventDefault();

		const data = new FormData(formulario);
		const response = await fetch(`${baseLink}/dashboard/create`, {
			method: "POST",
			body: data,
		});
		const dataResponse = await response.json();

		if (dataResponse.status === "success") {
			modal.hide();
			toastPersonalizado("success", dataResponse.message);
			renderTable();
		} else {
			mensajeErrorInput(textTitle, dataResponse.message.title);
			mensajeErrorInput(textSlug, dataResponse.message.slug);
			mensajeErrorInput(textContent, dataResponse.message.content);
		}
	});
}

function registrarBotones() {
	if (!simpleDatatable) return;
	simpleDatatable.addEventListener("click", (e) => {
		// console.log(e.target);
		if (e.target.closest(".btnShow")) {
			const id = e.target.dataset.id || e.target.parentElement.dataset.id;
			verPost(id);
		}
		if (e.target.closest(".btnEliminar")) {
			const id = e.target.dataset.id || e.target.parentElement.dataset.id;
			eliminarBlog(id);
		}
		if (e.target.closest(".btnEdita")) {
			const id = e.target.dataset.id || e.target.parentElement.dataset.id;
			limpiarErrrorInput([textTitle, textSlug, textContent]);
			btnSubmit.textContent = "Editar";
			modal.show();
			editarBlog(id);
		}
	});
}

async function editarBlog(id) {
	const response = await fetch(`${baseLink}/dashboard/${id}/edit`);
	const blog = await response.json();
	textTitle.value = blog.title;
	textSlug.value = blog.slug;
	textContent.value = blog.content;

	formulario.addEventListener("submit", async (e) => {
		e.preventDefault();

		const data = new FormData(formulario);
		const response = await fetch(`${baseLink}/dashboard/${id}/edit`, {
			method: "PUT",
			//enviar json
			headers: {
				"Content-Type": "application/json",
			},
			body: JSON.stringify({
				title: data.get("title"),
				slug: data.get("slug"),
				content: data.get("content"),
			}),
		});
		const dataResponse = await response.json();
		if (dataResponse.status === "success") {
			modal.hide();
			toastPersonalizado("success", dataResponse.message);
			renderTable();
		} else {
			mensajeErrorInput(textTitle, dataResponse.message.title);
			mensajeErrorInput(textSlug, dataResponse.message.slug);
			mensajeErrorInput(textContent, dataResponse.message.content);
		}
	});
}

function eliminarBlog(id) {
	Swal.fire({
		icono: "warning",
		titulo: "¿Estas seguro eliminar este post?",
		botonConfirmar: "si",
		botonCancelar: "no",
	}).then(async (result) => {
		if (result) {
			const response = await fetch(`${baseLink}/dashboard/${id}/delete`, {
				method: "DELETE",
			});
			const dataResponse = await response.json();
			if (dataResponse.status === "success") {
				toastPersonalizado("success", dataResponse.message);
				renderTable();
			}
		}
	});
}

function verPost(id) {
	// redirigir al post
	window.location.href = `${baseLink}/dashboard/${id}`;
}

function generarSlug() {
	if (textTitle) {
		textTitle.addEventListener("input", function () {
			textSlug.value = textTitle.value
				.toLowerCase()
				.replace(/ /g, "-")
				.replace(/[^\w-]+/g, "");
		});
	}
}
