<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $pageTitle ?? 'Crosos Framework' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= base_url . '/assets/index.css' ?>" rel="stylesheet">
</head>

<body class="font-[Poppins]">
    <header class="bg-gradient-to-r from-blue-700 via-blue-800 to-blue-900 fixed top-0 w-full h-16 flex z-20">
        <nav class="flex justify-between items-center w-[92%]  mx-auto">
            <!-- logo -->
            <div>
                <a href="<?= route('home.index') ?>" class="text-white flex justify-center items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-cpu-fill drop-shadow-lg" viewBox="0 0 16 16 ">
                        <path d="M6.5 6a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z" />
                        <path d="M5.5.5a.5.5 0 0 0-1 0V2A2.5 2.5 0 0 0 2 4.5H.5a.5.5 0 0 0 0 1H2v1H.5a.5.5 0 0 0 0 1H2v1H.5a.5.5 0 0 0 0 1H2v1H.5a.5.5 0 0 0 0 1H2A2.5 2.5 0 0 0 4.5 14v1.5a.5.5 0 0 0 1 0V14h1v1.5a.5.5 0 0 0 1 0V14h1v1.5a.5.5 0 0 0 1 0V14h1v1.5a.5.5 0 0 0 1 0V14a2.5 2.5 0 0 0 2.5-2.5h1.5a.5.5 0 0 0 0-1H14v-1h1.5a.5.5 0 0 0 0-1H14v-1h1.5a.5.5 0 0 0 0-1H14v-1h1.5a.5.5 0 0 0 0-1H14A2.5 2.5 0 0 0 11.5 2V.5a.5.5 0 0 0-1 0V2h-1V.5a.5.5 0 0 0-1 0V2h-1V.5a.5.5 0 0 0-1 0V2h-1V.5zm1 4.5h3A1.5 1.5 0 0 1 11 6.5v3A1.5 1.5 0 0 1 9.5 11h-3A1.5 1.5 0 0 1 5 9.5v-3A1.5 1.5 0 0 1 6.5 5z" />
                    </svg>
                    <span class="font-bold text-2xl drop-shadow-lg hidden md:block">Cronos Framework</span>
                    <span class="font-bold text-2xl drop-shadow-lg md:hidden">Cronos</span>
                </a>
            </div>
            <!-- menu -->
            <div class="duration-300 absolute md:static bg-blue-800 md:bg-transparent md:min-h-fit min-h-[60vh] right-[-100%] top-16 md:w-auto  w-full flex items-center px-5" id="navLinks">
                <ul class="flex flex-col md:flex-row md:items-center md:gap-[4vw] gap-8 text-white text-2xl md:text-base">
                    <li>
                        <a class="border-b md:border-none md:hover:font-semibold md:hover:bg-blue-700 md:px-2 md:py-1 md:rounded-tr-lg md:rounded-bl-lg transition duration-300" href="<?= route('home.index') ?>">Home</a>
                    </li>
                    <li>
                        <a class="border-b md:border-none md:hover:font-semibold md:hover:bg-blue-700 md:px-2 md:py-1 md:rounded-tr-lg md:rounded-bl-lg transition duration-300" href="<?= route('login.index') ?>">Login</a>
                    </li>
                    <li>
                        <a class="border-b md:border-none md:hover:font-semibold md:hover:bg-blue-700 md:px-2 md:py-1 md:rounded-tr-lg md:rounded-bl-lg transition duration-300" href="<?= route('register.index') ?>">Register</a>
                    </li>
                </ul>
            </div>
            <!-- botomenu -->
            <div class="md:hidden">
                <button id="btnMenu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-justify-right h-8 w-8 text-white" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M6 12.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm-4-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zm0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zm0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z" />
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-lg h-8 w-8 text-white hidden" viewBox="0 0 16 16">
                        <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z" />
                    </svg>
                </button>
            </div>
    </header>