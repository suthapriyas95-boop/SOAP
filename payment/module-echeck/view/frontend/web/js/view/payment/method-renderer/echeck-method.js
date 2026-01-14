/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See LICENSE.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'uiRegistry',
        'mage/translate',
        'jquery'
    ],
    function (Component, registry, $t, $) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'CyberSource_ECheck/payment/form',
                code: 'cybersourceecheck',
                active: false,
                checkBankTransitNumber: '',
                checkNumber: '',
                checkAccountNumber: '',
                driversLicenseNumber: ''
            },

            initObservable: function () {

                this._super().observe([
                    'active',
                    'checkBankTransitNumber',
                    'checkNumber',
                    'checkAccountNumber',
                    'driversLicenseNumber'
                ]);
                return this;
            },

            getCode: function () {
                return this.code;
            },

            getTitle: function () {

              return window.checkoutConfig.payment[this.getCode()].title;
            },

            /**
             * Check if payment is active
             *
             * @returns {Boolean}
             */
            isActive: function () {
                var active = (this.getCode() === this.isChecked());

                this.active(active);

                return active;
            },

            getData: function () {
                return {
                    'method': this.getCode(),
                    'additional_data': {
                        'check_bank_transit_number': this.checkBankTransitNumber(),
                        'check_number': this.checkNumber(),
                        'check_account_number': this.checkAccountNumber(),
                        'drivers_license_number': this.driversLicenseNumber(),
                        'drivers_license_country': registry.get("checkoutProvider").echeckDriversLicense.country_id,
                        'drivers_license_state': registry.get("checkoutProvider").echeckDriversLicense.region_id
                    }
                };
            },

            /**
             * Get image url for CVV
             * @returns {String}
             */
            getECheckImageUrl: function () {
                return window.checkoutConfig.payment[this.getCode()].echeckImage;
            },

            /**
             * Get Echeck image
             * @returns {String}
             */
            getECheckImageHtml: function () {
                return '<img src="' + this.getECheckImageUrl() +
                    '" alt="' + $t('Check Visual Reference') +
                    '" title="' + $t('Check Visual Reference') +
                    '" />';
            },

            validate: function () {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },

            isDriversLicenseNumberRequired: function () {
                return !!parseInt(window.checkoutConfig.payment[this.getCode()].isDriversLicenseNumberRequired);
            },

            isCheckNumberRequired: function () {
                return !!parseInt(window.checkoutConfig.payment[this.getCode()].isCheckNumberRequired);
            }
        });
    }
);
