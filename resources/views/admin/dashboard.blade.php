<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { darkMode: 'class' }
  </script>

  <!-- Prevent flash on load -->
  <script>
    (function() {
      const stored = localStorage.getItem('theme');
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      const shouldDark = stored ? stored === 'dark' : prefersDark;
      document.documentElement.classList.toggle('dark', shouldDark);
    })();
  </script>
</head>

<body class="transition-colors duration-300">

<div class="flex min-h-screen">

  <!-- Sidebar -->
  <aside class="w-72 bg-gray-200 dark:bg-[#0f172a]
                text-gray-900 dark:text-gray-200
                border-r border-gray-300 dark:border-slate-800 flex flex-col">

    <div class="h-16 flex items-center px-6 font-bold border-b
                border-gray-300 dark:border-slate-800">
      ⚡ Admin Panel
    </div>

    <nav class="p-4 space-y-2">
      <a href="{{ route('admin.users.pending') }}"
         class="block px-4 py-2 rounded hover:bg-slate-700 transition">
        👥 Registration Requests
      </a>

      <a href="{{ route('admin.users.index') }}"
         class="block px-4 py-2 rounded hover:bg-slate-700 transition">
        👤 Users
      </a>
    </nav>

    <div class="mt-auto border-t border-gray-300 dark:border-slate-800 p-4 flex items-center gap-3">
      <div class="w-9 h-9 rounded-full bg-slate-600 flex items-center justify-center">👤</div>
      <div class="text-sm">
        <p class="font-medium">Admin</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">Administrator</p>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <main class="flex-1 p-6 bg-gray-100 dark:bg-[#020617]
               text-gray-900 dark:text-gray-100">

    <!-- Top Bar -->
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Welcome Admin 👋</h1>

      <button id="theme-toggle"
              class="w-10 h-10 rounded-full bg-slate-800 text-white
                     flex items-center justify-center hover:bg-slate-700">
        <span id="theme-icon">🌙</span>
      </button>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-[#0f172a] rounded-xl shadow overflow-hidden">

      <table class="w-full text-left text-sm">
        <thead class="bg-slate-800 text-gray-300">
          <tr>
            <th class="p-4">Name</th>
            <th class="p-4">Email</th>
            <th class="p-4">Status</th>
            <th class="p-4">Actions</th>
          </tr>
        </thead>

        <tbody>
        @foreach($users as $user)
          <tr class="border-b border-slate-200 dark:border-slate-700
                     hover:bg-slate-100 dark:hover:bg-slate-800">

            <td class="p-4">{{ $user->name }}</td>
            <td class="p-4">{{ $user->email }}</td>

            <!-- Status -->
            <td class="p-4">
              @if($user->isbanned)
                <span class="text-red-500">Banned</span>
              @elseif($user->verified)
                <span class="text-green-500">Active</span>
              @else
                <span class="text-yellow-400">Pending</span>
              @endif
            </td>

            <!-- Actions -->
            <td class="p-4 flex gap-2">

              {{-- Approve --}}
              @if(!$user->verified)
                <form method="POST" action="{{ route('admin.users.approve', $user->id) }}">
                  @csrf
                  <button class="px-3 py-1 rounded bg-green-600 text-white">
                    Approve
                  </button>
                </form>
              @endif

              {{-- Ban --}}
              @if(!$user->isbanned)
                <form method="POST" action="{{ route('admin.users.ban', $user->id) }}">
                  @csrf
                  <input type="hidden" name="ban_type" value="Permanent">
                  <button class="px-3 py-1 rounded bg-red-600 text-white">
                    Ban
                  </button>
                </form>
              @endif

              {{-- Unban --}}
              @if($user->isbanned)
                <form method="POST" action="{{ route('admin.users.unban', $user->id) }}">
                  @csrf
                  <button class="px-3 py-1 rounded bg-indigo-600 text-white">
                    Unban
                  </button>
                </form>
              @endif

            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </main>
</div>

<!-- Dark mode -->
<script>
  const html = document.documentElement;
  const icon = document.getElementById('theme-icon');

  icon.textContent = html.classList.contains('dark') ? '☀️' : '🌙';

  document.getElementById('theme-toggle').onclick = () => {
    const dark = html.classList.toggle('dark');
    localStorage.setItem('theme', dark ? 'dark' : 'light');
    icon.textContent = dark ? '☀️' : '🌙';
  };
</script>

</body>
</html>
