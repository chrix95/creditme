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
        <tbody>
          <tr>
            <td><strong>Service Provider</strong></td>
            <td class="text-center" colspan="2">{{ response && response.msg.bundle_name }}</td>
          </tr>
          <tr>
            <td><strong>Customer Details</strong></td>
            <td class="text-center" colspan="2">{{ response && response.msg.customerName }}</td>
          </tr>
          <tr>
            <td><strong>Smartcard Number</strong></td>
            <td class="text-center" colspan="2">{{ response && response.smartcard_num }}</td>
          </tr>
          <tr>
            <td class="text-center">
              <h4><strong>Total: </strong></h4>
            </td>
            <td class="text-center text-danger" colspan="2">
              <h4><strong>₦{{ response && $parent.formatNumber(response.msg.amountToPay) }}</strong></h4>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <receipt-footer />
  </div>
</template>
<script>
export default {
  name: 'TVReceiptComponent',
  props: {
    response: {
      required: true
    }
  },
  methods: {}
}
</script>
<style scoped>
.token {
  font-size: 20px;
  font-weight: bold;
}
</style>