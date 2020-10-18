<div class="card-body">
    @if(count($wallet_transactions) > 0)
    <div class="table-responsive">
        <table class="table table-hover table-bordered table-stripped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @php $i = 0; @endphp
                @forelse ($wallet_transactions as $item)
                    <tr>
                        <td>{{$i+=1}}</td>
                        <td>
                            @if ($item->transaction_type == 1)
                                Credit
                            @else
                                Debit
                            @endif
                        </td>
                        <td>
                            {{ number_format($item->transaction_amount, 2) }}
                        </td>
                        <td>
                            {{$item->created_at->diffForHumans()}}
                        </td>
                        <td>
                            @if ($item->status)
                                <span class="text-success">Successful</span>
                            @else
                                <span class="text-danger">Failed</span>
                            @endif
                        </td>
                        <td>
                            {{$item->transaction_description}}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <h5 class="text-center">Looks like you have not made any wallet transactions yet.</h5>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{$wallet_transactions->links()}}
    </div>
    @else
    <p class="card-text text-center">Looks like you don't have any power transaction history.</p>
    @endif
</div>