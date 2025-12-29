<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
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

<body
    class="transition-colors duration-300 bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">

    <div class="flex min-h-screen">

        <!-- Sidebar -->
        <aside
            class="w-72 bg-gradient-to-b from-indigo-600 via-purple-600 to-pink-600 dark:from-gray-800 dark:to-gray-900
                      text-white flex flex-col shadow-xl">

            <!-- Logo -->
            <div
                class="h-16 flex items-center px-6 font-extrabold text-lg tracking-wide border-b border-indigo-400 dark:border-gray-700">
                ⚡ Admin Panel
            </div>

            <!-- Navigation -->
            <nav class="p-4 space-y-2">
                <a href="{{ route('admin.users.pending') }}"
                    class="block px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 transition font-medium">
                    👥 Registration Requests
                </a>

                <a href="{{ route('admin.users.index') }}"
                    class="block px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 transition font-medium">
                    👤 Users
                </a>

                <a href="{{ route('admin.users.banned') }}"
                    class="block px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 transition font-medium">
                    🚫 Banned Users
                </a>
            </nav>

            <!-- Admin Info -->
            <!-- Admin Info -->
            <a href="{{ route('admin.users.details', auth()->id()) }}"
                class="mt-auto border-t border-indigo-400 dark:border-gray-700 px-6 py-5 flex flex-col items-center text-center hover:bg-white/10 transition">

                @php
                    $admin = auth()->user();
                    $adminAvatar = $admin->profile->avatar ?? null;
                    $adminAvatarUrl = $adminAvatar ? asset('storage/' . $adminAvatar) : null;
                @endphp

                @if ($adminAvatarUrl)
                    <div class="relative">
                        <img src="{{ $adminAvatarUrl }}" alt="Admin Avatar"
                            class="w-16 h-16 rounded-full object-cover border-2 border-white shadow">
                        <span
                            class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></span>
                    </div>
                @else
                    <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center text-2xl shadow">
                        👤
                    </div>
                @endif

                <div class="mt-3">
                    <p class="font-semibold text-white text-sm">{{ $admin->name }}</p>
                    <p class="text-xs text-gray-200">Online</p>
                </div>
            </a>
            <!-- Logout -->
            <form id="logout-form-{{ auth()->id() }}" method="POST" action="{{ route('admin.logout') }}"
                class="p-4">
                @csrf
                <button type="button" onclick="document.getElementById('logout-modal').classList.remove('hidden')"
                    class="w-full flex items-center justify-center gap-2
               px-5 py-3 rounded-xl
               bg-gradient-to-r from-red-500 via-pink-600 to-purple-600
               text-white font-semibold
               shadow-lg hover:shadow-xl
               transform hover:scale-105
               transition duration-300 ease-in-out">
                    🚪 Logout
                </button>
            </form>

            <!-- Logout Confirmation Modal -->
            <div id="logout-modal"
                class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 w-[90%] max-w-md relative">
                    <h3 class="text-xl font-bold text-red-600 dark:text-red-400 mb-4">
                        ❓ Are you sure you want to logout?
                    </h3>
                    <p class="text-gray-700 dark:text-gray-300 mb-6">
                        If you logout now, you will need to login again to access the dashboard.
                    </p>

                    <div class="flex justify-end gap-3">
                        <!-- Cancel -->
                        <button type="button" onclick="document.getElementById('logout-modal').classList.add('hidden')"
                            class="px-4 py-2 rounded-lg bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 hover:bg-gray-400 dark:hover:bg-gray-500 transition">
                            Cancel
                        </button>

                        <!-- Confirm Logout -->
                        <button type="button"
                            onclick="document.getElementById('logout-form-{{ auth()->id() }}').submit()"
                            class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition shadow">
                            Yes, Logout
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <main class="flex-1 p-8 text-gray-900 dark:text-gray-100">

            <!-- Top Bar -->
            <div class="flex justify-between items-center mb-8">
                <h1
                    class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600">
                    Administration Dashboard
                </h1>

                <button id="theme-toggle"
                    class="w-12 h-12 rounded-full bg-gradient-to-r from-indigo-600 to-pink-600 text-white
                               flex items-center justify-center hover:opacity-80 shadow-lg transition">
                    <span id="theme-icon">🌙</span>
                </button>
            </div>

            <!-- Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-indigo-100 dark:border-gray-700">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white">
                        <tr>
                            <th class="p-4">Name</th>
                            <th class="p-4">Email</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($users as $user)
                            <tr
                                class="border-b border-gray-200 dark:border-gray-700 hover:bg-indigo-50 dark:hover:bg-gray-700 transition">
                                <td class="p-4 font-medium">{{ $user->name }}</td>
                                <td class="p-4">{{ $user->email }}</td>

                                <!-- Status -->
                                <td class="p-4">
                                    @if ($user->isbanned)
                                        <span
                                            class="px-2 py-1 rounded bg-red-100 text-red-600 font-semibold">Banned</span>
                                    @elseif($user->verified)
                                        <span
                                            class="px-2 py-1 rounded bg-green-100 text-green-600 font-semibold">Active</span>
                                    @else
                                        <span
                                            class="px-2 py-1 rounded bg-yellow-100 text-yellow-600 font-semibold">Pending</span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="p-4 flex gap-2">
                                    {{-- Approve (فقط في طلبات التسجيل) --}}
                                    @if ($section === 'pending')
                                        <form method="POST" action="{{ route('admin.users.approve', $user->id) }}">
                                            @csrf
                                            <button
                                                class="px-3 py-1 rounded bg-green-600 text-white hover:bg-green-700 transition shadow">
                                                Approve
                                            </button>
                                        </form>

                                        {{-- Reject --}}
                                        <form method="POST" action="{{ route('admin.users.reject', $user->id) }}">
                                            @csrf
                                            <button
                                                class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700 transition shadow">
                                                Reject
                                            </button>
                                        </form>
                                    @endif

                                    {{-- View Details (موجود بكل الحالات) --}}
                                    <a href="{{ route('admin.users.details', $user->id) }}"
                                        class="px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700 transition shadow">
                                        View Details
                                    </a>

                                    {{-- Ban --}}
                                    @if ($section === 'users' && !$user->isbanned)
                                        <!-- Ban Button -->
                                        <button type="button"
                                            onclick="document.getElementById('ban-modal-{{ $user->id }}').classList.remove('hidden')"
                                            class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700 transition shadow">
                                            Ban
                                        </button>

                                        <!-- Ban Confirmation Modal -->
                                        <div id="ban-modal-{{ $user->id }}"
                                            class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
                                            <div
                                                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 w-[90%] max-w-lg relative">
                                                <h3
                                                    class="text-xl font-bold text-red-600 dark:text-red-400 mb-4 flex items-center gap-2">
                                                    ⚠️ Are you sure you want to ban {{ $user->name }}?
                                                </h3>
                                                <p class="text-gray-700 dark:text-gray-300 mb-6">
                                                    Once banned, this user will lose access until you unban them.
                                                </p>

                                                <!-- Ban Form -->
                                                <form method="POST" action="{{ route('admin.users.ban', $user->id) }}"
                                                    class="space-y-4">
                                                    @csrf
                                                    <div class="flex flex-col md:flex-row gap-3">
                                                        <select name="ban_type"
                                                            class="flex-1 text-sm rounded bg-indigo-600 text-white px-2 py-2"
                                                            required>
                                                            <option value="">Select type</option>
                                                            <option value="Permanent">Permanent</option>
                                                            <option value="Temporary">Temporary</option>
                                                        </select>
                                                        <input type="date" name="banned_until"
                                                            class="flex-1 text-sm rounded bg-indigo-600 text-white px-2 py-2">
                                                    </div>
                                                    <input type="text" name="reason" placeholder="Reason"
                                                        class="w-full text-sm rounded bg-indigo-600 text-white px-3 py-2"
                                                        required>

                                                    <div class="flex justify-end gap-3 mt-4">
                                                        <!-- Cancel -->
                                                        <button type="button"
                                                            onclick="document.getElementById('ban-modal-{{ $user->id }}').classList.add('hidden')"
                                                            class="px-4 py-2 rounded-lg bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 hover:bg-gray-400 dark:hover:bg-gray-500 transition">
                                                            Cancel
                                                        </button>

                                                        <!-- Confirm Ban -->
                                                        <button type="submit"
                                                            class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition shadow">
                                                            Yes, Ban User
                                                        </button>
                                                    </div>
                                                </form>

                                                <!-- Close Button -->
                                                <button type="button"
                                                    onclick="document.getElementById('ban-modal-{{ $user->id }}').classList.add('hidden')"
                                                    class="absolute top-3 right-3 text-gray-500 hover:text-red-500 text-xl font-bold">
                                                    ✖
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Unban + View Reasons --}}
                                    @if ($section === 'banned' && $user->isbanned)
                                        {{-- زر Unban --}}
                                        <form method="POST" action="{{ route('admin.users.unban', $user->id) }}">
                                            @csrf
                                            <button
                                                class="px-3 py-1 rounded bg-purple-600 text-white hover:bg-purple-700 transition shadow">
                                                Unban
                                            </button>
                                        </form>

                                        {{-- View Reasons --}}
                                        <button type="button"
                                            onclick="document.getElementById('modal-{{ $user->id }}').classList.remove('hidden')"
                                            class="px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700 transition shadow">
                                            View Reasons
                                        </button>

                                        {{-- Modal --}}
                                        @php
                                            $reasons = is_array($user->ban_reasons_history)
                                                ? $user->ban_reasons_history
                                                : [];
                                        @endphp

                                        <div id="modal-{{ $user->id }}"
                                            class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">

                                            <div
                                                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 w-[90%] max-w-xl relative">
                                                <h3
                                                    class="text-xl font-bold text-indigo-700 dark:text-indigo-200 mb-4">
                                                    🚫 Ban Reasons for {{ $user->name }}
                                                </h3>

                                                @if (count($reasons))
                                                    <ul class="space-y-3 text-sm max-h-96 overflow-y-auto pr-2">
                                                        @foreach ($reasons as $reason)
                                                            @php
                                                                $parsed = is_string($reason)
                                                                    ? json_decode($reason, true)
                                                                    : $reason;
                                                            @endphp
                                                            <li
                                                                class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-700 dark:to-gray-600 rounded-xl px-4 py-3 shadow">
                                                                <div class="flex items-center gap-2 mb-2">
                                                                    <span class="text-red-500 text-lg">⚠️</span>
                                                                    <span
                                                                        class="font-semibold text-indigo-700 dark:text-indigo-200">
                                                                        {{ $parsed['reason'] ?? 'Unknown reason' }}
                                                                    </span>
                                                                </div>
                                                                <div class="text-xs text-gray-600 dark:text-gray-300">
                                                                    <p><strong>Date:</strong>
                                                                        {{ $parsed['date'] ?? 'N/A' }}</p>
                                                                    <p><strong>Admin ID:</strong>
                                                                        {{ $parsed['admin'] ?? 'N/A' }}</p>
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <p class="text-sm text-gray-500 dark:text-gray-300">No reasons
                                                        recorded.</p>
                                                @endif

                                                {{-- Close Button --}}
                                                <button
                                                    onclick="document.getElementById('modal-{{ $user->id }}').classList.add('hidden')"
                                                    class="absolute top-3 right-3 text-gray-500 hover:text-red-500 text-xl font-bold">
                                                    ✖
                                                </button>
                                            </div>
                                        </div>

                                        {{-- صندوق عرض الأسباب --}}
                                        @php
                                            $reasons = is_array($user->ban_reasons_history)
                                                ? $user->ban_reasons_history
                                                : [];
                                        @endphp

                                        <div id="reasons-{{ $user->id }}"
                                            class="hidden mt-3 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-700 dark:to-gray-600 rounded-xl shadow-lg p-4 w-72">
                                            <h4
                                                class="text-lg font-semibold text-indigo-700 dark:text-indigo-200 mb-2">
                                                🚫 Ban Reasons
                                            </h4>

                                            @if (count($reasons))
                                                <ul class="space-y-2 text-sm">
                                                    @foreach ($reasons as $reason)
                                                        <li
                                                            class="bg-white dark:bg-gray-800 rounded-lg px-3 py-2 shadow flex items-center gap-2">
                                                            <span class="text-red-500">⚠️</span>
                                                            <span>{{ is_string($reason) ? $reason : json_encode($reason) }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="text-sm text-gray-500 dark:text-gray-300">No reasons
                                                    recorded.
                                                </p>
                                            @endif
                                        </div>
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
