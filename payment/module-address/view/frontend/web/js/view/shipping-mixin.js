/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See LICENSE.txt for license details.
 */
/*global define*/
define(
    [
        'jquery',
        'underscore',
        'Magento_Checkout/js/model/quote',
        'mage/url',
        'mage/translate'
    ],
    function ($, _, quote, urlBuilder, $t) {
        var fieldsMap = {
            'street[0]': 'street.0',
            'street[1]': 'street.1',
            'city': 'city',
            'postcode': 'postcode',
            'region': 'region',
            'region_id': 'region_id'
        };

        return function (Component) {

            if (!window.checkoutConfig.addressVerification) {
                // bypass component extension if DAV not enabled
                return Component;
            }

            return Component.extend({
                setShippingInformation: function () {
                    var originalAction = this._super.bind(this);

                    if (!this.validateShippingInformation()) {
                        return;
                    }

                    $('body').trigger('processStart');
                    $.when(this.cybersourceValidation())
                        .then(originalAction)
                        .always(function () {
                            $('body').trigger('processStop');
                        });

                },
                cybersourceValidation: function () {
                    var validationDeferred = $.Deferred(),
                        shippingAddress = quote.shippingAddress(),
                        postData
                    ;

                    postData = {
                        city: shippingAddress.city,
                        country: shippingAddress.countryId,
                        firstname: shippingAddress.firstname,
                        lastname: shippingAddress.lastname,
                        postcode: shippingAddress.postcode,
                        region_id: shippingAddress.regionId,
                        street1: shippingAddress.street[0],
                        street2: shippingAddress.street[1],
                        telephone: shippingAddress.telephone
                    };

                    $.post(
                        urlBuilder.build('cybersourcea/index/address'),
                        postData,
                        'json'
                    ).then(
                        function (data) {
                            if (!data || !data.isValid) {
                                this.addressVerificationPopup(data.message, false, validationDeferred);
                                validationDeferred.reject();
                                return;
                            }

                            if (!data.needUpdate) {
                                validationDeferred.resolve();
                                return;
                            }

                            $('body').trigger('processStop');

                            if (data.needForce) {
                                this.addressVerificationPopup(data.message, data.normalizationData, validationDeferred);
                                return;
                            }

                            this.addressVerificationOptional(data.message, data.normalizationData, validationDeferred);
                        }.bind(this)
                    );

                    return validationDeferred.promise();
                },
                addressVerificationOptional: function (message, data, deferred) {
                    var that = this;
                    $('<div id="address-verifcation-modal"></div>')
                        .html(message)
                        .modal({
                            title: $t('Shipping Address Verification Message'),
                            autoOpen: true,
                            buttons: [
                                {
                                    text: $t('Normalize'),
                                    attr: {
                                        'data-action': 'confirm'
                                    },
                                    'class': 'action-primary',
                                    click: function () {
                                        that.normalizeAddress(data);
                                        this.closeModal();
                                        deferred.resolve();
                                    }
                                },
                                {
                                    text: $t('Continue'),
                                    attr: {
                                        'data-action': 'confirm'
                                    },
                                    'class': 'action-primary',
                                    click: function () {
                                        this.closeModal();
                                        deferred.resolve();
                                    }
                                }
                            ]
                        });
                },
                addressVerificationPopup: function (message, data, deferred) {
                    var that = this;

                    if (!data) {
                      $('<div id="address-verifcation-modal"></div>')
                            .html(message)
                            .modal({
                                title: $t('Shipping Address Verification Message'),
                                autoOpen: true
                            });
                        return;
                    }

                    $('<div id="address-verifcation-modal"></div>')
                        .html(message)
                        .modal({
                            title: $t('Shipping Address Verification Message'),
                            autoOpen: true,
                            buttons: [{
                                text: $t('Confirm'),
                                attr: {
                                    'data-action': 'confirm'
                                },
                                'class': 'action-primary',
                                click: function () {
                                    that.normalizeAddress(data);
                                    deferred.resolve();
                                    this.closeModal();
                                }
                            }]
                        });
                },
                normalizeAddress: function (data) {

                    if (typeof data['street[0]'] !== 'undefined') {
                        quote.shippingAddress().street[0] = data['street[0]'];
                        quote.shippingAddress().street[0] = data['street[0]'];
                    }
                    if (typeof data['street[1]'] !== 'undefined') {
                        if (data['street[1]'] !== '') {
                            quote.shippingAddress().street[1] = data['street[1]'];
                        } else {
                            quote.shippingAddress().street = quote.shippingAddress().street.splice(0, 1);
                        }
                    }
                    if (typeof data['city'] !== 'undefined') {
                        quote.shippingAddress().city = data['city'];
                    }
                    if (typeof data['postcode'] !== 'undefined') {
                        quote.shippingAddress().postcode = data['postcode'];
                    }
                    if (typeof data['region'] !== 'undefined') {
                        quote.shippingAddress().region = data['region'];
                    }
                    if (typeof data['region_id'] !== 'undefined') {
                        quote.shippingAddress().regionId = data['region_id'];
                    }
                    if (typeof data['region_code'] !== 'undefined') {
                        quote.shippingAddress().regionCode = data['region_code'];
                    }

                    //updates inline form fields
                    _.each(data, this.setSourceData.bind(this));
                },
                setSourceData: function (value, key) {
                    this.source.set('shippingAddress.' + this.getMappedFieldName(key), value);
                },
                getMappedFieldName: function (key) {
                    if (typeof fieldsMap[key] == 'undefined') {
                        return key;
                    }
                    return fieldsMap[key];
                }
            });
        }
    }
);
