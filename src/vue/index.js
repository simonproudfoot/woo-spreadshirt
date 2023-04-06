import Vue from 'vue';
import productImport from './productImport.vue';
import pluginSettings from './pluginSettings.vue';
new Vue({
  el: '#wpfooter',
  render: h => h(productImport),
});


new Vue({
  el: '#wooSpread-plugin',
  render: h => h(pluginSettings),
});
