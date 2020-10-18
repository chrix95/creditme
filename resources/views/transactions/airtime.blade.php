<div class="card-body">
    @if(count($airtimeTransactions) > 0)
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Transaction ID</th>
                    <th scope="col">Status</th>
                    <th scope="col">Receiver</th>
                    <th scope="col">Network</th>
                    <th scope="col">Amount (₦)</th>
                    <th scope="col">Date</th>
                </tr>
            </thead>
            <tbody>
                @php $counter = 0; @endphp
                @foreach($airtimeTransactions as $airtimeTransaction)
                <tr>
                    <th scope="row">{{$counter+=1}}</th>
                    <td>{{substr($airtimeTransaction->transaction_id, -12)}}</td>
                    <td>
                        {{-- by default status is pending = 0, active = 1, fulfilled = 2, failed = 3 and re-try = 4 --}}
                        @if($airtimeTransaction->status == 0)
                        <span class="badge badge-warning">Pending</span>
                        @elseif($airtimeTransaction->status == 1)
                        <span class="badge badge-info">In-progress</span>
                        @elseif($airtimeTransaction->status == 2)
                        <span class="badge badge-success">Fulfilled</span>
                        @elseif($airtimeTransaction->status == 3)
                        <span class="badge badge-danger">Failed</span>
                        @elseif($airtimeTransaction->status == 4)
                        <span class="badge badge-primary">Re-try</span>
                        @endif
                    </td>
                    <td>{{$airtimeTransaction->phone}}</td>
                    <td>{{$airtimeTransaction->service_id}}</td>
                    <td>{{number_format((float)$airtimeTransaction->amount, 2, '.', '')}}</td>
                    <td>{{$airtimeTransaction->date_created->diffForHumans()}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{$airtimeTransactions->links()}}
    </div>
    @else
    <p class="card-text text-center">Looks like you don't have any airtime transaction history.</p>
    @endif
</div>