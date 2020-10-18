<template>
  <div>
    <h3>Transaction Summary</h3>
    <table class="table table-borderless">
      <tbody>
        <tr>
          <th scope="row">TV Provider</th>
          <td class="uppercase">{{ response && response.msg.bundle_name }}</td>
        </tr>
        <tr>
          <th scope="row">Customer name</th>
          <td class="uppercase">{{ response && response.msg.customerName }}</td>
        </tr>
        <tr>
          <th scope="row">Smartcard number</th>
          <td class="uppercase">{{ response && response.smartcard_num }}</td>
        </tr>
        <tr>
          <th scope="row">Transaction ID</th>
          <td class="uppercase">{{ response && response.trans_id }}</td>
        </tr>
        <tr>
          <th scope="row">Phone</th>
          <td>{{ response && response.phone }}</td>
        </tr>
        <tr>
          <th scope="row">Total Amount</th>
          <td>₦ {{ response && $parent.formatNumber(response.msg.amountToPay) }}</td>
        </tr>
        <tr>
          <th scope="row">Payment option</th>
          <td>
            <select class="form-control" v-model="verifyPayload.payment_method" :disabled="$parent.loading">
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
              :disabled="$parent.loading"
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
import TVService from "../../services/TVService";
export default {
  name: 'TVPaymentComponent',
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
            this.vendTV()
          } else {
            Swal.fire('Validation', 'Kindly enter your voucher code', 'info')
          }
        }
        if (this.verifyPayload.payment_method === "WALLET") {
          this.verifyPayload.payment_ref = this.response.trans_id
          this.vendTV()
        }
      } else {
        Swal.fire('Internet Connection', 'You are currently offline', 'info')
      }
    },
    vendTV() {
      if(navigator.onLine) {
        this.$parent.setLoader(true)
        this.verifyPayload.transaction_id = this.response.trans_id
        this.verifyPayload.access_token = this.response.msg.access_token
        this.verifyPayload.passcode = this.$parent.generateHash(this.response.msg.access_token)
        TVService.vend(this.verifyPayload)
          .then(response => {
            if (response.data.status === "00") {
              var result = this.$parent.credentials
              this.$parent.nextStep(result)
              this.verifyPayload.payment_ref = ''
            } else {
              Swal.fire('Error occured', response.data.message, 'info')
            }
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
        amount: this.response.msg.amountToPay * 100,
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
            that.vendTV()
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
