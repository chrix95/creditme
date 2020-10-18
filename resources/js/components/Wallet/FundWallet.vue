<template>
    <div>
        <p class="description">
            <strong>Wallet Balance:</strong> <br>
            ₦ {{ formatNumber(walletBalance) }}
        </p>
        <a href="#" class="card-link" :disabled="loading" @click.prevent="fundWallet()">
            {{ !loading ? "Fund Wallet" : "Loading..." }}
        </a>
    </div>
</template>
<script>
import UserService from "../../services/UserService";
export default {
    name: "FundWallet",
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
    computed: {
        userInfo() {
            return JSON.parse(this.user)
        }
    },
    mounted() {
        this.walletBalance = this.userInfo.wallet.balance
    },
    data() {
        return {
            step: 0,
            loading: false,
            credentials: null,
            verifyPayload: {},
            walletBalance: 0
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
