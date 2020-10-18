<template>
    <div>
        <form @submit.prevent="verifyData()">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="inputDataProvider">Select Provider</label>
                    <select id="inputDataProvider" class="form-control" v-model="verifyPayload.service_id" @change="getBundles()" :disabled="$parent.loading">
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
                <!-- This will display a dropdown for all Data bundles -->
                <div class="form-group col-md-6">
                    <label for="inputBundles">Select Bundles</label>
                    <select id="inputBundles" class="form-control" v-model="verifyPayload.data_bundles_id" :disabled="packages.length === 0">
                        <option value="" selected disabled>Select an option...</option>
                        <option
                            v-for="item in packages"
                            :key="item.id"
                            :value="item.id"
                        >
                            {{ `${item.name} - ₦ ${$parent.formatNumber(item.amount)}`  }}
                        </option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="receiverNum" v-if="this.verifyPayload.service_id == 8 || this.verifyPayload.service_id == 9">Smartcard Number</label>
                    <label for="receiverNum" v-else>Receiver Number</label>
                    <input
                        type="text"
                        class="form-control"
                        id="receiverNum"
                        :placeholder="this.verifyPayload.service_id == 8 || this.verifyPayload.service_id == 9 ? 'Smartcard number' : 'Receiver Number'"
                        v-model="verifyPayload.phone"
                    />
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="dataEmail">Email (optional)</label>
                    <input
                        type="email"
                        class="form-control"
                        id="dataEmail"
                        placeholder="john.doe@gmail.com"
                        v-model="verifyPayload.email"
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
import DataService from "../../services/DataService";
export default {
    name: 'DataVerifyComponent',
    data() {
        return {
            providers: [
                {
                    id: 1,
                    code: 5,
                    description: "MTN"
                },
                {
                    id: 2,
                    code: 6,
                    description: "9mobile"
                },
                {
                    id: 3,
                    code: 28,
                    description: "GLO"
                },
                {
                    id: 4,
                    code: 7,
                    description: "Airtel"
                },
                {
                    id: 5,
                    code: 8,
                    description: "Smile"
                },
                {
                    id: 6,
                    code: 9,
                    description: "Spectranet"
                }
            ],
            verifyPayload: {
                platform: "WEB",
                service_id: '',
                data_bundles_id: '',
                user_id: this.$parent.user
            },
            packages: []
        };
    },
    methods: {
        getBundles() {
            if (navigator.onLine) {
                this.packages = []
                this.verifyPayload.data_bundles_id = ''
                this.$parent.setLoader(true);
                DataService.getBundles(this.verifyPayload.service_id)
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
                Swal.fire('Internet Connection', 'You are currently offline', 'info')
            }
        },
        verifyData() {
            if (this.validatePayload()) {
                if (navigator.onLine) {
                    this.$parent.setLoader(true);
                    this.verifyPayload.passcode = this.$parent.generateHash(
                        this.verifyPayload.phone
                    );
                    DataService.verify(this.verifyPayload)
                        .then(response => {
                            if (response.data.status === "00") {
                                var result = response.data.data
                                result.service = this.providers.find(c => c.code == this.verifyPayload.service_id).description
                                result.service_id = this.verifyPayload.service_id
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
            if (this.verifyPayload.service_id) {
                if (this.verifyPayload.data_bundles_id) {
                    if (this.verifyPayload.phone && this.verifyPayload.phone.length === 11) {
                        return true
                    } else {
                        Swal.fire('Validation', 'Phone number is required with minimum of 11 characters', 'info')
                    }
                } else {
                    Swal.fire('Validation', 'Select a data bundle', 'info')
                }
            } else {
                Swal.fire('Validation', 'Select a service provider', 'info')
            }
        }
    }
};
</script>
<style scoped></style>
