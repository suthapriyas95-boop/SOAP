define([
        'jquery',
        'transparent',
        'Magento_Ui/js/modal/modal'
    ],
    function ($) {
        $.widget('cybersource.transparent_iframe', $.mage.transparent, {
            options: {
                hiddenFormTmpl:
                    '<form target="<%= data.target %>" action="<%= data.action %>" method="POST" ' +
                    'hidden enctype="application/x-www-form-urlencoded" class="no-display">' +
                    '<% _.each(data.inputs, function(val, key){ %>' +
                    '<input value="<%- val %>" name="<%- key %>" type="hidden">' +
                    '<% }); %>' +
                    '</form>'
            },
            _iFrameContainer: null,
            _preparePaymentData: function (data) {
                return data;
            },
            _create: function () {
                this._super();

                this._iFrameContainer = $('#' + this.options.gateway + '-transparent-iframe-container').modal({
                    autoOpen: false,
                    buttons: [],
                    closed: this._closeIframe.bind(this),
                    modalCloseBtnHandler: this._closeModalBtn.bind(this),
                    clickableOverlay: false
                })

            },
            _postPaymentToGateway: function (response) {
                var $iframeSelector = $('[data-container="' + this.options.gateway + '-transparent-iframe"]'),
                    that = this,
                    data,
                    tmpl,
                    iframe,
                    $form
                ;

                data = response;
                tmpl = this.hiddenFormTmpl({
                    data: {
                        target: encodeURIComponent(($iframeSelector.attr('name'))),
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

                iframe.off('load').on('load', this._iframeLoadHandler.bind(this));

                encodeURIComponent($form = $(tmpl).appendTo(iframe));
                $form.submit();
                iframe.html('');
            },
            _iframeLoadHandler: function (event) {
                var iframe = event.target,
                    iframeLocation
                ;
                if (!this.options.context) {
                    return;
                }

                try {
                    // trying to access iframe's href to check is it on CyberSource
                    iframeLocation = iframe.contentWindow.location.href;
                    if (iframeLocation === 'about:blank') {
                        return;
                    }

                } catch (e) {
                    this.options.context.iframeLoadHandler.bind(this.options.context)();
                    return;
                }

                //we have returned back to placeOrder
                this._iFrameContainer.modal('closeModal');
                this.options.context.iframeReturnHandler.bind(this.options.context)();

            },
            _closeIframe: function () {
                this._iFrameContainer.find('iframe').html('');
                $('[data-container="' + this.options.gateway + '-transparent-iframe"]').off('submit');
                if (this.options.context) {
                    this.options.context.iframeCloseHandler.bind(this.options.context)();
                }

            },
            _closeModalBtn: function () {
                if (this.options.context && this.options.context.iframeCloseBtnHandler) {
                    this.options.context.iframeCloseBtnHandler.bind(this.options.context)();
                    return;
                }
                this._iFrameContainer.modal('closeModal');
            }
        });

        return $.cybersource.transparent_iframe;
    });
