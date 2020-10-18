<template>
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body" style="padding: 1.25rem;">
                    <div class="info info-hover text-center">
                        <h4>Generate Voucher</h4>
                        <form @submit.prevent="createVoucher()">
                            <div class="form-group">
                                <label for="amount">Amount</label>
                                <input type="text" id="amount" placeholder="Amount" class="form-control" v-model="fields.amount">
                            </div>
                            <div class="form-group">
                                <label for="expiry">Expiry</label>
                                <input type="date" id="expiry" class="form-control" v-model="fields.expiry" :min="minDate">
                            </div>
                            <div class="form-group">
                                <button class="btn btn-primary btn-round" :disabled="loading" type="submit">{{ !loading ? 'Generate' : 'Loading...' }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card text-center">
                <h4>Voucher History</h4>
                <div class="card-body mt-3">
                    <div class="tab-content">
                        <div class="tab-pane active" id="voucher" role="tabpanel">
                            <div v-if="voucherHistory.length === 0">
                                <p class="card-text text-center">No voucher currently created.</p>
                            </div>
                            <div class="table-responsive" v-else>
                                <table class="table table-hover table-bordered table-stripped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Voucher code</th>
                                            <th>Amount</th>
                                            <th>Balance</th>
                                            <th>Expiry Date</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in voucherHistory" :key="index">
                                            <td>{{ index + 1 }}</td>
                                            <td>{{ item.voucher }}</td>
                                            <td>{{ formatNumber(item.amount) }}</td>
                                            <td>{{ formatNumber(item.balance) }}</td>
                                            <td>{{ item.expiry }}</td>
                                            <td v-if="new Date(item.expiry) > new Date()" class="bg-success">Active</td>
                                            <td v-else class="bg-danger">Expired</td>
                                            <td>
                                                <a href="#" style="color: red" class="card-link" :disabled="loading" @click.prevent="deleteVoucher(item.id)">
                                                    <i class="now-ui-icons ui-1_simple-remove"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import UserService from "../../services/UserService";
export default {
    name: "VoucherComponent",
    props: {
        passcode: {
            type: [String],
            required: true
        },
        transactions: {
            type: [String],
            required: true
        }
    },
    mounted() {
        this.voucherHistory = JSON.parse(this.transactions)
    },
    data() {
        return {
            loading: false,
            credentials: null,
            fields: {},
            voucherHistory: [],
            minDate: new Date().toJSON().slice(0,10).replace(/-/g,'-')
        };
    },
    methods: {
        setLoader(option) {
            this.loading = option;
        },
        formatNumber(num) {
            var result = num
                .toString()
                .replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,")
            return result
        },
        generateHash(ref) {
            let encrypted = $cryptoJS(ref, this.passcode)
            return encrypted.toString()
        },
        createVoucher() {
            this.setLoader(true)
            var combindeString = this.fields.amount + '|' + this.fields.expiry
            this.fields.passcode = this.generateHash(combindeString)
            UserService.create_voucher(this.fields)
                .then(response => {
                    if (response.data.status === "00") {
                        this.voucherHistory = [response.data.data.data, ...this.voucherHistory]
                        this.fields = {}
                        Swal.fire('Voucher created Successful', `The voucher code is ${response.data.data.data.voucher}, worth ₦ ${this.formatNumber(response.data.data.data.amount)}.`, 'success')
                    } else {
                        Swal.fire('Error occured', response.data.message, 'info')
                    }
                    this.setLoader(false)
                })
                .catch(err => {
                    this.setLoader(false);
                    if (err.response === undefined) {
                        Swal.fire('Server Error', 'Result unknown, kindly refresh', 'error')
                    } else {
                        Swal.fire(err.response.statusText, err.response.data.message, 'error')
                    }
                });
        },
        destroyVoucher(id) {
            this.setLoader(true)
            UserService.delete_voucher(id)
                .then(response => {
                    if (response.data.status === "00") {
                        this.voucherHistory = this.voucherHistory.filter(c => c.id !== id)
                        Swal.fire('Success', `Voucher deleted successfully`, 'success')
                    } else {
                        Swal.fire('Error occured', response.data.message, 'info')
                    }
                    this.setLoader(false)
                })
                .catch(err => {
                    this.setLoader(false);
                    if (err.response === undefined) {
                        Swal.fire('Server Error', 'Result unknown, kindly refresh', 'error')
                    } else {
                        Swal.fire(err.response.statusText, err.response.data.message, 'error')
                    }
                });
        },
        verifyWalletVend() {
            this.setLoader(true)
            var combindeString = this.userInfo.email + '|' + this.verifyPayload.payment_ref
            this.verifyPayload.email = this.userInfo.email
            this.verifyPayload.passcode = this.generateHash(combindeString)
            UserService.fund_user_wallet(this.verifyPayload)
                .then(response => {
                    if (response.data.status === "00") {
                        this.walletBalance = response.data.data.new_balance
                        this.transactionHistory = [response.data.data.transaction, ...this.transactionHistory]
                        this.transactionHistory.pop()
                        Swal.fire('Wallet Funding Successful', `Your new wallet balance is ₦ ${this.formatNumber(response.data.data.new_balance)}.`, 'success')
                    } else {
                        Swal.fire('Error occured', response.data.message, 'info')
                    }
                    this.setLoader(false)
                })
                .catch(err => {
                    this.setLoader(false);
                    if (err.response === undefined) {
                        Swal.fire('Server Error', 'Result unknown, kindly refresh', 'error')
                    } else {
                        Swal.fire(err.response.statusText, err.response.data.message, 'error')
                    }
                });
        },
        deleteVoucher(id) {
            var that = this
            Swal.fire({
                title: 'Are you sure?',
                showCancelButton: true,
                confirmButtonText: 'Proceed',
                showLoaderOnConfirm: true,
                preConfirm: (login) => {
                    that.destroyVoucher(id)
                    Swal.close()
                },
                allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    
                })
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
