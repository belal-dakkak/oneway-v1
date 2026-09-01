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

        order.product_name   = " ";
        order.products_count = order.products.length;

        const body = JSON.stringify(order);

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

