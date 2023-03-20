<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $pageTitle ?? 'Crosos Framework' ?></title>
    <link href="<?= base_url . '/assets/croonos.dashboard.css' ?>" rel="stylesheet">
</head>

<body class="h-screen flex">
    <!-- SIDEBAR -->
    <div class="sidebar sm:left-0 -left-[14rem] bg-slate-900" id="sidebar">
        <div class="sidebar-logo">
            <h1 class="text-white font-black" id="titleLogo">Cronos</h1>
        </div>
        <div class="px-4 space-y-2">
            <!-- menu simple -->

            <a href="<?= route('dashboard.index') ?>" class="singleMenu active-link">
                <i class="bi bi-blockquote-right"></i>
                <h2>Dashboard</h2>
            </a>
            <!-- PRODUCTOS -->
            <!-- <div class="relative menudespeglables">
					<div class="dropdownMenu">
						<div class="relative flex space-x-2 items-center active-link">
							<i class="bi bi-bricks"></i>
							<h2>Otros</h2>
						</div>
						<i class="bi bi-chevron-down"></i>
					</div>
					<div class="dropdown-content hidden">
						<a href="#" class="dropdown-link">Opción a</a>
						<a href="#" class="dropdown-link">Opción b</a>
						<a href="#" class="dropdown-link">Opción c</a>
						<a href="#" class="dropdown-link">Opción d</a>
					</div>
				</div> -->
        </div>
    </div>

    <!-- LATERAL -->
    <div class="flex-1">
        <!-- TOP MENU -->
        <div class="top-menu bg-gray-900">
            <!-- MENU ICON HAMBURGUESA MOVIL -->
            <div class="pl-4 sm:hidden">
                <button class="top-menu-movil" id="sidebarToggleMovil">
                    <i class="bi bi-three-dots-vertical"></i>
                    <i class="bi bi-x-lg hidden"></i>
                </button>
            </div>
            <!-- MENU ICON HAMBURGUESA LAPTOP -->
            <div class="pl-4" id="menulaptop">
                <i class="bi bi-box-arrow-left top-menu-laptop"></i>
            </div>
            <!-- MENU DERECHO USER -->
            <div class="flex items-center pr-4 md:pr-8">
                <div class="relative">
                    <!-- boton user -->
                    <div class="flex space-x-2 cursor-pointer" id="botonuser">
                        <i class="bi bi-person-circle text-white text-xl"></i>
                        <span class="hidden md:block font-medium text-gray-400"><?= session()->user()->name ?></span>
                        <i class="bi bi-chevron-down h-4 w-4 text-gray-400"></i>
                    </div>
                    <!-- dropdown user -->
                    <div id="dropdownuser" class="top-menu-dropdown hidden">
                        <a href="#" class="top-menu-dropdown-link">
                            <i class="bi bi-gear-wide mr-2"></i>
                            Configuración
                        </a>
                        <a href="#" class="top-menu-dropdown-link">
                            <i class="bi bi-key mr-2"></i>
                            Cambiar contraseña
                        </a>
                        <a href="<?= route('login.logout') ?>" class="top-menu-dropdown-link">
                            <i class="bi bi-box-arrow-right mr-2"></i>
                            Cerrar sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>