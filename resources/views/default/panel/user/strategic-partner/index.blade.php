@extends('panel.layout.app')
@section('title', __('Strategic Partner'))

@section('content')
    <div class="py-10">
        <div class="container-xl">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="mb-1 text-2xl font-semibold">{{ __('Strategic Partner Dashboard') }}</h1>
                    <p class="mb-0 text-muted">{{ $partner->name }} &middot; {{ $partner->country }}</p>
                </div>
                <input
                    class="form-control max-w-xl"
                    readonly
                    value="{{ $referralUrl }}"
                    onclick="this.select(); navigator.clipboard?.writeText(this.value); toastr.success('{{ __('Strategic Partner URL copied.') }}');"
                >
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <x-card><p class="mb-1 text-muted">{{ __('Child Affiliates') }}</p><h3>{{ $children->count() }}</h3></x-card>
                <x-card><p class="mb-1 text-muted">{{ __('Total Users Generated') }}</p><h3>{{ $totalUsersUnderChildren }}</h3></x-card>
                <x-card><p class="mb-1 text-muted">{{ __('Total Earnings') }}</p><h3>{{ currency()->symbol }}{{ number_format($totalEarned, 2) }}</h3></x-card>
                <x-card><p class="mb-1 text-muted">{{ __('Available Balance') }}</p><h3>{{ currency()->symbol }}{{ number_format($availableBalance, 2) }}</h3></x-card>
            </div>

            <div class="mt-6 flex flex-wrap gap-2">
                <a @class(['btn', 'btn-primary' => $tab === 'overview']) href="{{ route('dashboard.user.strategic-partner.index') }}">{{ __('Overview') }}</a>
                <a @class(['btn', 'btn-primary' => $tab === 'children']) href="{{ route('dashboard.user.strategic-partner.children') }}">{{ __('Child Affiliates') }}</a>
                <a @class(['btn', 'btn-primary' => $tab === 'earnings']) href="{{ route('dashboard.user.strategic-partner.earnings') }}">{{ __('Partner Earnings') }}</a>
                <a @class(['btn', 'btn-primary' => $tab === 'withdrawals']) href="{{ route('dashboard.user.strategic-partner.withdrawals') }}">{{ __('Withdrawals') }}</a>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
                <x-card class="xl:col-span-2">
                    <h3 class="mb-4">{{ __('Child Affiliate Performance') }}</h3>
                    <x-table>
                        <x-slot:head>
                            <tr>
                                <th>{{ __('Child Affiliate') }}</th>
                                <th>{{ __('Phone') }}</th>
                                <th>{{ __('Users Generated') }}</th>
                                <th>{{ __('Last 30 Days') }}</th>
                                <th>{{ __('Total') }}</th>
                            </tr>
                        </x-slot:head>
                        <x-slot:body>
                            @forelse ($children as $summary)
                                <tr>
                                    <td>{{ $summary['user']?->fullName() ?: __('Unknown') }}</td>
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
                    <h3 class="mb-4">{{ __('Request Withdrawal') }}</h3>
                    <form method="POST" action="{{ route('dashboard.user.strategic-partner.withdrawals.request') }}">
                        @csrf
                        <label class="form-label">{{ __('Amount') }}</label>
                        <input class="form-control mb-3" name="amount" type="number" min="1" max="{{ $availableBalance }}" step="0.01" required>
                        <button class="btn btn-primary w-full">{{ __('Request Cashout') }}</button>
                    </form>

                    <hr class="my-5">

                    <form method="POST" action="{{ route('dashboard.user.strategic-partner.profile.update') }}">
                        @csrf
                        <h4 class="mb-3">{{ __('Payout Details') }}</h4>
                        <input class="form-control mb-2" name="preferred_payout_method" placeholder="{{ __('Payout method') }}" value="{{ $partner->preferred_payout_method }}">
                        <input class="form-control mb-2" name="payout_account_name" placeholder="{{ __('Account name') }}" value="{{ data_get($partner->payout_details, 'account_name') }}">
                        <input class="form-control mb-2" name="payout_account_number" placeholder="{{ __('Account number') }}" value="{{ data_get($partner->payout_details, 'account_number') }}">
                        <input class="form-control mb-2" name="payout_bank_name" placeholder="{{ __('Bank name') }}" value="{{ data_get($partner->payout_details, 'bank_name') }}">
                        <textarea class="form-control mb-3" name="payout_extra" placeholder="{{ __('Additional payout details') }}">{{ data_get($partner->payout_details, 'extra') }}</textarea>
                        <button class="btn btn-outline-primary w-full">{{ __('Save Payout Details') }}</button>
                    </form>
                </x-card>
            </div>

            <x-card class="mt-6">
                <h3 class="mb-4">{{ __('Withdrawal History') }}</h3>
                <x-table>
                    <x-slot:head>
                        <tr>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Requested') }}</th>
                        </tr>
                    </x-slot:head>
                    <x-slot:body>
                        @forelse ($withdrawals as $withdrawal)
                            <tr>
                                <td>{{ $withdrawal->currency }} {{ number_format($withdrawal->amount, 2) }}</td>
                                <td>{{ __(ucfirst($withdrawal->status)) }}</td>
                                <td>{{ $withdrawal->requested_at?->toDayDateTimeString() ?: $withdrawal->created_at?->toDayDateTimeString() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">{{ __('No withdrawal requests yet.') }}</td></tr>
                        @endforelse
                    </x-slot:body>
                </x-table>
            </x-card>
        </div>
    </div>
@endsection
