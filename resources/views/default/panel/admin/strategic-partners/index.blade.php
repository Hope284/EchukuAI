@extends('panel.layout.app')
@section('title', __('Strategic Partner Management'))

@section('content')
    <div class="py-10">
        <div class="container-xl">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="mb-0">{{ __('Strategic Partner Management') }}</h1>
                <a class="btn btn-primary" href="{{ route('dashboard.admin.strategic-partners.create') }}">{{ __('Create Strategic Partner') }}</a>
            </div>
            <x-card>
                <x-table>
                    <x-slot:head>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Country') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Children') }}</th>
                            <th></th>
                        </tr>
                    </x-slot:head>
                    <x-slot:body>
                        @forelse ($partners as $partner)
                            <tr>
                                <td>{{ $partner->name }}</td>
                                <td>{{ $partner->email }}</td>
                                <td>{{ $partner->country }}</td>
                                <td>{{ __(ucfirst($partner->status)) }}</td>
                                <td>{{ $partner->children_count }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('dashboard.admin.strategic-partners.show', $partner) }}">{{ __('Manage') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">{{ __('No Strategic Partners yet.') }}</td></tr>
                        @endforelse
                    </x-slot:body>
                </x-table>
                <div class="mt-4">{{ $partners->links() }}</div>
            </x-card>
        </div>
    </div>
@endsection
