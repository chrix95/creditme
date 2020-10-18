<template>
  <div>
    <h3>Transaction Summary</h3>
    <table class="table table-borderless">
      <tbody>
        <tr>
          <th scope="row">Meter name</th>
          <td class="uppercase">{{ response && response.msg.name }}</td>
        </tr>
        <tr>
          <th scope="row">Meter Address</th>
          <td class="uppercase">{{ response && response.msg.address }}</td>
        </tr>
        <tr>
          <th scope="row">Transaction ID</th>
          <td class="uppercase">{{ response && response.trans_id }}</td>
        </tr>
        <tr>
          <th scope="row">Disco</th>
          <td class="uppercase">{{ response && response.disco }}</td>
        </tr>
        <tr>
          <th scope="row">Phone</th>
          <td>{{ response && response.phone }}</td>
        </tr>
        <tr>
          <th scope="row">Total Amount</th>
          <td>₦ {{ response && $parent.formatNumber(response.amount) }}</td>
        </tr>
        <tr>
          <th scope="row">Payment option</th>
          <td>
            <select class="form-control" v-model="verifyPayload.payment_method">
              <option value="">Select an option</option>
              <option value="CARD">Pay with Paystack</option>
              <option value="VOUCHER">Pay with Voucher</option>
              <option value="WALLET" v-if="$parent.user !== ''">Pay with Wallet</option>
            </select>
          </td>
        </tr>
        <tr v-if="verifyPayload.payment_method === 'VOUCHER'">
          <th scope="row" for="voucherCode">Voucher code</th>
          <th>
            <input
              type="text"
              class="form-control"
              id="voucherCode"
              placeholder="Enter your code"
              v-model="verifyPayload.payment_ref"
            />
          </th>
        </tr>
      </tbody>
    </table>
    <div class="row justify-content-between">
      <button type="button" class="btn btn-neutral" @click.prevent="$parent.prevStep()" :disabled="$parent.loading">
        <i class="now-ui-icons arrows-1_minimal-left"></i>
        Back
      </button>
      <button type="button" class="btn btn-primary btn-round" :disabled="verifyPayload.payment_method === '' || $parent.loading" @click.prevent="proceed()">
        {{ !$parent.loading ? 'Complete' : 'Loading...' }}
        <i class="now-ui-icons arrows-1_minimal-right"></i>
      </button>
    </div>
  </div>
</template>
<script>
import PowerService from "../../services/PowerService";
export default {
  name: 'PowerPaymentComponent',
  props: {
    response: {
      required: true
    }
  },
	data() {
    return {
      verifyPayload: {
        payment_method: ''
      },
    }
  },
  methods: {
    proceed() {
      if (navigator.onLine) {
        if (this.verifyPayload.payment_method === "CARD") {
          this.payWithCard()
        }
        if (this.verifyPayload.payment_method === "VOUCHER") {
          if(this.verifyPayload.payment_ref) {
            this.vendPower()
          } else {
            Swal.fire('Validation', 'Kindly enter your voucher code', 'info')
          }
        }
        if (this.verifyPayload.payment_method === "WALLET") {
          this.verifyPayload.payment_ref = this.response.trans_id
          this.vendPower()
        }
      } else {
        Swal.fire('Internet Connection', 'You are currently offline', 'info')
      }
    },
    vendPower() {
      if(navigator.onLine) {
        this.$parent.setLoader(true)
        this.verifyPayload.transaction_id = this.response.trans_id
        this.verifyPayload.passcode = this.$parent.generateHash(this.response.trans_id)
        PowerService.vend(this.verifyPayload)
          .then(response => {
            if (response.data.status === "00") {
              var result = this.$parent.credentials
              result.token = response.data.data.token
              result.units = response.data.data.units
              this.$parent.nextStep(result)
              this.verifyPayload.payment_ref = ''
            } else {
              Swal.fire('Error occured', response.data.message, 'info')
            }
            this.$parent.setLoader(false)
          })
          .catch(err => {
            this.$parent.setLoader(false);
            if (err.response === undefined) {
              Swal.fire('Server Error', 'Result unknown, kindly refresh', 'error')
            } else {
              Swal.fire(err.response.statusText, err.response.data.message, 'error')
            }
          });
      } else {
        Swal.fire('Internet Connection', 'You are currently offline', 'info')
      }
    },
    payWithCard() {
      this.$parent.setLoader(true)
      var that = this;
      var handler = PaystackPop.setup({
        key: this.$parent.paystack,
        email: this.response.email ? this.response.email : 'info@cardcom.ng',
        amount: this.response.amount * 100,
        metadata: {
          custom_fields: [
            {
              customer_phone: this.response.phone,
              customer_email: this.response.email,
              txRef: this.response.trans_id,
            },
          ],
        },
        callback: function(response) {
          if (response.status == "success" && response.message == "Approved") {
            that.verifyPayload.payment_ref = response.reference ? response.reference : response.trxref
            that.vendPower()
          } else {
            Swal.fire('Payment Error', 'Your payment could not be processed', 'info')
            that.$parent.setLoader(false)
          }
        },
        onClose: function() {
          that.$parent.setLoader(false)
        },
      });
      handler.openIframe();
    },
  }
};
</script>
<style scoped></style>
