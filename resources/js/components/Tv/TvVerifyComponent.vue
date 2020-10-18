<template>
    <div>
        <form @submit.prevent="verifyTV()">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="inputProvider">Select Provider</label>
                    <select id="inputProvider" class="form-control" v-model="verifyPayload.service_id" @change="getBundles()" :disabled="$parent.loading">
                        <option value="" selected disabled>Select an option...</option>
                        <option
                            v-for="provider in providers"
                            :key="provider.id"
                            :value="provider.code"
                        >
                            {{ provider.description }}
                        </option>
                    </select>
                </div>
                <!-- This will display amount for starTimes -->
                <div class="form-group col-md-6" v-if="this.verifyPayload.service_id == 23">
                    <label for="smartcardAmount">Enter Amount (₦)</label>
                    <input
                        type="text"
                        class="form-control"
                        id="smartcardAmount"
                        placeholder="Enter Amount"
                        :disabled="$parent.loading"
                        v-model="verifyPayload.amount"
                    />
                </div>
                <!-- This will display a dropdown for DSTV and GOTV -->
                <div class="form-group col-md-6" v-else>
                    <label for="inputPackage">Select Package</label>
                    <select id="inputPackage" class="form-control" v-model="verifyPayload.service_code" :disabled="$parent.loading || packages.length === 0">
                        <option value="" selected disabled>Select an option...</option>
                        <option
                            v-for="item in packages"
                            :key="item.id"
                            :value="item.code"
                        >
                            {{ `${item.name} - ₦ ${$parent.formatNumber(item.amount)}`  }}
                        </option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="smartcardNum">Smartcard Number</label>
                    <input
                        type="text"
                        class="form-control"
                        id="smartcardNum"
                        placeholder="Smartcard number"
                        v-model="verifyPayload.smartcard_num"
                    />
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="tvEmail">Email (optional)</label>
                    <input
                        type="email"
                        class="form-control"
                        id="tvEmail"
                        placeholder="john.doe@gmail.com"
                        v-model="verifyPayload.email"
                    />
                </div>
                <div class="form-group col-md-6">
                    <label for="tvPhone">Phone</label>
                    <input
                        type="text"
                        class="form-control"
                        id="tvPhone"
                        placeholder="081XXXXX"
                        v-model="verifyPayload.phone"
                    />
                </div>
            </div>
             <button
                type="submit"
                class="btn btn-primary btn-round"
                :disabled="$parent.loading"
            >
                {{ !$parent.loading ? "Proceed" : "Loading..." }}
            </button>
        </form>
    </div>
</template>
<script>
import TVService from "../../services/TVService";
export default {
    name: 'TVVerifyComponent',
    data() {
        return {
            providers: [
                {
                    id: "1",
                    code: 21,
                    description: "DSTV"
                },
                {
                    id: "2",
                    code: 22,
                    description: "GOTV"
                },
                {
                    id: "3",
                    code: 23,
                    description: "Startimes"
                }
            ],
            verifyPayload: {
                disco: "",
                platform: "WEB",
                service_id: '',
                service_code: '',
                user_id: this.$parent.user
            },
            packages: []
        };
    },
    methods: {
        getBundles() {
            if (navigator.onLine) {
                this.packages = []
                this.verifyPayload.service_code = ''
                this.$parent.setLoader(true);
                TVService.getBundles(this.verifyPayload.service_id)
                    .then(response => {
                        if (response.data.status === "00") {
                            this.packages = response.data.bundles
                        } else {
                            Swal.fire('Error occured', response.data.message, 'info')
                        }
                        this.$parent.setLoader(false);
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
                Swal.fire('Internet Connection', 'You are currently offline, kindly reload', 'info')
            }
        },
        verifyTV() {
            if (this.validatePayload()) {
                if (navigator.onLine) {
                    this.$parent.setLoader(true);
                    this.verifyPayload.passcode = this.$parent.generateHash(
                        this.verifyPayload.smartcard_num
                    );
                    TVService.verify(this.verifyPayload)
                        .then(response => {
                            if (response.data.status === "00") {
                                var result = response.data.data
                                result.phone = this.verifyPayload.phone
                                result.smartcard_num = this.verifyPayload.smartcard_num
                                this.$parent.nextStep(result);
                            } else {
                                Swal.fire('Error occured', response.data.message, 'info')
                            }
                            this.$parent.setLoader(false);
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
            }
        },
        validatePayload() {
            var checkStatus = false
            if (this.verifyPayload.service_id) {
                if (this.verifyPayload.service_id == 23) {
                    if (this.verifyPayload.amount) {
                        checkStatus = true
                    } else {
                        Swal.fire('Validation', 'Enter a valid amount', 'info')
                    }
                } else {
                    if (this.verifyPayload.service_code) {
                        checkStatus = true
                    } else {
                        Swal.fire('Validation', 'Select a provider package', 'info')
                    }
                }
            } else {
                Swal.fire('Validation', 'Select a provider', 'info')
            }
            if (checkStatus) {
                if (this.verifyPayload.phone && this.verifyPayload.phone.length === 11) {
                    return true;
                } else {
                    Swal.fire('Validation', 'Phone number is required with minimum of 11 characters', 'info')
                }
            }
        }
    }
};
</script>
<style scoped></style>
