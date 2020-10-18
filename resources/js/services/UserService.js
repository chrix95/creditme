import Api from "./Api";

export default {
  fund_user_wallet(credentials) {
    return Api().post("users/fund_user_wallet", credentials);
  },
  create_voucher(credentials) {
    return Api().post("users/create_voucher", credentials);
  },
  delete_voucher(id) {
    return Api().delete(`users/delete_voucher/${id}`);
  }
};
