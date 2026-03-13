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

    <link href="<?= base_url . '/assets/css/dashboard.css' ?>" rel="stylesheet">
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