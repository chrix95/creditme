import Api from "./Api";

export default {
  verify(credentials) {
    return Api().post("airtime/verify", credentials);
  },
  vend(credentials) {
    return Api().post("airtime/vend", credentials);
  },
};
