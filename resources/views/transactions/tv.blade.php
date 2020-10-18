<div class="card-body">
    @if(count($tvTransactions) > 0)
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Transaction ID</th>
                    <th scope="col">Status</th>
                    <th scope="col">Receiver</th>
                    <th scope="col">Bundle</th>
                    <th scope="col">Amount (₦)</th>
                    <th scope="col">Date</th>
                </tr>
            </thead>
            <tbody>
                @php $counter = 0; @endphp
                @foreach($tvTransactions as $tvTransaction)
                <tr>
                    <th scope="row">{{$counter+=1}}</th>
                    <td>{{substr($tvTransaction->transaction_id, -12)}}</td>
                    <td>
                        @if($tvTransaction->status == 0)
                        <span class="badge badge-warning">Pending</span>
                        @elseif($tvTransaction->status == 1)
                        <span class="badge badge-info">In-progress</span>
                        @elseif($tvTransaction->status == 2)
                        <span class="badge badge-success">Fulfilled</span>
                        @elseif($tvTransaction->status == 3)
                        <span class="badge badge-danger">Failed</span>
                        @elseif($tvTransaction->status == 4)
                        <span class="badge badge-primary">Re-try</span>
                        @endif
                    </td>
                    <td>{{$tvTransaction->smartcard_num}}</td>
                    <td>{{$tvTransaction->bundle->name}}</td>
                    <td>{{number_format((float)$tvTransaction->amount, 2, '.', '')}}</td>
                    <td>{{$tvTransaction->date_created->diffForHumans()}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{$tvTransactions->links()}}
    </div>
    @else
    <p class="card-text text-center">Looks like you don't have any tv transaction history.</p>
    @endif
</div>