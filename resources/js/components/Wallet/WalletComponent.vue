<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h4 style="margin-top: 0px; margin-bottom: 5px;">
                    Wallet Balance
                    <br />
                    <strong
                        >₦ {{ formatNumber(walletBalance) }}</strong
                    >
                </h4>
                <button
                    type="button"
                    class="btn btn-primary btn-round"
                    :disabled="loading"
                    @click.prevent="fundWallet()"
                >
                    <i class="now-ui-icons business_money-coins"></i>
                    {{ !loading ? "Fund Wallet" : "Loading..." }}
                </button>
            </div>
            <div class="col-md-12">
                <p>
                    <strong class="pull-left">Wallet History</strong>
                    <span class="pull-right">
                        <button data-v-0a2b17f9="" type="button" class="btn btn-primary btn-sm btn-round">View more</button>
                    </span>
                </p>
                <table class="table">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody v-if="transactionHistory.length === 0">
                        <tr>
                            <td colspan="4">
                                Looks like you have not made any wallet
                                transactions yet.
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr
                            v-for="(item, index) in transactionHistory"
                            :key="index"
                        >
                            <td class="text-center">{{ index + 1 }}</td>
                            <td>
                                {{
                                    item.transaction_type == 1
                                        ? "Credit"
                                        : "Debit"
                                }}
                            </td>
                            <td>{{ `${item.transaction_description.substr(0, 20)}...` }}</td>
                            <td>₦{{ formatNumber(item.transaction_amount) }}</td>
                            <td>{{ item.status ? "Successful" : "Failed" }}</td>
                            <td>{{ item.created_at }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
<script>
import UserService from "../../services/UserService";
export default {
    name: "WalletComponent",
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
        },
        transactions: {
            type: [String],
            required: true
        }
    },
    computed: {
        userInfo() {
            return JSON.parse(this.user)
        }
    },
    mounted() {
        this.walletBalance = this.userInfo.wallet.balance
        this.transactionHistory = JSON.parse(this.transactions)
    },
    data() {
        return {
            step: 0,
            loading: false,
            credentials: null,
            verifyPayload: {},
            transactionHistory: [],
            walletBalance: 0
        };
    },
    methods: {
        setLoader(option) {
            this.loading = option;
        },
        nextStep(credentials) {
            this.credentials = credentials ? credentials : this.credentials;
            this.step++;
        },
        prevStep() {
            this.step--
        },
        restartProcess() {
            this.step = 0
            this.loading = false
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
        payWithCard(amount) {
            if (navigator.onLine) {
                this.verifyPayload.amount = amount
                this.setLoader(true)
                var that = this;
                var handler = PaystackPop.setup({
                    key: this.paystack,
                    email: this.userInfo.email ? this.userInfo.email : 'info@cardcom.ng',
                    amount: amount * 100,
                    metadata: {
                        custom_fields: [
                            {
                                customer_phone: this.userInfo.phone,
                                customer_email: this.userInfo.email
                            },
                        ]
                    },
                    callback: function(response) {
                        if (response.status == "success" && response.message == "Approved") {
                            that.verifyPayload.payment_ref = response.reference ? response.reference : response.trxref
                            that.setLoader(true)
                            that.verifyWalletVend()
                        } else {
                            Swal.fire('Payment Error', 'Your payment could not be processed', 'info')
                            that.setLoader(false)
                        }
                    },
                    onClose: function() {
                        that.setLoader(false)
                    },
                });
                handler.openIframe();
            } else {
                Swal.fire('Internet Connection', 'You are currently offline', 'info')
            }
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
        fundWallet() {
            var that = this
            Swal.fire({
                title: 'Enter amount to fund',
                input: 'text',
                inputAttributes: {
                    autocapitalize: 'off'
                },
                showCancelButton: true,
                confirmButtonText: 'Proceed',
                showLoaderOnConfirm: true,
                preConfirm: (login) => {
                    that.payWithCard(login)
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
