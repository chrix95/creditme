<template>
  <div>
    <form @submit.prevent="verifyAirtime()">
      <div class="form-row">
        <div class="form-group col-md-12">
          <label for="inputNetwork">Network Provider</label>
          <select id="inputNetwork" class="form-control" v-model="verifyPayload.network">
            <option selected disabled value="">Select an option...</option>
            <option v-for="network in networks" :key="network.id" :value="network.code">
              {{ network.name }}
            </option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-6">
          <label for="recieverAirtime">Receiver number</label>
          <input
              type="text"
              class="form-control"
              id="recieverAirtime"
              placeholder="081XXXXX"
              v-model="verifyPayload.phone"
          />
        </div>
        <div class="form-group col-md-6">
          <label for="airtimeAmount">Amount (₦)</label>
          <input
            type="number"
            class="form-control"
            id="airtimeAmount"
            placeholder="200"
            v-model="verifyPayload.amount"
          />
        </div>
      </div>
      <div class="form-group">
        <label for="airtimeEmail">Email address (optional)</label>
        <input
          type="text"
          class="form-control"
          id="airtimeEmail"
          placeholder="john.doe@gmail.com"
          v-model="verifyPayload.email"
        />
      </div>
      <button type="submit" class="btn btn-primary btn-round" :disabled="$parent.loading">{{ !$parent.loading ? 'Proceed' : 'Loading...' }}</button>
    </form>
  </div>
</template>
<script>
import AirtimeService from "../../services/AirtimeService";
export default {
  name: 'AirtimeVerifyComponent',
	data() {
    return {
      networks: [
        {
          id: 1,
          name: 'MTN',
          code: 'MTN'
        },
        {
          id: 2,
          name: 'Airtel',
          code: 'Airtel'
        },
        {
          id: 3,
          name: 'GLO',
          code: 'Glo'
        },
        {
          id: 4,
          name: '9mobile',
          code: 'Etisalat'
        }
      ],
      verifyPayload: {
        network: '',
        platform: "WEB",
        user_id: this.$parent.user
      },
    }
  },
  methods: {
    verifyAirtime() {
      if(this.validatePayload()) {
        if(navigator.onLine) {
          this.$parent.setLoader(true)
          this.verifyPayload.passcode = this.$parent.generateHash(this.verifyPayload.phone)
          AirtimeService.verify(this.verifyPayload)
            .then(response => {
              if (response.data.status === "00") {
                this.$parent.nextStep(response.data.data)
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
        }  else {
          Swal.fire('Internet Connection', 'You are currently offline', 'info')
        }
      }
    },
    validatePayload() {
      if(this.verifyPayload.network) {
        if(this.verifyPayload.phone && this.verifyPayload.phone.length === 11) {
          if(this.verifyPayload.amount && this.verifyPayload.amount >= 50) {
            return true
          } else {
            Swal.fire('Validation', 'Enter an amount greater than 50', 'info')
          }
        } else {
          Swal.fire('Validation', 'Reciever number is required with minimum of 11 characters', 'info')
        }
      } else {
        Swal.fire('Validation', 'Network is required', 'info')
      }
    },
  }
};
</script>
<style scoped></style>
