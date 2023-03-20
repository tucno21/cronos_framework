@include('home.layouts.head')

<div class="bg-slate-100 mt-16 min-h-[calc(100vh-4rem)] flex items-center">
    <div class="container mx-auto">
        <div class="h-full flex flex-col md:flex-row gap-4 md:gap-6 p-3 md:p-6 justify-center items-center">
            <div class="flex-1 flex flex-col p-4">
                <h1 class="font-bold text-3xl md:text-4xl leading-tight">
                    Bienvenido
                </h1>
                <p class="mt-4 mb-4">
                    Este mini-framework, inspirado en Laravel y diseñado para proyectos pequeños, fue creado como parte de mi aprendizaje en PHP. Mi objetivo fue imitar las funcionalidades básicas y más utilizadas en los tutoriales de Laravel. Actualmente, el framework está en desarrollo y se actualizará con nuevas funcionalidades en el futuro.
                </p>
                <a href="https://github.com/tucno21/cronos_framework" target="_blank" class="bg-blue-800 text-white px-3 py-2 rounded text-center">
                    Ver Proyecto
                </a>
            </div>
            <div class="flex-1 flex flex-col justify-center items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" fill="currentColor" class="bi bi-cpu-fill text-blue-800" viewBox="0 0 16 16">
                    <path d="M6.5 6a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z" />
                    <path d="M5.5.5a.5.5 0 0 0-1 0V2A2.5 2.5 0 0 0 2 4.5H.5a.5.5 0 0 0 0 1H2v1H.5a.5.5 0 0 0 0 1H2v1H.5a.5.5 0 0 0 0 1H2v1H.5a.5.5 0 0 0 0 1H2A2.5 2.5 0 0 0 4.5 14v1.5a.5.5 0 0 0 1 0V14h1v1.5a.5.5 0 0 0 1 0V14h1v1.5a.5.5 0 0 0 1 0V14h1v1.5a.5.5 0 0 0 1 0V14a2.5 2.5 0 0 0 2.5-2.5h1.5a.5.5 0 0 0 0-1H14v-1h1.5a.5.5 0 0 0 0-1H14v-1h1.5a.5.5 0 0 0 0-1H14v-1h1.5a.5.5 0 0 0 0-1H14A2.5 2.5 0 0 0 11.5 2V.5a.5.5 0 0 0-1 0V2h-1V.5a.5.5 0 0 0-1 0V2h-1V.5a.5.5 0 0 0-1 0V2h-1V.5zm1 4.5h3A1.5 1.5 0 0 1 11 6.5v3A1.5 1.5 0 0 1 9.5 11h-3A1.5 1.5 0 0 1 5 9.5v-3A1.5 1.5 0 0 1 6.5 5z" />
                </svg>
                <span class="block font-bold text-2xl drop-shadow-lg text-blue-800">Cronos Framework</span>
                <span class="block font-bold text-2xl drop-shadow-lg text-blue-800">PHP</span>

            </div>
        </div>
    </div>
</div>

@include('home.layouts.footer')