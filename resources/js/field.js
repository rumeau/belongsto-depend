Nova.booting((Vue, router, store) => {
  Vue.component('index-belongsto-depend', require('./components/IndexField'))
  Vue.component('detail-belongsto-depend', require('./components/DetailField'))
  Vue.component('form-belongsto-depend', require('./components/FormField'))
})
