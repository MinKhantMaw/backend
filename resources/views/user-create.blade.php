<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Create User</title>
        @vite(['resources/css/app.css', 'resources/js/user-create.jsx'])
    </head>
    <body class="min-h-screen bg-slate-50">
        <div id="app"></div>
    </body>
</html>
