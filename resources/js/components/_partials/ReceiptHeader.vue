<template>
  <div class="row">
    <div class="col-xs-6 col-sm-6 col-md-6 text-left">
      <img src="ui/assets/img/logo.png" alt="Cardcom Logo">
      <address>
        Email: cardcomng@gmail.com
        <br>
        <abbr title="Phone">Tel:</abbr> 0805 893 0195
      </address>
    </div>
    <div class="col-xs-6 col-sm-6 col-md-6 text-right">
      <address>
        <strong>Date: </strong>{{ currentDate }}
        <br>
        <strong>Transaction ID #:</strong> {{ transaction_id }}
      </address>
    </div>
  </div>
</template>
<script>
export default {
  props: {
    transaction_id: {
      required: true
    }
  },
  computed: {
    currentDate() {
      var today = new Date();
      var date = today.getFullYear()+'-'+(today.getMonth()+1)+'-'+today.getDate();
      return date
    }
  }
}
</script>
<style scoped>
h5 {
  font-size: 1.3em;
  line-height: 0em;
  margin-bottom: 0px;
}
</style>