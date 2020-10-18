import Api from "./Api";

export default {
  getBundles(code) {
    return Api().get(`tv/get-tv-info/${code}`);
  },
  verify(credentials) {
    return Api().post("tv/verify", credentials);
  },
  vend(credentials) {
    return Api().post("tv/vend", credentials);
  },
};
