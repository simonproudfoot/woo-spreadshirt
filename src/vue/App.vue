<template>
<div>
    <div>
        <h1>Products</h1>
        <button :disabled="loadingproducts" @click="importItems('products')">{{ loadingproducts ? 'Importing' : 'Import products' }}</button>
        <button :disabled="deletingproducts" @click="deleteItems('products')">{{ deletingproducts ? 'Deleting' : 'Delete all products' }}</button>
    </div>
    <div>
        <h1>Categories</h1>
        <button :disabled="loadingcategories" @click="importItems('categories')">{{ loadingcategories ? 'Importing' : 'Import categories' }}</button>
        <button :disabled="deletingcategories" @click="deleteItems('categories')">{{ deletingcategories ? 'Deleting' : 'Delete all categories' }}</button>
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
                })
                .catch(error => console.error(error));
        },
    }
};
</script>

<style lang="css" scoped>
</style>
