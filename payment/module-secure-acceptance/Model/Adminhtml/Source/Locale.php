<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Model\Adminhtml\Source;


class Locale implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'label' => __('Arabic'),
                'value' => 'ar-xn',
            ],
            [
                'label' => __('Catalan'),
                'value' => 'ca-es',
            ],
            [
                'label' => __('Chinese - Hong Kong'),
                'value' => 'zh-hk',
            ],
            [
                'label' => __('Chinese - Macau'),
                'value' => 'zh-mo',
            ],
            [
                'label' => __('Chinese - Mainland'),
                'value' => 'zh-cn',
            ],
            [
                'label' => __('Chinese - Singapore'),
                'value' => 'zh-sg',
            ],
            [
                'label' => __('Chinese - Taiwan'),
                'value' => 'zh-tw',
            ],
            [
                'label' => __('Croatian'),
                'value' => 'hr-hr',
            ],
            [
                'label' => __('Czech'),
                'value' => 'cz-cz',
            ],
            [
                'label' => __('Danish'),
                'value' => 'da-dk',
            ],
            [
                'label' => __('Dutch'),
                'value' => 'nl-nl',
            ],
            [
                'label' => __('English - United States of America'),
                'value' => 'en-us',
            ],
            [
                'label' => __('English - Australia'),
                'value' => 'en-au',
            ],
            [
                'label' => __('English - Great Britain'),
                'value' => 'en-gb',
            ],
            [
                'label' => __('English - Canada'),
                'value' => 'en-ca',
            ],
            [
                'label' => __('English - Ireland'),
                'value' => 'en-ie',
            ],
            [
                'label' => __('English - New Zealand'),
                'value' => 'en-nz',
            ],
            [
                'label' => __('Finnish'),
                'value' => 'fi-fi',
            ],
            [
                'label' => __('French'),
                'value' => 'fr-fr',
            ],
            [
                'label' => __('French - Canada'),
                'value' => 'fr-ca',
            ],
            [
                'label' => __('German'),
                'value' => 'de-de',
            ],
            [
                'label' => __('German - Austria'),
                'value' => 'de-at',
            ],
            [
                'label' => __('Greek'),
                'value' => 'el-gr',
            ],
            [
                'label' => __('Hebrew'),
                'value' => 'he-il',
            ],
            [
                'label' => __('Hungary'),
                'value' => 'hu-hu',
            ],
            [
                'label' => __('Indonesian'),
                'value' => 'id-id',
            ],
            [
                'label' => __('Italian'),
                'value' => 'it-it',
            ],
            [
                'label' => __('Japanese'),
                'value' => 'ja-jp',
            ],
            [
                'label' => __('Korean'),
                'value' => 'ko-kr',
            ],
            [
                'label' => __('Lao People\'s Democratic Republic'),
                'value' => 'lo-la',
            ],
            [
                'label' => __('Malaysian Bahasa'),
                'value' => 'ms-my',
            ],
            [
                'label' => __('Norwegian (Bokmal)'),
                'value' => 'nb-no',
            ],
            [
                'label' => __('Philippines Tagalog'),
                'value' => 'tl-ph',
            ],
            [
                'label' => __('Polish'),
                'value' => 'pl-pl',
            ],
            [
                'label' => __('Portuguese - Brazil'),
                'value' => 'pt-br',
            ],
            [
                'label' => __('Russian'),
                'value' => 'ru-ru',
            ],
            [
                'label' => __('Slovakian'),
                'value' => 'sk-sk',
            ],
            [
                'label' => __('Spanish'),
                'value' => 'es-es',
            ],
            [
                'label' => __('Spanish - Argentina'),
                'value' => 'es-ar',
            ],
            [
                'label' => __('Spanish - Chile'),
                'value' => 'es-cl',
            ],
            [
                'label' => __('Spanish - Colombia'),
                'value' => 'es-co',
            ],
            [
                'label' => __('Spanish - Mexico'),
                'value' => 'es-mx',
            ],
            [
                'label' => __('Spanish - Peru'),
                'value' => 'es-pe',
            ],
            [
                'label' => __('Spanish - United States of America'),
                'value' => 'es-us',
            ],
            [
                'label' => __('Swedish'),
                'value' => 'sv-se',
            ],
            [
                'label' => __('Thai'),
                'value' => 'th-th',
            ],
            [
                'label' => __('Turkish'),
                'value' => 'tr-tr',
            ],
            [
                'label' => __('Vietnamese'),
                'value' => 'vi-vn',
            ],
        ];
    }
}
