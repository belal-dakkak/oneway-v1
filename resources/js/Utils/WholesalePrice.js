const WholesalePrice = {
    synchronize(products, changedProduct) {
        if (!Array.isArray(products) || !changedProduct || !changedProduct.model_id) {
            return products
        }

        products.forEach((product) => {
            if (product !== changedProduct && product.model_id === changedProduct.model_id) {
                product.price = changedProduct.price
            }
        })

        return products
    },
}

export default WholesalePrice
