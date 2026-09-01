<?php

namespace App\Classes;

class RefundPolicy
{
    const AR = 1;
    const EN = 2;

    private static $ar = [
        'terms' => [
            'مدة الاستبدال 3 أيام من تاريخ الفاتورة',
            'أن تكون السلع المراد استبدالها بحالة جيدة وقابلة للعرض بغلافها وبطاقاتها ومرفقة بايصال الشراء الأصلي',
            'أن تكون السلع المراد استبدالها  غير مطابقة للمواصفات القياسية أو بها خلل أو عيب لا يكون ظاهرا عند الشراء',
            'لا يوجد لدينا استرداد نقدي',
            'يجب استلام الطلبية خلال مدة اقصاها 10 ايام من تاريخ الطلب والا تلغى الطلبية بدون استرجاع العربون',
        ],
    ];

    private static $en = [
        'terms' => [
            'The exchange period is 3 days from the date of the invoice',
            'The goods to be exchanged must be in good condition, displayable with their packaging and tags, and accompanied by the original purchase receipt',
            'The goods to be replaced do not conform to standard specifications or have a defect or defect that is not apparent upon purchase',
            'We do not have cashback',
            'The order must be received within a maximum period of 10 days from the date of the order, otherwise the order will be canceled without refunding the deposit',
        ],
    ];

    /**
     * Get refund policy terms
     *
     * It can be used as following:
     * ```
     * $policy = RefundPolicy::getTerms(RefundPolicy::AR | RefundPolicy::EN);
     *
     * $ar_terms = $policy['ar']['terms'];
     * $en_terms = $policy['en']['terms'];
     * ```
     *
     * @param int $lang Indicates returned language. Possible values could
     * be `RefundPolicy::AR` to return Arabic version, `RefundPolicy::EN`
     * to return English version, and `RefundPolicy::AR | RefundPolicy::EN`
     * to return terms in both languages.
     *
     * @return array Array of policy terms language version(s)
     * */
    public static function getTerms(int $lang) : array
    {
        $policy = [];

        $reqAr = $lang & static::AR;
        $reqEn = $lang & static::EN;

        if($reqAr)
            $policy['ar'] = static::$ar;
        if($reqEn)
            $policy['en'] = static::$en;


        return $policy;
    }
}

