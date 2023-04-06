<template>
<div v-if="visible" class="wooSpeadBar">
    <h1>WooCommerce / Spreadshirt sync</h1>
    <label for="key">Spreadshirt API key</label><br>
    <input type="text" name="key" id="">
    <br>
    <br>
    <label for="key">Shop ID</label><br>
    <input type="text" name="key" id="">
    <br>
    <br>
    <label for="slug">Checkout page slug</label><br>
    <input type="text" name="slug" id="" placeholder="checkout">
</div>
</template>

<script>
export default {
    data() {
        return {
            loadingproducts: false,
            deletingProducts: false,
            loadingcategories: false,
            deletingcategories: false,
            visible: false
        }
    },

    created() {
        const urlParams = new URLSearchParams(window.location.search)
        const postType = urlParams.get('page')
        if (postType === 'wooSpread_plugin') {
            this.visible = true
        }
    },

    methods: {
        importItems(items) {
            this['loading' + items] = true
            const url = myVueObj.rest_url + 'api/v1/get-' + items;
            fetch(url, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                })
                .then((response) => {
                    console.log(response)
                    this['loading' + items] = false
                    location.reload();
                })
                .catch(error => console.error(error));
        },

        deleteItems(items) {
            this['deleting' + items] = true
            const url = myVueObj.rest_url + 'api/v1/delete-' + items;
            fetch(url, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                })
                .then((response) => {
                    console.log(response)
                    this['deleting' + items] = false
                    location.reload();
                })
                .catch(error => console.error(error));
        },
    }
};
</script>
