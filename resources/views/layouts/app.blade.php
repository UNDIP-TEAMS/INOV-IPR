<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title','Test')</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  @vite(['resources/css/app.css', 'resources/js/app.js'])
  
  <script src="{{ asset('js/nextbutton.js') }}?v={{ time() }}" defer></script>
</head>

<body class="@yield('body-class')">


  @include('partials.header')

  <main>
    @yield('content')
  </main>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  @include('partials.footer')
</body>
</html>
