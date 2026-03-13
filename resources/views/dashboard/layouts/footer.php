</div><!-- /main-container -->

<script>
    /* =========================================================
       CRONOS DASHBOARD — Layout JS
       Conserva toda la lógica original de cronos.dashboard.js
       Solo maneja: sidebar toggle, overlay, dropdown user,
       active link — el resto sigue en cronos.dashboard.js
    ========================================================= */

    // ── Estado persistido ─────────────────────────────────────
    let full = localStorage.getItem("dashboardToggleMenu") === "true";
    if (localStorage.getItem("dashboardToggleMenu") === null) {
        localStorage.setItem("dashboardToggleMenu", "true");
        full = true;
    }

    const sidebar = document.getElementById("sidebar");
    const mainContainer = document.getElementById("mainContainer");
    const titleLogo = document.getElementById("titleLogo");
    const singleMenu = document.querySelectorAll(".singleMenu");
    const dropdownMenu = document.querySelectorAll(".dropdownMenu");
    const sidebarToggleMovil = document.getElementById("sidebarToggleMovil");
    const botonuser = document.getElementById("botonuser");
    const menulaptop = document.getElementById("menulaptop");
    const sidebarOverlay = document.getElementById("sidebarOverlay");

    // ── Función principal de toggle collapsed ─────────────────
    function cambioClassList() {
        // Sidebar width
        sidebar.classList.toggle("w-14", !full);
        sidebar.classList.remove(full ? "w-14" : "");

        if (full) {
            sidebar.style.width = "15rem";
            mainContainer.style.marginLeft = "15rem";
        } else {
            sidebar.style.width = "3.5rem";
            mainContainer.style.marginLeft = "3.5rem";
        }

        // Título logo
        if (titleLogo) {
            titleLogo.style.opacity = full ? "1" : "0";
            titleLogo.style.width = full ? "auto" : "0";
            titleLogo.style.overflow = "hidden";
        }

        // Ícono del toggle
        if (menulaptop) {
            const icon = menulaptop.querySelector("i");
            if (icon) {
                icon.className = full ?
                    "bi bi-layout-sidebar-reverse" :
                    "bi bi-layout-sidebar";
            }
        }

        // Items del menú: mostrar/ocultar labels
        singleMenu.forEach((menu) => {
            menu.classList.toggle("justify-start", full);
            menu.classList.toggle("justify-center", !full);
            const label = menu.querySelector(".singleMenu__label");
            if (label) {
                label.style.opacity = full ? "1" : "0";
                label.style.width = full ? "auto" : "0";
            }
        });

        // Tooltips en modo colapsado
        singleMenu.forEach((menu) => {
            if (!full) {
                menu.addEventListener("mouseenter", showTooltip);
                menu.addEventListener("mouseleave", hideTooltip);
            } else {
                menu.removeEventListener("mouseenter", showTooltip);
                menu.removeEventListener("mouseleave", hideTooltip);
            }
        });

        // dropdownMenu labels
        dropdownMenu.forEach((menu) => {
            const firstChildLabel = menu.firstElementChild?.lastElementChild;
            const lastChild = menu.lastElementChild;
            if (firstChildLabel) firstChildLabel.classList.toggle("hidden", !full);
            if (lastChild) lastChild.classList.toggle("hidden", !full);
            menu.classList.toggle("justify-between", full);
            menu.classList.toggle("justify-center", !full);
            menu.classList.toggle("menu-comprimido", !full);
            menu.classList.toggle("menu-extendido", full);
        });

        // Footer info del usuario
        const userInfo = document.querySelector(".sidebar-user__info");
        if (userInfo) {
            userInfo.style.opacity = full ? "1" : "0";
            userInfo.style.width = full ? "auto" : "0";
        }
    }

    function showTooltip(e) {
        const tooltip = e.currentTarget.querySelector(".singleMenu__tooltip");
        if (tooltip) tooltip.style.display = "block";
    }

    function hideTooltip(e) {
        const tooltip = e.currentTarget.querySelector(".singleMenu__tooltip");
        if (tooltip) tooltip.style.display = "none";
    }

    cambioClassList();

    // ── Toggle laptop ──────────────────────────────────────────
    if (menulaptop) {
        menulaptop.addEventListener("click", () => {
            full = !full;
            localStorage.setItem("dashboardToggleMenu", full);
            cambioClassList();
        });
    }

    // ── Toggle móvil ───────────────────────────────────────────
    if (sidebarToggleMovil) {
        sidebarToggleMovil.addEventListener("click", () => {
            const isOpen = sidebar.classList.contains("sidebar--open");
            sidebar.classList.toggle("sidebar--open", !isOpen);
            sidebarOverlay.classList.toggle("active", !isOpen);

            // Íconos hamburguesa / X
            const iconOpen = document.getElementById("iconMovilOpen");
            const iconClose = document.getElementById("iconMovilClose");
            if (iconOpen && iconClose) {
                iconOpen.classList.toggle("hidden", !isOpen);
                iconClose.classList.toggle("hidden", isOpen);
            }

            document.body.style.overflow = isOpen ? "" : "hidden";
        });
    }

    // Cerrar sidebar móvil al tocar el overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener("click", () => {
            sidebar.classList.remove("sidebar--open");
            sidebarOverlay.classList.remove("active");
            document.getElementById("iconMovilOpen")?.classList.remove("hidden");
            document.getElementById("iconMovilClose")?.classList.add("hidden");
            document.body.style.overflow = "";
        });
    }

    // ── User dropdown ──────────────────────────────────────────
    if (botonuser) {
        botonuser.addEventListener("click", function(e) {
            e.stopPropagation();
            dropdownuser.classList.toggle("hidden");
            botonuser.classList.toggle("open");
        });
    }

    document.addEventListener("click", (e) => {
        if (!e.target.closest("#botonuser")) {
            dropdownuser?.classList.add("hidden");
            botonuser?.classList.remove("open");
        }
    });

    // ── Sidebar en móvil: asegurar que siempre empiece cerrado ─
    if (window.innerWidth < 640) {
        sidebar.style.width = "";
        mainContainer.style.marginLeft = "";
    }

    // Estilos del sidebar en móvil (desde CSS)
    const styleMovil = document.createElement("style");
    styleMovil.textContent = `
        @media (max-width: 639px) {
            .sidebar {
                transform: translateX(-100%) !important;
                width: 15rem !important;
                transition: transform 0.3s ease, width 0.3s ease !important;
            }
            .sidebar.sidebar--open {
                transform: translateX(0) !important;
            }
            .main-container {
                margin-left: 0 !important;
            }
        }
    `;
    document.head.appendChild(styleMovil);
</script>

</body>

</html>