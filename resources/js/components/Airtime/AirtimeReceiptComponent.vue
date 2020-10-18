<template>
  <div>
    <div class="row">
      <div class="col-md-12 text-right">
        <button type="button" class="btn btn-primary btn-round" @click.prevent="$parent.restartProcess()">Restart</button>
      </div>
    </div>
    <receipt-header :transaction_id="response && response.trans_id"/>
    <div class="row">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Service</th>
            <th class="text-center">Network</th>
            <th class="text-center">Total (₦)</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="col-md-9">
              <strong id="receipt_service_id">Airtime Purchase</strong>
            </td>
            <td class="col-md-1 text-center">{{ response && response.network }}</td>
            <td class="col-md-1 text-center">₦ {{ response && $parent.formatNumber(response.amount) }}</td>
          </tr>
          <tr>
            <td class="text-right" colspan="2"><h4><strong>Total:</strong></h4></td>
            <td class="text-center text-danger"><h4><strong>₦{{ response && $parent.formatNumber(response.amount) }}.00</strong></h4></td>
          </tr>
        </tbody>
      </table>
    </div>
    <receipt-footer />
  </div>
</template>
<script>
export default {
  name: 'AirtimeReceiptComponent',
  props: {
    response: {
      required: true
    }
  }
}
</script>
<style scoped></style>