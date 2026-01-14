define([
    'jquery',
    'CyberSource_SecureAcceptance/js/flex/get-token',
	'CyberSource_SecureAcceptance/js/vault/enabler',
	'CyberSource_SecureAcceptance/js/cc-inputs-enabler',
    'flex-microform',
    'CyberSource_SecureAcceptance/js/model/credit-card-type-map',
    'CyberSource_SecureAcceptance/js/model/credit-card-validation/microform-credit-card-validation',
    'transparent'
], function (
        $,
        getToken,
        vaultEnabler,
        inputsEnabler,
        Flex,
        cardTypeMap
    ) {
    'use strict';

    $.widget('cybersource.microform', $.mage.transparent, {
        
        options: {
            editFormSelector: '#edit_form',
            hiddenFormTmpl:
                '<form target="<%= data.target %>" action="<%= data.action %>"' +
                'method="POST" hidden' +
                'enctype="application/x-www-form-urlencoded" class="no-display">' +
                    '<% _.each(data.inputs, function(val, key){ %>' +
                    '<input value="<%= val %>" name="<%= key %>" type="hidden">' +
                    '<% }); %>' +
                '</form>',
            cgiUrl: null,
            orderSaveUrl: null,
            controller: null,
            gateway: null,
            dateDelim: null,
            cardFieldsMap: null,
            expireYearLength: 2,
            containerSelector: null,
            containerSelectorCvn: null
        },
        
        /**
         * @private
         */
        _create: function () {
            this._super();
            this.options.containerSelector = '#' + this.options.gateway + '_cc_number'
            this.options.containerSelectorCvn = '#' + this.options.gateway + '_cc_cid'
            this._prepareCaptureContext();
        },

        _prepareCaptureContext: function () {
            var data = {
                'method': this.options.gateway,
                'additional_data':$.extend({}, {
                    'form_key': FORM_KEY,
                })
            };

            getToken(data,this.options.orderSaveUrl).then(function(token,url){
                try {
                    var flex = new Flex.Flex(token),
                        microform = flex.microform({styles: this.getStyles()}),
                        number = microform.createField(
                            'number',
                            {
                                placeholder: '',
                                autoformat: false
                            }
                        );

                    number.load(this.options.containerSelector);

                    number.on('change', this.cardTypeChangeHandler.bind(this));
                    number.on('change', this.validationChangeHandler.bind(this));
                    number.on('autocomplete', this.cardAutofillHandler.bind(this));

                    
                    this.microform = microform;
                    this.placeOrderUrl = url;
                    var securityCode = microform.createField(
                        'securityCode',
                        {
                            placeholder: '',
                            styles: this.getStyles()
                        }
                    );

                    securityCode.load(this.options.containerSelectorCvn);

                    securityCode.on('change', this.validationChangeHandlerCvn.bind(this));

                } catch (e) {
                    console.log(e);
                }

            }.bind(this)).fail(function(response){
                if(response.error)
                {
                    window.location.replace(response.redirect_url);
                }
                return;
            });
        },

        _orderSave: function () {
            inputsEnabler(this.options.gateway, false);
            
            this.microform.createToken(
                {
                    cardExpirationMonth: this.padMonth(this.creditCardExpMonth()),
                    cardExpirationYear: this.creditCardExpYear()
                },
                function (err, response) {
                    
                    if (err) {
                        console.log(err);
                        this._processErrors({'error_messages' : err.message});
                        window.location.reload();
                        return;
                    }
                    this.microformResponse = response;
                    $.ajax({
                        url: this.placeOrderUrl,
                        type: 'post',
                        context: this,
                        data: this.getData(),
                        dataType: 'json',
                        success: function (response) {
                            
                            /*Skip if both success and error are true or both are false */
                            if ((response.success || response.error) && (!response.success || !response.error)) {
                                window.location.replace(response.redirect_url);
                                return;
                            }

                            $('body').trigger('processStop');
                            
                        }.bind(this)
                    });
                }.bind(this)
            );

            return;
        },

        creditCardExpMonth: function(){
            var month= this.element.find('[data-container="' + this.options.gateway + '-cc-month"]').val();
            return month;
        },
        
        creditCardExpYear: function(){
            var year = this.element.find('[data-container="' + this.options.gateway + '-cc-year"]').val();
            if (year.length > this.options.expireYearLength) {
                year = year.substring(year.length - this.options.expireYearLength);
            }
            return year;
        },

        getData: function() {

            var data = {
                    'method': this.options.gateway,
                    'order_data': $(this.options.editFormSelector).serialize(),
                    'expDate': this.padMonth(this.creditCardExpMonth()) + '-' + this.creditCardExpYear(),
                    'token': this.microformResponse,
                    'ccType': this.ccType()
                };
        
            return data;
        },

        padMonth: function(value) {
            if (parseInt(value, 10) < 10) {
                return '0' + value;
            }
        
            return '' + value;
        },

        cardAutofillHandler: function(cardData) {

            if (!cardData) {
                return;
            }
        
            if (cardData.expirationMonth && parseInt(cardData.expirationMonth, 10)) {
                creditCardExpMonth(parseInt(cardData.expirationMonth, 10));
            }
        
            if (cardData.expirationYear) {
                creditCardExpYear(cardData.expirationYear);
            }
        },
        
        validationChangeHandler: function(data) {
            $(this.options.containerSelector).attr('data-valid', data.valid);
        },
        validationChangeHandlerCvn: function(data) {
            $(this.options.containerSelectorCvn).attr('data-valid', data.valid);
        },

        padMonth: function(value) {
            if (parseInt(value, 10) < 10) {
                return '0' + value;
            }
        
            return '' + value;
        },
        
        getCode: function(){
            return this.options.gateway;
        },

        getStyles: function() {
        
            return {
                'input': {
                    'font-size': '14px',
                    'color': '#3A3A3A'
                },
                '::placeholder': {
                    'color': 'black',
                    'line-height': '10px'
                },
                ':focus': {
                    'color': 'black'
                },
                ':disabled': {
                    'cursor': 'not-allowed',
                }
            };
        },
        
        cardTypeChangeHandler: function(event) {
                var card, magentoType;
        
                if (!event.card || event.card.length === 0) {
                    return;
                }
        
                card = event.card[0];
                magentoType = cardTypeMap.getMagentoType(card.cybsCardType);

                $(this.options.containerSelector).attr('valid-card-type', magentoType === this.ccType());
            }
    });

    return $.cybersource.microform;
});