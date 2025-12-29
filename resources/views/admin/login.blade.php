<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body
    class="min-h-screen flex items-center justify-center 
             bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 
             dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 
             transition-colors duration-300">

    <form method="POST" action="{{ route('admin.login.submit') }}"
        class="bg-white dark:bg-gray-800 p-12 rounded-3xl w-[500px] text-gray-900 dark:text-gray-100 shadow-2xl border border-indigo-100 dark:border-gray-700">
        @csrf

        <h1
            class="text-4xl font-extrabold mb-10 text-center text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600">
            ⚡ Admin Login
        </h1>

        @if ($errors->any())
            <div class="mb-6 text-red-500 text-lg font-medium text-center">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Email -->
        <div class="mb-6">
            <label class="block mb-2 font-semibold text-lg">Email</label>
            <div
                class="flex items-center bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-700 dark:to-gray-600 rounded-xl shadow px-3">
                <span class="px-2 text-xl">📧</span>
                <input type="email" name="email" class="w-full p-3 text-lg bg-transparent focus:outline-none"
                    required>
            </div>
        </div>

        <!-- Password -->
        <div class="mb-8">
            <label class="block mb-2 font-semibold text-lg">Password</label>
            <div
                class="flex items-center bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-700 dark:to-gray-600 rounded-xl shadow px-3">
                <span class="px-2 text-xl">🔒</span>
                <input type="password" name="password" class="w-full p-3 text-lg bg-transparent focus:outline-none"
                    required>
            </div>
        </div>

        <!-- Remember Me -->
        <div class="flex items-center mb-8">
            <input type="checkbox" name="remember" id="remember" class="mr-3 w-5 h-5 accent-indigo-600">
            <label for="remember" class="text-md">Remember Me</label>
        </div>

        <!-- Login Button -->
        <button type="submit"
            class="w-full py-4 rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600
                 text-white text-lg font-semibold flex items-center justify-center gap-3
                 shadow-lg hover:shadow-xl transform hover:scale-105 transition duration-300 ease-in-out">
            🚪 <span>Login</span>
        </button>

    </form>

</body>

</html>
