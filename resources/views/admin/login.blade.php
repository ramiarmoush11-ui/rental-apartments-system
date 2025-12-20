<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex items-center justify-center bg-slate-900">

<form method="POST" action="{{ route('admin.login.submit') }}"
      class="bg-slate-800 p-8 rounded-xl w-96 text-white">
  @csrf

  <h1 class="text-2xl font-bold mb-6 text-center">Admin Login</h1>

  @if($errors->any())
    <div class="mb-4 text-red-400 text-sm">
      {{ $errors->first() }}
    </div>
  @endif

  <div class="mb-4">
    <label>Email</label>
    <input type="email" name="email"
           class="w-full mt-1 p-2 rounded bg-slate-700"
           required>
  </div>

  <div class="mb-6">
    <label>Password</label>
    <input type="password" name="password"
           class="w-full mt-1 p-2 rounded bg-slate-700"
           required>
  </div>

  <button class="w-full bg-indigo-600 py-2 rounded hover:bg-indigo-700">
    Login
  </button>
</form>

</body>
</html>
