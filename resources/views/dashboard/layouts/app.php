<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $pageTitle ?? 'Cronos Framework' ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <link href="@asset('assets/css/dashboard.css')" rel="stylesheet">

    {{-- Estilos adicionales inyectados por vistas hijas --}}
    @stack('styles')
</head>

<body class="bg-gray-50">

    <!-- OVERLAY MÓVIL -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- SIDEBAR - FIXED POSITION -->
    <div class="sidebar hidden sm:flex" id="sidebar">

        <!-- Logo -->
        <div class="sidebar-logo">
            <div class="sidebar-logo__brand">
                <div class="sidebar-logo__icon">
                    <i class="bi bi-cpu-fill"></i>
                </div>
                <h1 class="sidebar-logo__name" id="titleLogo">Cronos</h1>
            </div>
            <button class="sidebar-toggle" id="menulaptop" title="Colapsar menú">
                <i class="bi bi-layout-sidebar-reverse"></i>
            </button>
        </div>

        <!-- Nav items -->
        <div class="flex-1 px-3 py-3 space-y-0.5 overflow-y-auto">
            <span class="sidebar-section-label">Principal</span>

            <a href="<?= route('dashboard.index') ?>" class="singleMenu active-link">
                <i class="bi bi-grid-1x2-fill singleMenu__icon"></i>
                <span class="singleMenu__label">Dashboard</span>
                <span class="singleMenu__tooltip">Dashboard</span>
            </a>

            <!-- Agrega más menú items aquí con la misma estructura -->
        </div>

        <!-- Footer: info del usuario -->
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-user__avatar">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="sidebar-user__info">
                    <div class="sidebar-user__name"><?= session()->user()->name ?? '' ?></div>
                    <div class="sidebar-user__role">Administrador</div>
                </div>
            </div>
        </div>

    </div>

    <!-- MAIN CONTAINER -->
    <div class="main-container" id="mainContainer">

        <!-- TOP MENU -->
        <div class="top-menu">

            <!-- MENU ICON HAMBURGUESA MÓVIL -->
            <div class="top-menu__left">
                <div class="sm:hidden">
                    <button class="top-menu-movil" id="sidebarToggleMovil">
                        <i class="bi bi-list" id="iconMovilOpen"></i>
                        <i class="bi bi-x-lg hidden" id="iconMovilClose"></i>
                    </button>
                </div>

                <!-- Título de página -->
                <div class="hidden sm:block">
                    <span class="top-menu__title"><?= $pageTitle ?? 'Dashboard' ?></span>
                </div>
            </div>

            <!-- MENÚ DERECHO USER -->
            <div class="top-menu__right">
                <!-- Toggle laptop (visible en desktop) -->
                <div class="hidden sm:block" id="menulaptopTop">
                    <!-- vacío, el toggle está en el sidebar logo -->
                </div>

                <!-- User dropdown -->
                <div class="relative">
                    <button class="user-btn" id="botonuser">
                        <div class="user-btn__avatar">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <span class="user-btn__name hidden md:block"><?= session()->user()->name ?? '' ?></span>
                        <i class="bi bi-chevron-down user-btn__chevron"></i>
                    </button>

                    <div id="dropdownuser" class="top-menu-dropdown hidden">
                        <a href="#" class="top-menu-dropdown-link">
                            <i class="bi bi-person"></i>
                            Mi perfil
                        </a>
                        <a href="#" class="top-menu-dropdown-link">
                            <i class="bi bi-gear-wide"></i>
                            Configuración
                        </a>
                        <a href="#" class="top-menu-dropdown-link">
                            <i class="bi bi-key"></i>
                            Cambiar contraseña
                        </a>
                        <div class="divider"></div>
                        <a href="<?= route('login.logout') ?>" class="top-menu-dropdown-link top-menu-dropdown-link--danger">
                            <i class="bi bi-box-arrow-right"></i>
                            Cerrar sesión
                        </a>
                    </div>
                </div>
            </div>

        </div>
        <!-- /top-menu -->

        <!-- El contenido de cada vista se inyecta aquí -->
        @yield('content')

    </div><!-- /main-container -->

    <script>
        /* =========================================================
       CRONOS DASHBOARD — Layout JS
       Conserva toda la lógica original de cronos.dashboard.js
       Solo maneja: sidebar toggle, overlay, dropdown user,
       active link — el resto sigue en cronos.dashboard.js
    ========================================================= */

        // IIFE para evitar conflictos de scope con cronos.dashboard.js
        (function() {

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
            const dropdownuser = document.getElementById("dropdownuser");
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
            if (botonuser && dropdownuser) {
                // Asegurar que el menú empiece oculto
                dropdownuser.style.display = 'none';
                dropdownuser.style.position = 'absolute';

                botonuser.addEventListener("click", function(e) {
                    e.stopPropagation();
                    e.preventDefault();

                    const isHidden = dropdownuser.style.display === 'none';
                    dropdownuser.style.display = isHidden ? 'block' : 'none';
                    botonuser.classList.toggle("open", isHidden);

                    // Forzar z-index alto
                    dropdownuser.style.zIndex = '9999';

                    console.log("Dropdown toggled:", {
                        display: dropdownuser.style.display,
                        zIndex: dropdownuser.style.zIndex,
                        position: dropdownuser.style.position
                    });
                });
            }

            document.addEventListener("click", (e) => {
                // Cerrar el menú si el click NO está en el botón NI en el dropdown
                if (!e.target.closest("#botonuser") && !e.target.closest("#dropdownuser")) {
                    dropdownuser.style.display = 'none';
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
        })();
    </script>

    {{-- Scripts adicionales inyectados por vistas hijas --}}
    @stack('scripts')
</body>

</html>