import Api from "./Api";

export default {
  verify(credentials) {
    return Api().post("power/verify", credentials);
  },
  vend(credentials) {
    return Api().post("power/vend", credentials);
  },
};
