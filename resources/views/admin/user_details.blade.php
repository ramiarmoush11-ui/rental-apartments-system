<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Details</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 text-gray-800 dark:text-gray-100 min-h-screen p-8">

  <h1 class="text-4xl font-extrabold mb-10 text-center text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600">
    👤 User Details
  </h1>

  <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8">

    <!-- Profile Card -->
    @php
      $profile = $user['Profile'] ?? null;
      $avatarPath = $profile['Avatar'] ?? null;
      $avatarUrl = $avatarPath ? asset('storage/' . $avatarPath) : null;

      $idPhotoPath = $profile['IdPhoto'] ?? null;
      $idPhotoUrl = $idPhotoPath ? asset('storage/' . $idPhotoPath) : null;
    @endphp

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 space-y-6 border border-indigo-100 dark:border-gray-700">
      <div class="flex flex-col items-center text-center">
        {{-- Avatar --}}
        @if($avatarUrl)
          <img src="{{ $avatarUrl }}"
               alt="Avatar"
               class="w-36 h-36 rounded-full object-cover mb-4 border-4 border-gradient-to-r from-indigo-500 to-pink-500 shadow-lg">
        @else
          <div class="w-36 h-36 rounded-full bg-gradient-to-r from-indigo-400 to-pink-400 flex items-center justify-center text-white text-xl mb-4 shadow-lg">
            No Avatar
          </div>
        @endif

        <h2 class="text-2xl font-bold text-indigo-600 dark:text-indigo-300">
          {{ $profile['FirstName'] ?? 'First name not provided' }}
          {{ $profile['LastName'] ?? 'Last name not provided' }}
        </h2>
      </div>

      {{-- Profile Info --}}
      <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-200">📋 Profile Info</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @foreach([
          'First Name' => $profile['FirstName'] ?? 'Not provided',
          'Last Name' => $profile['LastName'] ?? 'Not provided',
          'Birth Date' => $profile['BirthDate'] ?? 'Not specified'
        ] as $label => $value)
          <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-700 dark:to-gray-600 rounded-lg p-4 shadow">
            <span class="block text-xs text-gray-500 dark:text-gray-300">{{ $label }}</span>
            <span class="block font-semibold text-sm mt-1 text-indigo-700 dark:text-indigo-200">{{ $value }}</span>
          </div>
        @endforeach
      </div>

     {{-- ID Photo --}}
<h3 class="text-lg font-semibold text-slate-700 dark:text-slate-200 mt-6">🪪 Identity Photo</h3>
@if($idPhotoUrl)
  <div class="w-full bg-gradient-to-r from-indigo-50 to-pink-50 dark:from-gray-700 dark:to-gray-600 rounded-xl overflow-hidden shadow-lg">
    <a href="{{ $idPhotoUrl }}" target="_blank">
      <img src="{{ $idPhotoUrl }}"
           alt="ID Photo"
           class="w-full h-auto object-contain cursor-pointer hover:opacity-90 transition">
    </a>
  </div>
@else
  <div class="w-full bg-gradient-to-r from-indigo-400 to-pink-400 rounded-xl flex items-center justify-center text-white text-lg h-48 shadow-lg">
    No ID Photo
  </div>
@endif
    </div>

    <!-- Account Info -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 space-y-4 col-span-2 border border-purple-100 dark:border-gray-700">
      <h3 class="text-lg font-semibold mb-4 text-slate-700 dark:text-slate-200">📄 Account Info</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        @foreach([
          'Name' => $user['Name'],
          'Email' => $user['Email'],
          'Phone' => $user['Phone'],
          'Verified' => $user['Verified'],
          'Role' => $user['Role'],
          'IsBanned' => $user['IsBanned'],
          'BanCount' => $user['BanCount'],
          'BanType' => $user['BanType'],
          'BannedUntil' => $user['BannedUntil'],
          'EmailVerifiedAt' => $user['EmailVerifiedAt'],
          'CreatedAt' => $user['CreatedAt']
        ] as $label => $value)
          <div class="bg-gradient-to-r from-pink-50 to-indigo-50 dark:from-gray-700 dark:to-gray-600 rounded-lg p-4 shadow">
            <span class="block text-xs text-gray-500 dark:text-gray-300">{{ $label }}</span>
            <span class="block font-semibold text-sm mt-1 text-purple-700 dark:text-purple-200">{{ $value }}</span>
          </div>
        @endforeach
      </div>
    </div>

  </div>

  <!-- Back Button -->
  <div class="mt-12 text-center">
    <a href="{{ url()->previous() }}"
       class="inline-block px-6 py-3 rounded-full bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white font-semibold shadow-lg hover:opacity-90 transition">
      ← Back
    </a>
  </div>

</body>
</html>