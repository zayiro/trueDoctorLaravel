<x-admin-layout>
    @php
    print_r(auth()->user()->role);
    @endphp
    @if(auth()->user()->role == 'doctor')
        @include('admin.dashboard-partner')
    @elseif (auth()->user()->role == 'admin')
        @include('admin.dashboard-administrator')
    @else
        @include('admin.dashboard-patient')        
    @endif
    
</x-admin-layout>