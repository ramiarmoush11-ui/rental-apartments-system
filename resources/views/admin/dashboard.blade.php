<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0b1220] text-gray-200">

<div class="flex min-h-screen">

  <!-- Sidebar -->
  <aside class="w-72 bg-[#0f172a] border-r border-slate-800 flex flex-col">

    <!-- Logo -->
    <div class="h-16 flex items-center gap-3 px-6 border-b border-slate-800">
      <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center font-bold">
        ⚡
      </div>
      <span class="font-semibold text-lg">Dashboard</span>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-1 text-sm">

      <a class="flex items-center justify-between px-4 py-2 rounded-lg bg-slate-800 text-white">
        <div class="flex items-center gap-3">
          🏠 <span>Dashboard</span>
        </div>
        <span class="text-xs bg-indigo-600 px-2 py-0.5 rounded-full">5</span>
      </a>

      <a class="flex items-center justify-between px-4 py-2 rounded-lg hover:bg-slate-800 transition">
        <div class="flex items-center gap-3">
          👥 <span>Team</span>
        </div>
      </a>

      <a class="flex items-center justify-between px-4 py-2 rounded-lg hover:bg-slate-800 transition">
        <div class="flex items-center gap-3">
          📁 <span>Projects</span>
        </div>
        <span class="text-xs bg-slate-700 px-2 py-0.5 rounded-full">12</span>
      </a>

      <a class="flex items-center justify-between px-4 py-2 rounded-lg hover:bg-slate-800 transition">
        <div class="flex items-center gap-3">
          📅 <span>Calendar</span>
        </div>
        <span class="text-xs bg-slate-700 px-2 py-0.5 rounded-full">20+</span>
      </a>

      <a class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-800 transition">
        📄 <span>Documents</span>
      </a>

      <a class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-800 transition">
        📊 <span>Reports</span>
      </a>

      <!-- Teams -->
      <div class="mt-6 text-xs uppercase tracking-wide text-gray-500 px-4">
        Your teams
      </div>

      <div class="mt-2 space-y-1">
        <a class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-800">
          <span class="w-6 h-6 rounded bg-slate-700 flex items-center justify-center text-xs">H</span>
          Heroicons
        </a>
        <a class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-800">
          <span class="w-6 h-6 rounded bg-slate-700 flex items-center justify-center text-xs">T</span>
          Tailwind Labs
        </a>
        <a class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-800">
          <span class="w-6 h-6 rounded bg-slate-700 flex items-center justify-center text-xs">W</span>
          Workcation
        </a>
      </div>

    </nav>

    <!-- User -->
    <div class="border-t border-slate-800 p-4 flex items-center gap-3">
      <div class="w-9 h-9 rounded-full bg-slate-600 flex items-center justify-center">
        👤
      </div>
      <div class="text-sm">
        <p class="font-medium">Tom Cook</p>
        <p class="text-xs text-gray-400">Admin</p>
      </div>
    </div>

  </aside>

  <!-- Main Content -->
  <main class="flex-1 bg-gradient-to-br from-[#0b1220] to-[#020617]">
    <!-- فاضي متل الصورة -->
  </main>

</div>

</body>
</html>
