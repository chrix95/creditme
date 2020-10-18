<div class="card-body">
    @if(count($dataTransactions) > 0)
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
                @foreach($dataTransactions as $dataTransaction)
                <tr>
                    <th scope="row">{{$counter+=1}}</th>
                    <td>{{substr($dataTransaction->transaction_id, -12)}}</td>
                    <td>
                        @if($dataTransaction->status == 0)
                        <span class="badge badge-warning">Pending</span>
                        @elseif($dataTransaction->status == 1)
                        <span class="badge badge-info">In-progress</span>
                        @elseif($dataTransaction->status == 2)
                        <span class="badge badge-success">Fulfilled</span>
                        @elseif($dataTransaction->status == 3)
                        <span class="badge badge-danger">Failed</span>
                        @elseif($dataTransaction->status == 4)
                        <span class="badge badge-primary">Re-try</span>
                        @endif
                    </td>
                    <td>{{$dataTransaction->phone}}</td>
                    <td>{{$dataTransaction->bundle->name}}</td>
                    <td>{{number_format((float)$dataTransaction->amount, 2, '.', '')}}</td>
                    <td>{{$dataTransaction->date_created->diffForHumans()}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{$dataTransactions->links()}}
    </div>
    @else
    <p class="card-text text-center">Looks like you don't have any data transaction history.</p>
    @endif
</div>