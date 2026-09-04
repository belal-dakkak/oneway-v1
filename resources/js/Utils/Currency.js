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
    static SYP = {
        round: true,
        label: 'syp'
    };

    static exchange(value, rate, isInt = false, forceRound = false)
    {
        let result = 0.00;

        value = parseFloat(value);

        if(rate > Currency.USD.rate)
            result = Currency.AED.round || forceRound ? Math.round(value * rate) : value * rate;
        else
            result = Currency.USD.round || forceRound ? Math.round(value * rate) : value * rate;

        const decimals = rate >= 100 ? 0 : 2;
        return isInt ? result : result.toFixed(decimals);
    }

    static decimalsFor(code)
    {
        return String(code || '').toUpperCase() === 'SYP' ? 0 : 2;
    }

    static inputStep(code)
    {
        return Currency.decimalsFor(code) === 0 ? '1' : '0.01';
    }

    static normalizeInput(value, code)
    {
        return Currency.normalize(value, Currency.decimalsFor(code));
    }

    static formatAmount(value, code)
    {
        const decimals = Currency.decimalsFor(code);
        const amount = Currency.number(value);

        return amount.toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
    }

    static formatFromUsd(value, rate, code)
    {
        const converted = Currency.number(value) * Currency.validRate(rate);
        return Currency.formatAmount(converted, code);
    }

    static fromUsd(value, rate, decimals = 2)
    {
        return Currency.normalize(
            Currency.number(value) * Currency.validRate(rate),
            decimals
        );
    }

    static toUsd(value, rate, decimals = 2)
    {
        return Currency.normalize(
            Currency.number(value) / Currency.validRate(rate),
            decimals
        );
    }

    static normalize(value, decimals = 2)
    {
        const precision = Number.isInteger(Number(decimals)) ? Number(decimals) : 2;
        return Currency.number(value).toFixed(precision);
    }

    static number(value)
    {
        const amount = Number(String(value ?? 0).replace(/,/g, ''));
        return Number.isFinite(amount) ? amount : 0;
    }

    static validRate(rate)
    {
        const exchangeRate = Number(rate);
        return Number.isFinite(exchangeRate) && exchangeRate > 0 ? exchangeRate : 0;
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
            case Currency.SYP.label:
                result = Math.round(value);
                break;
            default:
                result = value;
        }

        return (result).toFixed(label === Currency.SYP.label ? 0 : 2);
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
