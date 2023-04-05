<template>
<div v-if="visible" class="wooSpeadBar">
    <div>
        <button :disabled="loadingproducts" @click="importItems('products')">{{ loadingproducts ? 'Importing' : 'Import products' }}</button>
        <button :disabled="deletingproducts" @click="deleteItems('products')">{{ deletingproducts ? 'Deleting' : 'Delete all products' }}</button>
    </div>
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
        const postType = urlParams.get('post_type')
        if (postType === 'product') {
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

<style lang="css" scoped>
.wooSpeadBar {
    position: fixed;
    bottom: 10px;
    right: 10px;
    width: auto;
    background-color: red;
    padding: 1em;
}
</style>
