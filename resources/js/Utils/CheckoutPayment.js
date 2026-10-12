export function defaultPaymentMethod(country, commerce = {})
{
    return country !== 'SY' && Boolean(commerce.card_available) ? 'card' : 'cod'
}

export function reconcilePaymentMethod(current, country, cardAvailable, touched)
{
    if (country === 'SY' || !cardAvailable) return 'cod'
    return touched ? current : 'card'
}
