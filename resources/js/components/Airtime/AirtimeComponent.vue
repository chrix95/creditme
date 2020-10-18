<template>
  <div>
    <airtime-verify-component  v-show="step === 0" />
    <airtime-payment-component :response="credentials"  v-show="step === 1" />
    <airtime-receipt-component :response="credentials"  v-if="step === 2" />
  </div>
</template>
<script>
export default {
  name: 'AirtimeComponent',
  props: {
    passcode: {
      type: [String],
      required: true
    },
    user: {
      type: [String],
      required: true
    },
    paystack: {
      type: [String],
      required: true
    }
  },
	data() {
    return {
      step: 0,
      loading: false,
      credentials: null
    }
  },
  methods: {
    setLoader(option) {
      this.loading = option
    },
    nextStep(credentials) {
      this.credentials = credentials ? credentials : this.credentials
      this.step++
    },
    prevStep() {
      this.step--
    },
    restartProcess() {
      this.step = 0
      this.loading = false
    },
    formatNumber(num) {
      var result = num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,')
      return result
    },
    generateHash(ref) {
      let encrypted = $cryptoJS(ref, this.passcode)
      return encrypted.toString()
    }
  }
};
</script>
<style>
.uppercase {
  text-transform: uppercase;
}
.capitalize {
  text-transform: capitalize;
}
</style>
