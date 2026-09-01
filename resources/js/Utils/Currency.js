class Currency
{
    static AED = {
        round: true,
        label: 'aed'
    };
    static USD = {
        round: false,
        label: 'usd',
        rate: 1
    };

    static exchange(value, rate, isInt = false, forceRound = false)
    {
        let result = 0.00;

        value = parseFloat(value);

        if(rate > Currency.USD.rate)
            result = Currency.AED.round || forceRound ? Math.round(value * rate) : value * rate;
        else
            result = Currency.USD.round || forceRound ? Math.round(value * rate) : value * rate;

        return isInt ? result : result.toFixed(2);
    }

    static format(value, label)
    {
        let result = 0.00;

        value = parseFloat(value);

        switch (label)
        {
            case Currency.USD.label:
                result = Currency.USD.round ? Math.round(value) : value;
                break;
            case Currency.AED.label:
                result = Currency.AED.round ? Math.round(value) : value;
                break;
            default:
                result = value;
        }

        return (result).toFixed(2);
    }

    static getExchangeMethod()
    {
        return (value, rate, isInt, forceRound = false) => Currency.exchange(value, rate, isInt, forceRound);
    }

    static getFormatMethod()
    {
        return (value, label) => Currency.format(value, label);
    }
}

export default Currency;

