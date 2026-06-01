{{--<div class="col-md-12 mx-auto">--}}
{{--    <x-table--}}
{{--            class="table"--}}
{{--            id="userTable"--}}
{{--            variant="plain"--}}
{{--    >--}}
{{--        <x-slot:head>--}}
{{--            <tr>--}}
{{--                <th>--}}
{{--                    {{ __('Full Name') }}--}}
{{--                </th>--}}
{{--                <th>--}}
{{--                    {{ __('Affiliate Status') }}--}}
{{--                </th>--}}
{{--            </tr>--}}
{{--        </x-slot:head>--}}

{{--        <x-slot:body>--}}
{{--            @forelse ($users as $entry)--}}
{{--                --}}{{-- flex  --}}
{{--                <tr>--}}
{{--                    <td class="sort-id">{{ $entry->name . ' ' . $entry->surname }}--}}
{{--                    </td>--}}
{{--                    <td>--}}
{{--                        <button--}}
{{--                                class="btn btn-status-update {{ $entry->affiliate_status == 1 ? 'btn-success' : 'btn-danger' }}"--}}
{{--                                data-id="{{ $entry->id }}"--}}
{{--                                data-status="{{ $entry->affiliate_status == 1 ? 0 : 1 }}"--}}
{{--                        > {{ $entry->affiliate_status == 1 ? 'Active' : 'Passive' }} </button>--}}
{{--                    </td>--}}
{{--                </tr>--}}
{{--            @empty--}}
{{--                <tr>--}}
{{--                    <td--}}
{{--                            class="text-center"--}}
{{--                            colspan="4"--}}
{{--                    >--}}
{{--                        {{ __('You have no affiliate users') }}--}}
{{--                    </td>--}}
{{--                </tr>--}}
{{--            @endforelse--}}
{{--        </x-slot:body>--}}
{{--    </x-table>--}}
{{--    <div--}}
{{--        class="flex items-center border-b-0 border-l-0 border-r-0 border-t border-solid border-[--tblr-border-color] px-[--tblr-card-cap-padding-x] py-[--tblr-card-cap-padding-y] [&_.rounded-md]:rounded-full">--}}
{{--    <div--}}
{{--                class="m-0 ms-auto p-0"--}}
{{--                id="paginationLinks"--}}
{{--        >{{ $users->links() }}</div>--}}
{{--    </div>--}}
{{--</div>--}}

<td>
    <button
            class="btn btn-status-update"
            data-id="{{ $user->id }}"
            data-status="{{ $user->affiliate_status == 1 ? 0 : 1 }}"
            style="padding: 5px 10px; border-radius: 10px; color:white; {{ $user->affiliate_status == 1 ? 'background-color:green;' : 'background-color:tomato;' }}"
    > {{ $user->affiliate_status == 1 ? 'Active' : 'Passive' }} </button>
</td>
