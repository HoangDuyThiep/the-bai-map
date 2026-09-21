<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tài khoản đang chờ duyệt
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="font-semibold">Đăng ký của bạn đã được ghi nhận.</p>
                    <p class="mt-2 text-gray-600">
                        Vui lòng chờ quản trị viên duyệt tài khoản. Sau khi được duyệt, bạn có thể xem bản đồ và đăng thông tin bán hàng.
                    </p>

                    <form class="mt-6" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-primary-button>Đăng xuất</x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
