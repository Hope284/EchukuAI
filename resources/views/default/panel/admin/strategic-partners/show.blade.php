@extends('panel.layout.app')
@section('title', __('Strategic Partner Details'))

@section('content')
    <div class="py-10">
        <div class="container-xl">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="mb-1">{{ $partner->name }}</h1>
                    <p class="mb-0 text-muted">{{ __('Strategic Partner') }} &middot; {{ __(ucfirst($partner->status)) }} &middot; {{ $partner->country }}</p>
                </div>
                <a class="btn btn-outline-primary" href="{{ route('dashboard.admin.strategic-partners.edit', $partner) }}">{{ __('Edit') }}</a>
            </div>

            <x-card class="mb-6">
                <label class="form-label">{{ __('Strategic Partner Referral URL') }}</label>
                <input class="form-control" readonly value="{{ $referralUrl }}" onclick="this.select(); navigator.clipboard?.writeText(this.value); toastr.success('{{ __('Strategic Partner URL copied.') }}');">
            </x-card>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <x-card><p class="mb-1 text-muted">{{ __('Child Affiliates') }}</p><h3>{{ $children->count() }}</h3></x-card>
                <x-card><p class="mb-1 text-muted">{{ __('Available Balance') }}</p><h3>{{ currency()->symbol }}{{ number_format($availableBalance, 2) }}</h3></x-card>
                <x-card><p class="mb-1 text-muted">{{ __('Commission Rate') }}</p><h3>{{ number_format($partner->commission_rate, 2) }}%</h3></x-card>
                <x-card><p class="mb-1 text-muted">{{ __('Linked User') }}</p><h3 class="text-base">{{ $partner->user?->email ?: '-' }}</h3></x-card>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
                <x-card>
                    <h3 class="mb-4">{{ __('Child Affiliates') }}</h3>
                    <x-table>
                        <x-slot:head>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Phone') }}</th>
                                <th>{{ __('Users Count') }}</th>
                                <th>{{ __('Last 30 Days') }}</th>
                                <th>{{ __('Total') }}</th>
                            </tr>
                        </x-slot:head>
                        <x-slot:body>
                            @forelse ($children as $summary)
                                <tr>
                                    <td>{{ $summary['user']?->fullName() ?: '-' }}</td>
                                    <td>{{ $summary['user']?->phone ?: '-' }}</td>
                                    <td>{{ $summary['referred_users_count'] }}</td>
                                    <td>{{ currency()->symbol }}{{ number_format($summary['last_30_days'], 2) }}</td>
                                    <td>{{ currency()->symbol }}{{ number_format($summary['total'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5">{{ __('No child affiliates yet.') }}</td></tr>
                            @endforelse
                        </x-slot:body>
                    </x-table>
                </x-card>

                <x-card>
                    <h3 class="mb-4">{{ __('Payment Gateway Rules') }}</h3>
                    <form method="POST" action="{{ route('dashboard.admin.strategic-partners.payment-gateways.save', $partner) }}">
                        @csrf
                        @foreach ($gateways as $gateway)
                            @php($rule = $partner->paymentGateways->firstWhere('gateway_key', $gateway->code))
                            <div class="mb-3 rounded border p-3">
                                <label class="form-check">
                                    <input type="hidden" name="gateways[{{ $loop->index }}][gateway_key]" value="{{ $gateway->code }}">
                                    <input class="form-check-input" type="checkbox" name="gateways[{{ $loop->index }}][is_enabled]" value="1" @checked($rule?->is_enabled ?? false)>
                                    <span class="form-check-label">{{ $gateway->title ?: $gateway->code }}</span>
                                </label>
                                <div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-2">
                                    <input class="form-control" name="gateways[{{ $loop->index }}][country]" placeholder="{{ __('Country, or * for all') }}" value="{{ $rule?->country ?: '*' }}">
                                    <input class="form-control" name="gateways[{{ $loop->index }}][currency]" placeholder="{{ __('Currency code') }}" value="{{ $rule?->currency }}">
                                </div>
                            </div>
                        @endforeach
                        <button class="btn btn-primary">{{ __('Save Gateway Rules') }}</button>
                    </form>
                </x-card>
            </div>

            <x-card class="mt-6">
                <h3 class="mb-4">{{ __('Withdrawal Requests') }}</h3>
                <x-table>
                    <x-slot:head>
                        <tr>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Requested') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </x-slot:head>
                    <x-slot:body>
                        @forelse ($withdrawals as $withdrawal)
                            <tr>
                                <td>{{ $withdrawal->currency }} {{ number_format($withdrawal->amount, 2) }}</td>
                                <td>{{ __(ucfirst($withdrawal->status)) }}</td>
                                <td>{{ $withdrawal->requested_at?->toDayDateTimeString() ?: $withdrawal->created_at?->toDayDateTimeString() }}</td>
                                <td>
                                    <form class="flex gap-2" method="POST" action="{{ route('dashboard.admin.strategic-partners.withdrawals.update', $withdrawal) }}">
                                        @csrf
                                        <select class="form-select" name="status">
                                            @foreach ([\App\Models\ParentAffiliateWithdrawal::STATUS_APPROVED, \App\Models\ParentAffiliateWithdrawal::STATUS_PAID, \App\Models\ParentAffiliateWithdrawal::STATUS_REJECTED] as $status)
                                                <option value="{{ $status }}" @selected($withdrawal->status === $status)>{{ __(ucfirst($status)) }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-primary">{{ __('Update') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">{{ __('No withdrawals yet.') }}</td></tr>
                        @endforelse
                    </x-slot:body>
                </x-table>
            </x-card>
        </div>
    </div>
@endsection
