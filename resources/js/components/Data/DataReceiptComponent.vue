<template>
  <div>
    <receipt-header :transaction_id="response && response.trans_id"/>
    <div class="row">
      <table class="table table-hover">
        <tbody>
          <tr>
            <td><strong>Service Provider</strong></td>
            <td class="text-center" colspan="2">{{ response && response.service }}</td>
          </tr>
          <tr>
            <td><strong>Bundle name</strong></td>
            <td class="text-center" colspan="2">{{ response && response.bundle_name }}</td>
          </tr>
          <tr v-if="response && response.service == 'Smile'">
            <td><strong>Customer Details</strong></td>
            <td class="text-center" colspan="2">{{ response && response.customerName }}</td>
          </tr>
          <tr>
            <td v-if="response.service_id == 8 || response.service_id == 9"><strong>Smartcard Number</strong></td>
            <td v-else><strong>Receiver Number</strong></td>
            <td class="text-center" colspan="2">{{ response && response.phone }}</td>
          </tr>
          <tr>
            <td class="text-center">
              <h4><strong>Total: </strong></h4>
            </td>
            <td class="text-center text-danger" colspan="2">
              <h4><strong>₦{{ response && $parent.formatNumber(response.amountToPay) }}</strong></h4>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <receipt-footer />
    <div class="row">
      <div class="col-md-12 text-right">
        <button type="button" class="btn btn-primary btn-sm btn-round" @click.prevent="$parent.restartProcess()">Restart</button>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  name: 'DataReceiptComponent',
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