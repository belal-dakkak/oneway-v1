function jsonToFormData(obj, formData, parentKey) {
    formData = formData || new FormData();

    for (let key in obj) {
        if (obj.hasOwnProperty(key)) {
            let propName = parentKey ? `${parentKey}[${key}]` : key;

            if (typeof obj[key] === 'object' && !(obj[key] instanceof File)) {
                jsonToFormData(obj[key], formData, propName); // Recursive call for nested objects
            } else {
                formData.append(propName, obj[key]);
            }
        }
    }

    return formData;
}

class Receipt
{
    static apiHost = 'http://localhost:12354';
	static contentType = 'application/json; charset=utf-8';

    static async printOrder(order)
    {
        const body = JSON.stringify(Receipt.orderPayload(order));

		await fetch(`${Receipt.apiHost}/api/orders`, {
			body: body,
			headers: {
				'Access-Control-Allow-Origin': '*',
				'Content-Type': Receipt.contentType
			},
			method: 'POST'
		}).then(response => {

        })
        .catch(error => {

            throw new Error('Failed to print')
        });
        /*await axios.post(`${Receipt.apiHost}/api/orders`, {
            body: body,
			headers: {
        		'Access-Control-Allow-Origin': '*',
				'Content-Type': 'multipart/form-data'
      		},
        })
        .then(response => {

        })
        .catch(error => {
            throw new Error('Failed to print')
        });*/
    }

    static orderPayload(order)
    {
        if (!order || typeof order !== 'object' || Array.isArray(order)) {
            throw new TypeError('Order payload must be an object');
        }

        if (!Array.isArray(order.products)) {
            throw new TypeError('Order products must be an array');
        }

        const products = [...order.products];
        const productsCount = products.length;

        return {
            ...order,
            product_name: ' ',
            products,
            products_count: productsCount,
            compact_layout: productsCount > 0 && productsCount <= 2,
        };
    }

    static async printProduct(product)
    {

        const body = JSON.stringify(product);

		await fetch(`${Receipt.apiHost}/api/products`, {
			body: body,
			headers: {
				'Access-Control-Allow-Origin': '*',
				'Content-Type': Receipt.contentType
			},
			method: 'POST'
		}).then(response => {

        })
        .catch(error => {
            throw new Error('Failed to print')
        });
        /*await axios.post(`${Receipt.apiHost}/api/products`, {
            body: body,
			headers: {
        		'Access-Control-Allow-Origin': '*',
				'Content-type': 'multipart/form-data'
      		},
        })
        .then(response => {

        })
        .catch(error => {
            throw new Error('Failed to print')
        });*/
    }

    static async printProductMulti(product, times = 1)
    {

        product.times = times;
        const body = JSON.stringify(product);
		
		await fetch(`${Receipt.apiHost}/api/products/multi`, {
			body: body,
			headers: {
				'Access-Control-Allow-Origin': '*',
				'Content-Type': Receipt.contentType
			},
			method: 'POST'
		}).then(response => {

        })
        .catch(error => {
            throw new Error('Failed to print')
        });
        /*await axios.post(`${Receipt.apiHost}/api/products/multi`, {
            body: body,
			headers: {
        		'Access-Control-Allow-Origin': '*',
				'Content-type': 'multipart/form-data'
      		},
        })
        .then(response => {

        })
        .catch(error => {
            throw new Error('Failed to print')
        });*/
    }
}


export default Receipt;
