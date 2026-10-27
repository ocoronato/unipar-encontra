<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<meta name="theme-color" content="#0a0a0a" />

<title>{{ isset($title) ? $title.' | ' : '' }}{{ config('app.name') }}</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40' fill='none' stroke='%23dc2626' stroke-width='4' stroke-linecap='round'%3E%3Ccircle cx='17' cy='17' r='12'/%3E%3Cpath d='m26 26 10 10'/%3E%3C/svg%3E">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])

<script>
    // Tema escuro por padrão (preto e vermelho). Vale só na primeira visita: depois disso
    // vale a escolha feita em Perfil › Aparência, inclusive "Sistema".
    try {
        if (! localStorage.getItem('unipar.tema-padrao')) {
            localStorage.setItem('unipar.tema-padrao', '1');

            if (! localStorage.getItem('flux.appearance')) {
                localStorage.setItem('flux.appearance', 'dark');
            }
        }
    } catch (e) {}
</script>
@fluxAppearance
