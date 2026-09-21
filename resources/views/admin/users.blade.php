<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Quản lý thành viên
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 sm:rounded-lg">
                    {{ session('status') }}
                </div>
            @endif

            <section class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-lg text-gray-900">Đang chờ duyệt</h3>

                    <div class="mt-4 divide-y divide-gray-100">
                        @forelse ($pendingUsers as $user)
                            <div class="py-4 flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $user->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                                </div>

                                <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-primary-button>Duyệt</x-primary-button>
                                </form>
                            </div>
                        @empty
                            <p class="mt-4 text-gray-500">Không có tài khoản nào đang chờ duyệt.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-lg text-gray-900">Thành viên đã active</h3>

                    <div class="mt-4 divide-y divide-gray-100">
                        @foreach ($activeUsers as $user)
                            <div class="py-4">
                                <p class="font-medium text-gray-900">
                                    {{ $user->name }}
                                    @if ($user->role === 'admin')
                                        <span class="ml-2 text-xs text-emerald-700">Admin</span>
                                    @endif
                                </p>
                                <p class="text-sm text-gray-500">{{ $user->email }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
