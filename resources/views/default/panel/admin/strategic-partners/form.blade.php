@extends('panel.layout.app')
@section('title', $partner->exists ? __('Edit Strategic Partner') : __('Create Strategic Partner'))

@section('content')
    <div class="py-10">
        <div class="container-xl max-w-3xl">
            <x-card>
                <h1 class="mb-6">{{ $partner->exists ? __('Edit Strategic Partner') : __('Create Strategic Partner') }}</h1>
                <form method="POST" action="{{ $partner->exists ? route('dashboard.admin.strategic-partners.update', $partner) : route('dashboard.admin.strategic-partners.store') }}">
                    @csrf
                    @if ($partner->exists)
                        @method('PUT')
                    @endif
                    <label class="form-label">{{ __('Linked User') }}</label>
                    <select class="form-select mb-3" name="user_id">
                        <option value="">{{ __('No linked user') }}</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((int) old('user_id', $partner->user_id) === $user->id)>{{ $user->email }} - {{ $user->fullName() }}</option>
                        @endforeach
                    </select>
                    <input class="form-control mb-3" name="name" placeholder="{{ __('Full name') }}" value="{{ old('name', $partner->name) }}" required>
                    <input class="form-control mb-3" name="email" type="email" placeholder="{{ __('Email') }}" value="{{ old('email', $partner->email) }}" required>
                    <input class="form-control mb-3" name="phone" placeholder="{{ __('Phone') }}" value="{{ old('phone', $partner->phone) }}">
                    <input class="form-control mb-3" name="country" placeholder="{{ __('Country') }}" value="{{ old('country', $partner->country) }}" required>
                    <input class="form-control mb-3" name="state" placeholder="{{ __('State/region') }}" value="{{ old('state', $partner->state) }}">
                    <input class="form-control mb-3" name="company_name" placeholder="{{ __('Company or organization') }}" value="{{ old('company_name', $partner->company_name) }}">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="form-label">{{ __('Status') }}</label>
                            <select class="form-select mb-3" name="status" required>
                                @foreach ([\App\Models\ParentAffiliate::STATUS_PENDING, \App\Models\ParentAffiliate::STATUS_APPROVED, \App\Models\ParentAffiliate::STATUS_SUSPENDED] as $status)
                                    <option value="{{ $status }}" @selected(old('status', $partner->status) === $status)>{{ __(ucfirst($status)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">{{ __('Commission rate') }}</label>
                            <input class="form-control mb-3" name="commission_rate" type="number" min="0" max="100" step="0.0001" value="{{ old('commission_rate', $partner->commission_rate ?: 20) }}" required>
                        </div>
                    </div>
                    <input class="form-control mb-3" name="preferred_payout_method" placeholder="{{ __('Preferred payout method') }}" value="{{ old('preferred_payout_method', $partner->preferred_payout_method) }}">
                    <input class="form-control mb-3" name="payout_account_name" placeholder="{{ __('Payout account name') }}" value="{{ old('payout_account_name', data_get($partner->payout_details, 'account_name')) }}">
                    <input class="form-control mb-3" name="payout_account_number" placeholder="{{ __('Payout account number') }}" value="{{ old('payout_account_number', data_get($partner->payout_details, 'account_number')) }}">
                    <input class="form-control mb-3" name="payout_bank_name" placeholder="{{ __('Payout bank name') }}" value="{{ old('payout_bank_name', data_get($partner->payout_details, 'bank_name')) }}">
                    <textarea class="form-control mb-4" name="payout_extra" placeholder="{{ __('Additional payout details') }}">{{ old('payout_extra', data_get($partner->payout_details, 'extra')) }}</textarea>
                    <button class="btn btn-primary">{{ __('Save Strategic Partner') }}</button>
                </form>
            </x-card>
        </div>
    </div>
@endsection
