<div class="card-body">
    @if(count($powerTransactions) > 0)
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Transaction ID</th>
                    <th scope="col">Status</th>
                    <th scope="col">Service Provider</th>
                    <th scope="col">Token</th>
                    <th scope="col">Receiver</th>
                    <th scope="col">Amount (₦)</th>
                    <th scope="col">Date</th>
                </tr>
            </thead>
            <tbody>
                @php $counter = 0; @endphp
                @foreach($powerTransactions as $powerTransaction)
                <tr>
                    <th scope="row">{{$counter+=1}}</th>
                    <td>{{substr($powerTransaction->transaction_id, -12)}}</td>
                    <td>
                        @if($powerTransaction->status == 0)
                        <span class="badge badge-warning">Pending</span>
                        @elseif($powerTransaction->status == 1)
                        <span class="badge badge-info">In-progress</span>
                        @elseif($powerTransaction->status == 2)
                        <span class="badge badge-success">Fulfilled</span>
                        @elseif($powerTransaction->status == 3)
                        <span class="badge badge-danger">Failed</span>
                        @elseif($powerTransaction->status == 4)
                        <span class="badge badge-primary">Re-try</span>
                        @endif
                    </td>
                    <td>{{$powerTransaction->service->name}}</td>
                    <td>{{$powerTransaction->token}}</td>
                    <td>{{$powerTransaction->meter_num}}</td>
                    <td>{{number_format($powerTransaction->amount, 2)}}</td>
                    <td>{{$powerTransaction->date_created->diffForHumans()}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{$powerTransactions->links()}}
    </div>
    @else
    <p class="card-text text-center">Looks like you don't have any power transaction history.</p>
    @endif
</div>