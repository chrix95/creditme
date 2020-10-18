import Api from "./Api";

export default {
  getBundles(code) {
    return Api().get(`data/bundles/${code}`);
  },
  verify(credentials) {
    return Api().post("data/verify", credentials);
  },
  vend(credentials) {
    return Api().post("data/vend", credentials);
  },
};
