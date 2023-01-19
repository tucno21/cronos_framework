// funcion que se autoejecuta
(function () {
	const textTitle = document.getElementById("textTitle");
	const textSlug = document.getElementById("textSlug");

	// funcion que se ejecuta cuando el documento esta listo no jquery
	document.addEventListener("DOMContentLoaded", function () {
		if (textTitle) {
			textTitle.addEventListener("input", function () {
				textSlug.value = textTitle.value
					.toLowerCase()
					.replace(/ /g, "-")
					.replace(/[^\w-]+/g, "");
			});
		}
	});
})();
