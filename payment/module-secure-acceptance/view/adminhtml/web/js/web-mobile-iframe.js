define([
    'jquery',
    'CyberSource_SecureAcceptance/js/web-mobile',
    'Magento_Ui/js/modal/modal'
], function ($) {
    'use strict';

    $.widget('cybersource.webmobileiframe', $.cybersource.webmobile, {
        _iFrameContainer: null,
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
            expireYearLength: 2
        },
        _create: function () {
            this._super();

            this._iFrameContainer = $('#' + this.options.gateway + '-transparent-iframe-container').modal({
                autoOpen: false,
                buttons: [],
                closed: this._closeIframe.bind(this)
            })

        },
        _postPaymentToGateway: function (response) {
            var $iframeSelector = $('[data-container="' + this.options.gateway + '-transparent-iframe"]'),
                data,
                tmpl,
                iframe,
                $form
            ;
            var response = JSON.parse(decodeURIComponent(response));
            data = response[this.options.gateway].fields;
            tmpl = this.hiddenFormTmpl({
                data: {
                    target: encodeURIComponent($iframeSelector.attr('name')),
                    action: this.options.cgiUrl,
                    inputs: data
                }
            });

            iframe = $iframeSelector
                .on('submit', function (event) {
                    event.stopPropagation();
                    iframe.appendTo(this._iFrameContainer);
                    this._iFrameContainer.modal('openModal');
                }.bind(this));

            iframe.show();

            encodeURIComponent($form = $(tmpl).appendTo(iframe));
            $form.submit();
            iframe.html('');
        },
        _closeIframe: function(){
            this._iFrameContainer.find('iframe').html('');
            $('[data-container="' + this.options.gateway + '-transparent-iframe"]').off('submit');
        }
    });

    return $.cybersource.webmobileiframe;
});
