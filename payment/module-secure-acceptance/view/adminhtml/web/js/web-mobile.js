define([
    'jquery',
    'CyberSource_SecureAcceptance/js/vault/enabler',
    'transparent'
], function ($, vaultEnabler) {
    'use strict';

    $.widget('cybersource.webmobile', $.mage.transparent, {
        options: {
            editFormSelector: '#edit_form',
            hiddenFormTmpl:
                '<form action="<%= data.action %>" ' +
                'method="POST" hidden ' +
                'enctype="application/x-www-form-urlencoded" class="no-display"> ' +
                '<% _.each(data.inputs, function(val, key){ %>' +
                '<input value="<%= val %>" name="<%= key %>" type="hidden"> ' +
                '<% }); %>' +
                '</form>',
            cgiUrl: null,
            orderSaveUrl: null,
            controller: null,
            gateway: null
        },
        _orderSave: function () {
            var postData = {
                'form_key': FORM_KEY,
                'vault_enabled': vaultEnabler(this.element, this.options.gateway),
                'order_data': $(this.options.editFormSelector).serialize()
            }, that = this;

            $.ajax({
                url: this.options.orderSaveUrl,
                type: 'post',
                context: this,
                data: postData,
                dataType: 'json'
            }).done(
                function (response) {
                    if (response.success && response[that.options.gateway]) {
                        this._postPaymentToGateway(encodeURIComponent(JSON.stringify(response)));
                    } else {
                        this._processErrors(response);
                    }
                }
            ).always(
                function () {
                    $('body').trigger('processStop');

                }
            );

        },
        _postPaymentToGateway: function (response) {
            var $iframeSelector = $('[data-container="' + this.options.gateway + '-transparent-iframe"]'),
                data,
                tmpl;
                
            var response = JSON.parse(decodeURIComponent(response));
            
            tmpl = this.hiddenFormTmpl({
                data: {
                    target: $iframeSelector.attr('name'),
                    action: this.options.cgiUrl,
                    inputs: response[this.options.gateway].fields
                }
            });

            var $tmpl = $(tmpl);
            $tmpl
                .appendTo($('body'))
                .submit();
        }
    });

    return $.cybersource.webmobile;
});
