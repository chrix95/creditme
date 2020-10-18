<template>
    <div>
        <form @submit.prevent="verifyPower()">
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="inputDisco">Select Disco</label>
                    <select id="inputDisco" class="form-control" v-model="verifyPayload.disco">
                        <option value="" selected disabled>Select an option...</option>
                        <option
                            v-for="disco in sortedDisco"
                            :key="disco.id"
                            :value="disco.code"
                        >
                            {{ disco.description }}
                        </option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-6 col-md-6">
                    <label for="meterNum">Meter Number</label>
                    <input
                        type="text"
                        class="form-control"
                        id="meterNum"
                        placeholder="Meter number"
                        v-model="verifyPayload.meter_number"
                    />
                </div>
                <div class="form-group col-sm-6 col-md-6">
                    <label for="powerAmount">Amount (₦)</label>
                    <input
                        type="number"
                        class="form-control"
                        id="powerAmount"
                        placeholder="Amount"
                        v-model="verifyPayload.amount"
                    />
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-6 col-md-6">
                    <label for="powerEmail">Email (optional)</label>
                    <input
                        type="email"
                        class="form-control"
                        id="powerEmail"
                        placeholder="john.doe@gmail.com"
                        v-model="verifyPayload.email"
                    />
                </div>
                <div class="form-group col-sm-6 col-md-6">
                    <label for="powerPhone">Phone</label>
                    <input
                        type="text"
                        class="form-control"
                        id="powerPhone"
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
import PowerService from "../../services/PowerService";
export default {
    name: 'PowerVerifyComponent',
	computed: {
		sortedDisco() {
			return this.discos.sort((a,b) => a.description.localeCompare(b.description))
		}
	},
    data() {
        return {
            discos: [
                {
                    id: "1",
                    code: "AEDC",
                    description: "AEDC Prepaid",
                    minimum_value: "600",
                    maximum_value: "500000"
                },
                {
                    id: "2",
                    code: "Ikeja_Electric_Bill_Payment",
                    description: "Ikeja Electric Bill Payment",
                    minimum_value: "1000",
                    maximum_value: "100000"
                },
                {
                    id: "3",
                    code: "Ikeja_Token_Purchase",
                    description: "Ikeja Token Purchase",
                    minimum_value: "1000",
                    maximum_value: "100000"
                },
                {
                    id: "4",
                    code: "Eko_Prepaid",
                    description: "Eko Prepaid",
                    minimum_value: "1100",
                    maximum_value: "100000"
                },
                {
                    id: "5",
                    code: "Eko_Postpaid",
                    description: "Eko Postpaid",
                    minimum_value: "1000",
                    maximum_value: "100000"
                },
                {
                    id: "6",
                    code: "Ibadan_Disco_Prepaid",
                    description: "Ibadan Disco Prepaid",
                    minimum_value: "1000",
                    maximum_value: "50000"
                },
                {
                    id: "7",
                    code: "Kano_Electricity_Disco",
                    description: "Kano Electricity DISCO",
                    minimum_value: "600",
                    maximum_value: "100000"
                },
                {
                    id: "8",
                    code: "Kaduna_Electricity_Disco",
                    description: "Kaduna Electricity DISCO",
                    minimum_value: "600",
                    maximum_value: "100000"
                },
                {
                    id: "9",
                    code: "Jos_Disco",
                    description: "Jos Electricity Distribution",
                    minimum_value: "600",
                    maximum_value: "1000000"
                },
                {
                    id: "10",
                    code: "PhED_Electricity",
                    description:
                        "Port Harcourt Electricity Distribution Prepaid",
                    minimum_value: "600",
                    maximum_value: "100000"
                },
                {
                    id: "11",
                    code: "PH_Disco",
                    description:
                        "Port Harcourt Electricity Distribution Postpaid",
                    minimum_value: "1000",
                    maximum_value: "100000"
                },
                {
                    id: "12",
                    code: "Kaduna_Electricity_Disco_Postpaid",
                    description: "Kaduna Electricity Disco Postpaid",
                    minimum_value: "1000",
                    maximum_value: "5000000"
                },
                {
                    id: "13",
                    code: "AEDC_Postpaid",
                    description: "AEDC Postpaid",
                    minimum_value: "1000",
                    maximum_value: "21000000"
                },
                {
                    id: "14",
                    code: "Jos_Disco_Postpaid",
                    description: "Jos Electricity Postpaid",
                    minimum_value: "600",
                    maximum_value: "10000000"
                },
                {
                    id: "15",
                    code: "Enugu_Electricity_Distribution_Prepaid",
                    description: "Enugu Electricity Distribution Prepaid",
                    minimum_value: "600",
                    maximum_value: "50000"
                }
            ],
            verifyPayload: {
                disco: "",
                platform: "WEB",
                user_id: this.$parent.user
            },
        };
    },
    methods: {
        verifyPower() {
            if (this.validatePayload()) {
                if (navigator.onLine) {
                    this.$parent.setLoader(true);
                    this.verifyPayload.passcode = this.$parent.generateHash(
                        this.verifyPayload.meter_number
                    );
                    PowerService.verify(this.verifyPayload)
                        .then(response => {
                            if (response.data.status === "00") {
                                this.$parent.nextStep(response.data.data);
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
            if (this.verifyPayload.disco) {
                if (
                    this.verifyPayload.meter_number
                ) {
                    if (
                        this.verifyPayload.amount
                    ) {
                        if (
                            this.verifyPayload.phone &&
                            this.verifyPayload.phone.length === 11
                        ) {
                            return true;
                        } else {
                            Swal.fire('Validation', 'Phone number is required with minimum of 11 characters', 'info')
                        }
                    } else {
                        Swal.fire('Validation', 'Enter an amount', 'info')
                    }
                } else {
                    Swal.fire('Validation', 'Meter number is required', 'info')
                }
            } else {
                Swal.fire('Validation', 'Disco is required', 'info')
            }
        }
    }
};
</script>
<style scoped></style>
