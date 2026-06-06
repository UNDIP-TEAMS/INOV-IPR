<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>

    @vite(['resources/js/app.js'])
</head>
<body>

    <header class="admin-header">
        <div class="brand">
            <img src="{{ Storage::disk('s3')->url('logo.jpg') }}">
        </div>

        <a href="#" class="user-icon">
            <img src="{{ Storage::disk('s3')->url('user.png') }}">
        </a>
    </header>

    <main class="login-body">
        <div class="login-wrap">
            <h1 class="login-title">Selamat Datang</h1>

            <div class="login-card">
                    <form method="POST" action="{{ route('admin.login') }}">
                    @csrf

                    {{-- ERROR LOGIN  --}}
                    @if($errors->has('login'))
                        <div class="alert-error">
                        {{ $errors->first('login') }}
                        </div>
                    @endif

                    <input type="text" name="username" placeholder="Username" value="{{ old('username') }}">
                    <input type="password" name="password" placeholder="Password">

                    <button type="submit">Login</button>
                    </form>
            </div>
        </div>
    </main>
</body>
</html>

