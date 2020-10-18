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
            <td class="text-center" colspan="2">{{ response && response.disco }}</td>
          </tr>
          <tr>
            <td><strong>Customer Details</strong></td>
            <td class="text-center">{{ response && response.msg.name }}</td>
            <td class="text-right">{{ response && response.msg.address }}</td>
          </tr>
          <tr>
            <td><b>Token</b></td>
            <td class="text-center token" colspan="2">{{ response && spacedToken }}</td>
          </tr>
          <tr>
            <td><strong>Units</strong></td>
            <td class="text-center" colspan="2">{{ response && response.units }} KWh</td>
          </tr>
          <tr>
            <td class="text-center">
              <h4><strong>Total: </strong></h4>
            </td>
            <td class="text-center text-danger" colspan="2">
              <h4><strong>₦{{ response && $parent.formatNumber(response.amount) }}</strong></h4>
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
  name: 'PowerReceiptComponent',
  props: {
    response: {
      required: true
    }
  },
  computed: {
    spacedToken() {
      const arrToken = this.spaceToken(this.response.token, 4).join('-');
      return arrToken
    }
  },
  methods: {
    spaceToken(str, n) {
      var ret = [];
      var i;
      var len;
      for(i = 0, len = str.length; i < len; i += n) {
        ret.push(str.substr(i, n))
      }
      return ret
    }
  }
}
</script>
<style scoped>
.token {
  font-size: 20px;
  font-weight: bold;
}
</style>