# REST ADMIN ORDER - COMPLETE CODE PART 3 (Cybersource\Payment Namespace)

Complete template files, JavaScript, CSS, configuration, and implementation guide.

---

## **PHASE 12: TEMPLATE FILES (6 FILES)**

### **43. view/adminhtml/templates/rest/flex_token.phtml**
```php
<?php
/** @var \Cybersource\Payment\Block\Rest\FlexTokenDisplay $block */
?>
<div id="cybersource-flex-payment" class="admin-payment-method">
    <h3><?php echo __('CyberSource Flex Microform'); ?></h3>
    
    <div class="flex-container">
        <div id="flex-token-display" class="token-display" style="display:none;">
            <p class="token-status success"><?php echo __('Token Generated Successfully'); ?></p>
            <div id="card-data-container" class="card-data">
                <!-- Card data will be displayed here -->
            </div>
        </div>

        <div id="flex-token-loading" class="token-loading" style="display:none;">
            <p><?php echo __('Generating token...'); ?></p>
        </div>

        <div id="flex-token-error" class="token-error" style="display:none;">
            <p class="error-message"></p>
        </div>

        <button type="button" id="generate-flex-token" class="action-primary">
            <?php echo __('Generate Flex Token'); ?>
        </button>
    </div>

    <script type="text/x-magento-init">
    {
        "#generate-flex-token": {
            "Cybersource_Payment/js/rest/token-generator": {
                "endpoint": "<?php echo $block->getTokenGenerationEndpoint(); ?>",
                "quoteId": "<?php echo $block->getRequest()->getParam('quote_id'); ?>",
                "clientLibrary": "<?php echo $block->getClientLibrary(); ?>",
                "clientIntegrity": "<?php echo $block->getClientIntegrity(); ?>"
            }
        }
    }
    </script>
</div>
```

### **44. view/adminhtml/templates/rest/sop_form.phtml**
```php
<?php
/** @var \Cybersource\Payment\Block\Rest\SopFormDisplay $block */
?>
<div id="cybersource-sop-payment" class="admin-payment-method">
    <h3><?php echo __('CyberSource Secure Order Post'); ?></h3>
    
    <div class="sop-container">
        <form id="cybersource-sop-form" method="post" action="<?php echo $block->getSopFormUrl(); ?>">
            <fieldset>
                <legend><?php echo __('Payment Information'); ?></legend>
                
                <div class="field">
                    <label for="cc_type"><?php echo __('Card Type'); ?></label>
                    <select name="cc_type" id="cc_type" class="required-entry">
                        <option value=""><?php echo __('-- Please Select --'); ?></option>
                        <?php foreach ($block->getCardTypes() as $code => $label): ?>
                            <option value="<?php echo $code; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="sop-form-fields" class="sop-fields">
                    <!-- Hidden form fields will be injected here -->
                </div>

                <div class="actions">
                    <button type="submit" class="action-primary"><?php echo __('Submit Payment'); ?></button>
                </div>
            </fieldset>
        </form>

        <div id="sop-loading" style="display:none;">
            <p><?php echo __('Processing payment...'); ?></p>
        </div>

        <div id="sop-error" class="message error" style="display:none;">
            <p class="error-message"></p>
        </div>
    </div>

    <script type="text/x-magento-init">
    {
        "#cc_type": {
            "Cybersource_Payment/js/rest/sop-request": {
                "endpoint": "<?php echo $block->getRequestDataEndpoint(); ?>",
                "quoteId": "<?php echo $block->getQuoteId(); ?>",
                "formSelector": "#cybersource-sop-form"
            }
        }
    }
    </script>
</div>
```

### **45. view/adminhtml/templates/rest/payment_info.phtml**
```php
<?php
/** @var \Cybersource\Payment\Block\Rest\PaymentMethodInfo $block */
?>
<div id="cybersource-payment-info" class="payment-info-block">
    <h3><?php echo __('CyberSource Payment Information'); ?></h3>
    
    <table class="payment-details">
        <tbody>
            <tr>
                <td class="label"><?php echo __('Transaction ID'); ?></td>
                <td class="value"><?php echo $block->getTransactionId(); ?></td>
            </tr>
            <tr>
                <td class="label"><?php echo __('Authorization Code'); ?></td>
                <td class="value"><?php echo $block->getAuthCode(); ?></td>
            </tr>
            <tr>
                <td class="label"><?php echo __('AVS Result'); ?></td>
                <td class="value"><?php echo $block->getAvsResult(); ?></td>
            </tr>
            <tr>
                <td class="label"><?php echo __('CVV Result'); ?></td>
                <td class="value"><?php echo $block->getCvvResult(); ?></td>
            </tr>
        </tbody>
    </table>
</div>
```

### **46. view/adminhtml/templates/rest/token_response.phtml**
```php
<div id="token-response-container" class="token-response" style="display:none;">
    <div class="response-success">
        <h4><?php echo __('Token Generated'); ?></h4>
        <p><?php echo __('Your payment token has been generated successfully.'); ?></p>
        <div id="response-data" class="response-details"></div>
    </div>
</div>

<div id="token-response-error" class="token-response-error" style="display:none;">
    <div class="response-error">
        <h4><?php echo __('Error'); ?></h4>
        <p id="error-message"></p>
    </div>
</div>
```

### **47. view/adminhtml/templates/rest/form_fields.phtml**
```php
<div id="sop-form-fields-container" class="form-fields">
    <?php
    /**
     * This template is used to display hidden SOP form fields
     * Fields will be injected via JavaScript from the REST API response
     */
    ?>
    <div id="hidden-fields"></div>
    
    <script type="text/javascript">
    // Form field injection will happen here via JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        var container = document.getElementById('hidden-fields');
        // Fields will be populated by JavaScript REST client
    });
    </script>
</div>
```

### **48. view/adminhtml/templates/rest/error_message.phtml**
```php
<div id="error-message-container" class="error-container" style="display:none;">
    <div class="message error">
        <div class="message-text">
            <strong><?php echo __('Error:'); ?></strong>
            <span id="error-text"></span>
        </div>
    </div>
</div>

<style>
#error-message-container {
    margin: 15px 0;
    padding: 10px;
    border: 1px solid #d1515d;
    background-color: #fffbfb;
}

.error-message {
    color: #d1515d;
    font-weight: bold;
}
</style>
```

---

## **PHASE 13: JAVASCRIPT FILES (6 FILES)**

### **49. view/adminhtml/web/js/rest/rest-client.js**
```javascript
define(['jquery', 'mage/url'], function($, url) {
    'use strict';

    return {
        /**
         * Make REST API request
         */
        request: function(endpoint, data, method) {
            method = method || 'POST';

            return $.ajax({
                url: url.build(endpoint),
                type: method,
                dataType: 'json',
                contentType: 'application/json',
                data: JSON.stringify(data),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });
        },

        /**
         * Generate token
         */
        generateToken: function(quoteId, storeId) {
            var data = {
                quote_id: quoteId
            };

            if (storeId) {
                data.store_id = storeId;
            }

            return this.request('/rest/V1/cybersource/admin/token/generate', data);
        },

        /**
         * Get SOP request data
         */
        getSopRequestData: function(quoteId, cardType, vaultEnabled, storeId) {
            var data = {
                quote_id: quoteId,
                cc_type: cardType,
                vault_enabled: vaultEnabled || false
            };

            if (storeId) {
                data.store_id = storeId;
            }

            return this.request('/rest/V1/cybersource/admin/sop/request-data', data);
        },

        /**
         * Submit SOP response
         */
        submitSopResponse: function(responseData) {
            return this.request('/rest/V1/cybersource/admin/sop/response', responseData);
        },

        /**
         * Place Flex order
         */
        placeFlexOrder: function(quoteId, token, cardData) {
            var data = {
                quote_id: quoteId,
                token: token,
                cc_type: cardData.cc_type,
                exp_date: cardData.exp_date,
                masked_pan: cardData.masked_pan
            };

            return this.request('/rest/V1/cybersource/admin/flex/place-order', data);
        },

        /**
         * Delete vault token
         */
        deleteVaultToken: function(publicHash, customerId) {
            var endpoint = '/rest/V1/cybersource/admin/vault/token/' + publicHash;
            return this.request(endpoint, { customer_id: customerId }, 'DELETE');
        },

        /**
         * Handle error response
         */
        handleError: function(error) {
            var errorMessage = 'An error occurred';

            if (error.responseJSON && error.responseJSON.error) {
                errorMessage = error.responseJSON.error;
            } else if (error.statusText) {
                errorMessage = error.statusText;
            }

            return errorMessage;
        }
    };
});
```

### **50. view/adminhtml/web/js/rest/token-generator.js**
```javascript
define(['jquery', 'Cybersource_Payment/js/rest/rest-client'], function($, restClient) {
    'use strict';

    return function(config) {
        var endpoint = config.endpoint;
        var quoteId = config.quoteId;
        var generateBtn = $('#generate-flex-token');

        generateBtn.on('click', function() {
            generateToken();
        });

        function generateToken() {
            var loading = $('#flex-token-loading');
            var display = $('#flex-token-display');
            var error = $('#flex-token-error');

            // Show loading
            loading.show();
            display.hide();
            error.hide();

            restClient.generateToken(quoteId).done(function(response) {
                if (response.success) {
                    displayToken(response);
                    loading.hide();
                    display.show();
                } else {
                    showError(response.error || 'Token generation failed');
                    loading.hide();
                    error.show();
                }
            }).fail(function(xhr) {
                var errorMsg = restClient.handleError(xhr);
                showError(errorMsg);
                loading.hide();
                error.show();
            });
        }

        function displayToken(response) {
            var container = $('#card-data-container');
            var html = '<p><strong>' + response.token.substring(0, 20) + '...</strong></p>';
            html += '<p class="info">' + response.client_library + '</p>';
            container.html(html);
        }

        function showError(message) {
            $('#flex-token-error .error-message').text(message);
        }
    };
});
```

### **51. view/adminhtml/web/js/rest/sop-request.js**
```javascript
define(['jquery', 'Cybersource_Payment/js/rest/rest-client', 'Cybersource_Payment/js/rest/form-validator'], 
    function($, restClient, formValidator) {
    'use strict';

    return function(config) {
        var endpoint = config.endpoint;
        var quoteId = config.quoteId;
        var formSelector = config.formSelector;

        var cardTypeSelect = $('#cc_type');

        cardTypeSelect.on('change', function() {
            requestFormFields();
        });

        function requestFormFields() {
            var cardType = cardTypeSelect.val();

            if (!cardType) {
                return;
            }

            var loading = $('<div class="loader"></div>');
            $(formSelector).after(loading);

            restClient.getSopRequestData(quoteId, cardType, false).done(function(response) {
                if (response.success) {
                    populateFormFields(response.fields);
                    loading.remove();
                } else {
                    showError(response.error || 'Failed to get request data');
                    loading.remove();
                }
            }).fail(function(xhr) {
                var errorMsg = restClient.handleError(xhr);
                showError(errorMsg);
                loading.remove();
            });
        }

        function populateFormFields(fields) {
            var container = $('#sop-form-fields');
            container.empty();

            $.each(fields, function(key, value) {
                var input = $('<input>')
                    .attr('type', 'hidden')
                    .attr('name', key)
                    .val(value);
                container.append(input);
            });
        }

        function showError(message) {
            $('#sop-error .error-message').text(message);
            $('#sop-error').show();
        }
    };
});
```

### **52. view/adminhtml/web/js/rest/flex-order.js**
```javascript
define(['jquery', 'Cybersource_Payment/js/rest/rest-client'], function($, restClient) {
    'use strict';

    return function(config) {
        var placeOrderBtn = $('#place-flex-order');

        if (placeOrderBtn.length) {
            placeOrderBtn.on('click', function() {
                placeOrder();
            });
        }

        function placeOrder() {
            var quoteId = $('input[name="quote_id"]').val();
            var token = $('input[name="flex_token"]').val();
            var cardType = $('input[name="cc_type"]').val();
            var expDate = $('input[name="cc_exp"]').val();
            var maskedPan = $('input[name="cc_masked"]').val();

            if (!token || !cardType) {
                showError('Please provide all required card information');
                return;
            }

            var cardData = {
                cc_type: cardType,
                exp_date: expDate,
                masked_pan: maskedPan
            };

            restClient.placeFlexOrder(quoteId, token, cardData)
                .done(function(response) {
                    if (response.success) {
                        // Redirect to order
                        window.location.href = response.redirect_url;
                    } else {
                        showError(response.error || 'Order creation failed');
                    }
                })
                .fail(function(xhr) {
                    var errorMsg = restClient.handleError(xhr);
                    showError(errorMsg);
                });
        }

        function showError(message) {
            $('#error-message-container #error-text').text(message);
            $('#error-message-container').show();
        }
    };
});
```

### **53. view/adminhtml/web/js/rest/form-validator.js**
```javascript
define(['jquery'], function($) {
    'use strict';

    return {
        /**
         * Validate card number (basic Luhn check)
         */
        validateCardNumber: function(cardNumber) {
            var digits = cardNumber.replace(/\D/g, '');
            if (digits.length < 13 || digits.length > 19) {
                return false;
            }

            var sum = 0;
            var isEven = false;

            for (var i = digits.length - 1; i >= 0; i--) {
                var digit = parseInt(digits.charAt(i), 10);

                if (isEven) {
                    digit *= 2;
                    if (digit > 9) {
                        digit -= 9;
                    }
                }

                sum += digit;
                isEven = !isEven;
            }

            return (sum % 10) === 0;
        },

        /**
         * Validate expiration date
         */
        validateExpirationDate: function(month, year) {
            var currentDate = new Date();
            var expDate = new Date(year, month, 0);
            return expDate > currentDate;
        },

        /**
         * Validate CVV
         */
        validateCvv: function(cvv, cardType) {
            var cvvLength = cardType === '003' ? 4 : 3; // AMEX has 4 digits
            return cvv.length === cvvLength;
        },

        /**
         * Validate form fields
         */
        validateForm: function(formSelector) {
            var errors = [];

            var cardNumber = $(formSelector + ' input[name="card_number"]').val();
            if (!cardNumber) {
                errors.push('Card number is required');
            } else if (!this.validateCardNumber(cardNumber)) {
                errors.push('Card number is invalid');
            }

            var month = $(formSelector + ' select[name="exp_month"]').val();
            var year = $(formSelector + ' select[name="exp_year"]').val();
            if (!this.validateExpirationDate(month, year)) {
                errors.push('Card has expired');
            }

            return errors;
        }
    };
});
```

### **54. view/adminhtml/web/js/rest/response-handler.js**
```javascript
define(['jquery', 'Cybersource_Payment/js/rest/rest-client'], function($, restClient) {
    'use strict';

    return function(config) {
        var responseHandler = {
            /**
             * Handle SOP response
             */
            handleSopResponse: function(responseData) {
                var loading = $('<div class="loader"></div>');
                $('body').append(loading);

                restClient.submitSopResponse(responseData)
                    .done(function(response) {
                        loading.remove();
                        if (response.success) {
                            // Redirect to order
                            window.location.href = response.redirect_url;
                        } else {
                            showError(response.error);
                        }
                    })
                    .fail(function(xhr) {
                        loading.remove();
                        var errorMsg = restClient.handleError(xhr);
                        showError(errorMsg);
                    });
            },

            /**
             * Handle token response
             */
            handleTokenResponse: function(response) {
                $('#token-response-container').show();
                $('#response-data').html(JSON.stringify(response, null, 2));
            },

            /**
             * Handle error
             */
            handleError: function(error) {
                showError(error);
            }
        };

        function showError(message) {
            var errorHtml = '<div class="message error"><div>' + message + '</div></div>';
            $('.page-main-actions').after(errorHtml);
        }

        return responseHandler;
    };
});
```

---

## **PHASE 14: CSS FILES (2 FILES)**

### **55. view/adminhtml/web/css/admin-payment.css**
```css
/* CyberSource REST Admin Payment Styles */

.admin-payment-method {
    margin: 20px 0;
    padding: 20px;
    border: 1px solid #e3e3e3;
    background-color: #f9f9f9;
}

.admin-payment-method h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 16px;
    color: #333;
    border-bottom: 2px solid #ed7d31;
    padding-bottom: 10px;
}

/* Flex Token Display */
#cybersource-flex-payment .flex-container {
    padding: 15px;
    background-color: #fff;
    border-radius: 4px;
}

#flex-token-display {
    margin-bottom: 15px;
    padding: 15px;
    background-color: #e8f5e9;
    border-left: 4px solid #4caf50;
}

#flex-token-display .token-status {
    color: #4caf50;
    font-weight: bold;
    margin: 0;
}

.card-data {
    margin-top: 10px;
    padding: 10px;
    background-color: #f5f5f5;
    font-family: monospace;
    font-size: 12px;
    overflow-x: auto;
}

#flex-token-loading {
    padding: 15px;
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
    color: #856404;
}

#flex-token-error {
    padding: 15px;
    background-color: #fffbfb;
    border-left: 4px solid #d1515d;
    color: #d1515d;
}

/* SOP Form Display */
#cybersource-sop-payment .sop-container {
    padding: 15px;
    background-color: #fff;
    border-radius: 4px;
}

#cybersource-sop-payment fieldset {
    border: 1px solid #e3e3e3;
    padding: 15px;
    margin: 15px 0;
}

#cybersource-sop-payment legend {
    font-size: 14px;
    font-weight: bold;
    padding: 0 10px;
}

#cybersource-sop-payment .field {
    margin: 15px 0;
    display: flex;
    flex-direction: column;
}

#cybersource-sop-payment label {
    font-weight: bold;
    margin-bottom: 8px;
    color: #333;
}

#cybersource-sop-payment select,
#cybersource-sop-payment input {
    padding: 8px;
    border: 1px solid #d3d3d3;
    border-radius: 4px;
}

#cybersource-sop-payment .actions {
    margin-top: 20px;
    text-align: center;
}

#cybersource-sop-payment .action-primary {
    padding: 10px 20px;
    background-color: #ed7d31;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

#cybersource-sop-payment .action-primary:hover {
    background-color: #d76b1f;
}

#sop-loading {
    padding: 15px;
    text-align: center;
    color: #666;
}

#sop-error {
    padding: 15px;
    background-color: #fffbfb;
    border-left: 4px solid #d1515d;
    color: #d1515d;
    margin: 15px 0;
}

/* Payment Info Display */
#cybersource-payment-info .payment-details {
    width: 100%;
    border-collapse: collapse;
}

#cybersource-payment-info .payment-details tr {
    border-bottom: 1px solid #e3e3e3;
}

#cybersource-payment-info .payment-details td {
    padding: 12px;
}

#cybersource-payment-info .payment-details .label {
    font-weight: bold;
    width: 200px;
    background-color: #f5f5f5;
}

#cybersource-payment-info .payment-details .value {
    font-family: monospace;
    color: #666;
}

/* Loading Animation */
.loader {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #ed7d31;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .admin-payment-method {
        padding: 15px;
    }

    #cybersource-sop-payment .field {
        flex-direction: column;
    }

    #cybersource-payment-info .payment-details .label {
        width: 100%;
    }
}
```

### **56. view/adminhtml/web/css/form-display.css**
```css
/* CyberSource REST Form Display Styles */

.sop-fields {
    display: none;
}

.sop-fields.active {
    display: block;
}

.form-fields {
    margin: 15px 0;
}

.form-fields .field {
    margin-bottom: 15px;
}

.form-fields label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-fields input,
.form-fields select {
    width: 100%;
    padding: 8px;
    border: 1px solid #d3d3d3;
    border-radius: 4px;
    font-size: 14px;
}

.form-fields input:focus,
.form-fields select:focus {
    outline: none;
    border-color: #ed7d31;
    box-shadow: 0 0 5px rgba(237, 125, 49, 0.5);
}

.error-container {
    padding: 15px;
    margin: 15px 0;
    background-color: #fee;
    border: 1px solid #fcc;
    border-radius: 4px;
}

.error-message {
    color: #c33;
    font-weight: bold;
}

/* Hidden fields - SOP form fields */
#hidden-fields {
    margin-top: 10px;
    padding: 10px;
    background-color: #f9f9f9;
    border: 1px dashed #ccc;
    border-radius: 4px;
    display: none;
}

#hidden-fields.populated {
    display: block;
}

/* Token display styles */
.token-display {
    padding: 15px;
    margin: 15px 0;
    background-color: #e8f5e9;
    border: 1px solid #4caf50;
    border-radius: 4px;
}

.token-display.success {
    background-color: #e8f5e9;
    border-color: #4caf50;
}

.token-display.error {
    background-color: #fee;
    border-color: #c33;
}

.token-display p {
    margin: 5px 0;
    color: #333;
}

/* Payment info table */
.payment-info-block {
    margin: 20px 0;
    padding: 15px;
    background-color: #f9f9f9;
    border: 1px solid #e3e3e3;
    border-radius: 4px;
}

.payment-info-block h3 {
    margin-top: 0;
    color: #333;
}

.payment-details {
    width: 100%;
    background-color: white;
}

.payment-details tbody tr:nth-child(even) {
    background-color: #f9f9f9;
}

.payment-details td {
    padding: 10px;
    border-bottom: 1px solid #e3e3e3;
}

.payment-details .label {
    font-weight: bold;
    width: 30%;
    color: #666;
}

.payment-details .value {
    word-break: break-all;
}

/* Buttons */
.action-primary {
    padding: 10px 20px;
    background-color: #ed7d31;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    transition: background-color 0.3s;
}

.action-primary:hover {
    background-color: #d76b1f;
}

.action-primary:disabled {
    background-color: #ccc;
    cursor: not-allowed;
}

/* Messages */
.message {
    padding: 12px 15px;
    margin: 15px 0;
    border-radius: 4px;
    font-size: 13px;
}

.message.success {
    background-color: #e8f5e9;
    border-left: 4px solid #4caf50;
    color: #2e7d32;
}

.message.error {
    background-color: #fffbfb;
    border-left: 4px solid #d1515d;
    color: #d1515d;
}

.message.warning {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
    color: #856404;
}

.message.info {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
    color: #1565c0;
}
```

---

## **PHASE 15: CONFIGURATION FILES (7 FILES)**

### **57. etc/webapi.xml (NEW)**
```xml
<?xml version="1.0"?>
<routes xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Webapi:etc/webapi.xsd">
    <!-- Cybersource REST Admin Endpoints -->

    <!-- 1. Generate Flex Token -->
    <route url="/V1/cybersource/admin/token/generate" method="POST">
        <service class="Cybersource\Payment\Api\Admin\TokenGeneratorInterface" method="generateToken"/>
        <resources>
            <resource ref="Magento_Sales::create"/>
        </resources>
    </route>

    <!-- 2. Build SOP Request Data -->
    <route url="/V1/cybersource/admin/sop/request-data" method="POST">
        <service class="Cybersource\Payment\Api\Admin\SopRequestDataBuilderInterface" method="buildRequestData"/>
        <resources>
            <resource ref="Magento_Sales::create"/>
        </resources>
    </route>

    <!-- 3. Handle SOP Response -->
    <route url="/V1/cybersource/admin/sop/response" method="POST">
        <service class="Cybersource\Payment\Api\Admin\SopResponseHandlerInterface" method="handleResponse"/>
        <resources>
            <resource ref="Magento_Sales::create"/>
        </resources>
    </route>

    <!-- 4. Create Order with Flex Token -->
    <route url="/V1/cybersource/admin/flex/place-order" method="POST">
        <service class="Cybersource\Payment\Api\Admin\FlexOrderCreatorInterface" method="createOrder"/>
        <resources>
            <resource ref="Magento_Sales::create"/>
        </resources>
    </route>

    <!-- 5. Delete Vault Token -->
    <route url="/V1/cybersource/admin/vault/token/:publicHash" method="DELETE">
        <service class="Cybersource\Payment\Api\Admin\VaultTokenManagementInterface" method="deleteToken"/>
        <resources>
            <resource ref="Magento_Sales::create"/>
        </resources>
    </route>

    <!-- 6. Get Available Tokens -->
    <route url="/V1/cybersource/admin/vault/tokens/:customerId" method="GET">
        <service class="Cybersource\Payment\Api\Admin\VaultTokenManagementInterface" method="getAvailableTokens"/>
        <resources>
            <resource ref="Magento_Sales::view"/>
        </resources>
    </route>
</routes>
```

### **58. etc/di.xml (UPDATE/ADD TO EXISTING)**
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">

    <!-- REST API Service Preferences -->
    <preference for="Cybersource\Payment\Api\Admin\TokenGeneratorInterface" 
                type="Cybersource\Payment\Model\Api\AdminTokenGenerator"/>
    <preference for="Cybersource\Payment\Api\Admin\SopRequestDataBuilderInterface" 
                type="Cybersource\Payment\Model\Api\AdminSopRequestDataBuilder"/>
    <preference for="Cybersource\Payment\Api\Admin\SopResponseHandlerInterface" 
                type="Cybersource\Payment\Model\Api\AdminSopResponseHandler"/>
    <preference for="Cybersource\Payment\Api\Admin\FlexOrderCreatorInterface" 
                type="Cybersource\Payment\Model\Api\AdminFlexOrderCreator"/>
    <preference for="Cybersource\Payment\Api\Admin\VaultTokenManagementInterface" 
                type="Cybersource\Payment\Model\Api\AdminVaultTokenManagement"/>

    <!-- REST Services -->
    <type name="Cybersource\Payment\Service\Rest\RequestValidator">
        <arguments/>
    </type>

    <type name="Cybersource\Payment\Service\Rest\ResponseFormatter">
        <arguments/>
    </type>

    <!-- Helpers -->
    <type name="Cybersource\Payment\Helper\Rest\RequestValidator">
        <arguments/>
    </type>

    <type name="Cybersource\Payment\Helper\Rest\ResponseFormatter">
        <arguments/>
    </type>

    <type name="Cybersource\Payment\Helper\Rest\TokenDataExtractor">
        <arguments/>
    </type>

    <!-- REST Controllers Dependencies -->
    <type name="Cybersource\Payment\Controller\Rest\Admin\TokenGenerator">
        <arguments>
            <argument name="tokenGenerator" xsi:type="object">Cybersource\Payment\Api\Admin\TokenGeneratorInterface</argument>
            <argument name="jsonFactory" xsi:type="object">Magento\Framework\Controller\Result\JsonFactory</argument>
            <argument name="logger" xsi:type="object">Psr\Log\LoggerInterface</argument>
        </arguments>
    </type>

    <type name="Cybersource\Payment\Controller\Rest\Admin\SopRequestData">
        <arguments>
            <argument name="sopRequestBuilder" xsi:type="object">Cybersource\Payment\Api\Admin\SopRequestDataBuilderInterface</argument>
            <argument name="jsonFactory" xsi:type="object">Magento\Framework\Controller\Result\JsonFactory</argument>
            <argument name="logger" xsi:type="object">Psr\Log\LoggerInterface</argument>
        </arguments>
    </type>

    <type name="Cybersource\Payment\Controller\Rest\Admin\SopResponse">
        <arguments>
            <argument name="sopResponseHandler" xsi:type="object">Cybersource\Payment\Api\Admin\SopResponseHandlerInterface</argument>
            <argument name="jsonFactory" xsi:type="object">Magento\Framework\Controller\Result\JsonFactory</argument>
            <argument name="logger" xsi:type="object">Psr\Log\LoggerInterface</argument>
        </arguments>
    </type>

    <type name="Cybersource\Payment\Controller\Rest\Admin\FlexPlaceOrder">
        <arguments>
            <argument name="flexOrderCreator" xsi:type="object">Cybersource\Payment\Api\Admin\FlexOrderCreatorInterface</argument>
            <argument name="jsonFactory" xsi:type="object">Magento\Framework\Controller\Result\JsonFactory</argument>
            <argument name="logger" xsi:type="object">Psr\Log\LoggerInterface</argument>
        </arguments>
    </type>

    <type name="Cybersource\Payment\Controller\Rest\Admin\VaultTokenDelete">
        <arguments>
            <argument name="vaultTokenManagement" xsi:type="object">Cybersource\Payment\Api\Admin\VaultTokenManagementInterface</argument>
            <argument name="jsonFactory" xsi:type="object">Magento\Framework\Controller\Result\JsonFactory</argument>
            <argument name="logger" xsi:type="object">Psr\Log\LoggerInterface</argument>
        </arguments>
    </type>

    <!-- Plugins -->
    <type name="Cybersource\Payment\Model\ResourceModel\Request\DataBuilder">
        <plugin name="rest_request_data_builder_plugin" type="Cybersource\Payment\Plugin\Rest\RequestDataBuilderPlugin" sortOrder="100"/>
    </type>

    <type name="Magento\Sales\Model\Order\Payment">
        <plugin name="rest_token_validator_plugin" type="Cybersource\Payment\Plugin\Rest\TokenValidatorPlugin" sortOrder="50"/>
    </type>

    <!-- Observers -->
    <type name="Magento\Framework\Event\Manager">
        <plugin name="rest_data_assign_observer" type="Cybersource\Payment\Observer\Rest\RestDataAssignObserver"/>
    </type>

    <!-- Block Dependencies -->
    <type name="Cybersource\Payment\Block\Rest\FlexTokenDisplay">
        <arguments>
            <argument name="registry" xsi:type="object">Magento\Framework\Registry</argument>
            <argument name="quoteRepository" xsi:type="object">Magento\Quote\Model\QuoteRepository</argument>
        </arguments>
    </type>

    <type name="Cybersource\Payment\Block\Rest\SopFormDisplay">
        <arguments>
            <argument name="registry" xsi:type="object">Magento\Framework\Registry</argument>
        </arguments>
    </type>

    <type name="Cybersource\Payment\Block\Rest\PaymentMethodInfo">
        <arguments>
            <argument name="registry" xsi:type="object">Magento\Framework\Registry</argument>
        </arguments>
    </type>
</config>
```

### **59. etc/routes.xml (UPDATE/ADD)**
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:App/etc/routes.xsd">
    <!-- Admin Routes for REST -->
    <router id="admin">
        <route id="cybersource_rest" frontName="cybersource">
            <module name="Cybersource_Payment"/>
        </route>
    </router>

    <!-- Standard Routes -->
    <router id="standard">
        <route id="cybersource_rest_api" frontName="cybersource">
            <module name="Cybersource_Payment"/>
        </route>
    </router>
</config>
```

### **60. etc/extension_attributes.xml (UPDATE/ADD)**
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Api/etc/extension_attributes.xsd">
    <!-- Quote Extension Attributes for REST -->
    <extension_attributes for="Magento\Quote\Api\Data\CartInterface">
        <attribute code="cybersource_flex_token" type="string">
            <join reference_table="quote" reference_field="entity_id" table="quote" field="entity_id">
                <field_mapping field_name="entity_id" value_name="quote_id"/>
            </join>
        </attribute>
        <attribute code="cybersource_flex_client_library" type="string">
            <join reference_table="quote" reference_field="entity_id" table="quote" field="entity_id">
                <field_mapping field_name="entity_id" value_name="quote_id"/>
            </join>
        </attribute>
        <attribute code="cybersource_flex_integrity" type="string">
            <join reference_table="quote" reference_field="entity_id" table="quote" field="entity_id">
                <field_mapping field_name="entity_id" value_name="quote_id"/>
            </join>
        </attribute>
    </extension_attributes>

    <!-- Order Extension Attributes -->
    <extension_attributes for="Magento\Sales\Api\Data\OrderInterface">
        <attribute code="cybersource_payment_info" type="string"/>
    </extension_attributes>

    <!-- Payment Extension Attributes -->
    <extension_attributes for="Magento\Sales\Api\Data\OrderPaymentInterface">
        <attribute code="cybersource_flex_jwt" type="string"/>
        <attribute code="cybersource_transaction_id" type="string"/>
    </extension_attributes>
</config>
```

### **61. etc/system.xml (UPDATE/ADD)**
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Config:etc/system_file.xsd">
    <system>
        <section id="payment">
            <group id="cybersource_rest" translate="label" type="text" sortOrder="999" showInDefault="1" showInWebsite="1" showInStore="1">
                <label>CyberSource REST Payment</label>
                
                <field id="active_flex" translate="label" type="select" sortOrder="10" showInDefault="1" showInWebsite="1" showInStore="0">
                    <label>Enable Flex Microform</label>
                    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
                </field>

                <field id="active_sop" translate="label" type="select" sortOrder="20" showInDefault="1" showInWebsite="1" showInStore="0">
                    <label>Enable Secure Order Post</label>
                    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
                </field>

                <field id="environment" translate="label" type="select" sortOrder="30" showInDefault="1" showInWebsite="0" showInStore="0">
                    <label>Environment</label>
                    <source_model>Cybersource\Payment\Model\Config\Source\Environment</source_model>
                </field>

                <field id="admin_order_enabled" translate="label" type="select" sortOrder="40" showInDefault="1" showInWebsite="0" showInStore="0">
                    <label>Enable REST for Admin Orders</label>
                    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
                </field>
            </group>
        </section>
    </system>
</config>
```

### **62. etc/config.xml (UPDATE/ADD)**
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Store:etc/config.xsd">
    <default>
        <payment>
            <cybersource_rest>
                <active_flex>1</active_flex>
                <active_sop>1</active_sop>
                <environment>test</environment>
                <admin_order_enabled>1</admin_order_enabled>
                <vault_enabled>1</vault_enabled>
            </cybersource_rest>
        </payment>
    </default>
</config>
```

### **63. etc/acl.xml (UPDATE/ADD)**
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Acl/etc/acl.xsd">
    <acl>
        <resources>
            <resource id="Magento_Admin::admin">
                <resource id="Magento_Sales::sales">
                    <resource id="Magento_Sales::sales_order">
                        <resource id="Magento_Sales::actions_edit">
                            <resource id="Cybersource_Payment::admin_cybersource_rest" translate="title" title="CyberSource REST Payment Admin"/>
                            <resource id="Cybersource_Payment::admin_token_generate" translate="title" title="Generate Payment Token"/>
                            <resource id="Cybersource_Payment::admin_sop_request" translate="title" title="Build SOP Request"/>
                            <resource id="Cybersource_Payment::admin_sop_response" translate="title" title="Handle SOP Response"/>
                            <resource id="Cybersource_Payment::admin_flex_order" translate="title" title="Create Flex Order"/>
                            <resource id="Cybersource_Payment::admin_vault_token" translate="title" title="Manage Vault Tokens"/>
                        </resource>
                    </resource>
                </resource>
            </resource>
        </resources>
    </acl>
</config>
```

---

## **IMPLEMENTATION CHECKLIST**

### **Pre-Implementation Steps**
- [ ] Backup existing CyberSource Secure Acceptance module
- [ ] Verify Magento 2 version compatibility (2.3.x, 2.4.x)
- [ ] Check PHP version (7.4+)
- [ ] Ensure test environment available

### **Module Structure Creation (63 files)**
- [ ] Create `app/code/Cybersource/Payment/` directory structure
- [ ] Create all subdirectories (Api, Model, Controller, Service, Helper, Observer, Plugin, Block, etc.)
- [ ] Copy all 63 files to correct paths with updated namespace

### **Core API Files (15 files)**
- [ ] Api/Admin/TokenGeneratorInterface.php
- [ ] Api/Admin/SopRequestDataBuilderInterface.php
- [ ] Api/Admin/SopResponseHandlerInterface.php
- [ ] Api/Admin/FlexOrderCreatorInterface.php
- [ ] Api/Admin/VaultTokenManagementInterface.php
- [ ] Model/Api/AdminTokenGenerator.php
- [ ] Model/Api/AdminSopRequestDataBuilder.php
- [ ] Model/Api/AdminSopResponseHandler.php
- [ ] Model/Api/AdminFlexOrderCreator.php
- [ ] Model/Api/AdminVaultTokenManagement.php
- [ ] Controller/Rest/Admin/TokenGenerator.php
- [ ] Controller/Rest/Admin/SopRequestData.php
- [ ] Controller/Rest/Admin/SopResponse.php
- [ ] Controller/Rest/Admin/FlexPlaceOrder.php
- [ ] Controller/Rest/Admin/VaultTokenDelete.php

### **Data Models (9 files)**
- [ ] Model/Rest/Request/TokenRequest.php
- [ ] Model/Rest/Request/SopRequestDataRequest.php
- [ ] Model/Rest/Request/SopResponseRequest.php
- [ ] Model/Rest/Request/FlexPlaceOrderRequest.php
- [ ] Model/Rest/Request/VaultTokenDeleteRequest.php
- [ ] Model/Rest/Response/TokenResponse.php
- [ ] Model/Rest/Response/SopRequestDataResponse.php
- [ ] Model/Rest/Response/OrderResponse.php
- [ ] Model/Rest/Response/SuccessResponse.php

### **Supporting Services (11 files)**
- [ ] Helper/Rest/RequestValidator.php
- [ ] Helper/Rest/ResponseFormatter.php
- [ ] Helper/Rest/TokenDataExtractor.php
- [ ] Service/Rest/RequestValidator.php
- [ ] Service/Rest/ResponseFormatter.php
- [ ] Service/Rest/OrderDataProcessor.php
- [ ] Service/Rest/ErrorHandler.php
- [ ] Observer/Rest/RestDataAssignObserver.php
- [ ] Observer/Rest/RestTokenObserver.php
- [ ] Plugin/Rest/RequestDataBuilderPlugin.php
- [ ] Plugin/Rest/TokenValidatorPlugin.php
- [ ] Plugin/Rest/ResponseSignaturePlugin.php

### **View Components (18 files)**
- [ ] Block/Rest/FlexTokenDisplay.php
- [ ] Block/Rest/SopFormDisplay.php
- [ ] Block/Rest/PaymentMethodInfo.php
- [ ] view/adminhtml/layout/sales_order_create_index.xml
- [ ] view/adminhtml/layout/sales_order_create_load_block_billing_method.xml
- [ ] view/adminhtml/layout/cybersource_rest_admin_payment.xml
- [ ] view/adminhtml/templates/rest/flex_token.phtml
- [ ] view/adminhtml/templates/rest/sop_form.phtml
- [ ] view/adminhtml/templates/rest/payment_info.phtml
- [ ] view/adminhtml/templates/rest/token_response.phtml
- [ ] view/adminhtml/templates/rest/form_fields.phtml
- [ ] view/adminhtml/templates/rest/error_message.phtml
- [ ] view/adminhtml/web/js/rest/rest-client.js
- [ ] view/adminhtml/web/js/rest/token-generator.js
- [ ] view/adminhtml/web/js/rest/sop-request.js
- [ ] view/adminhtml/web/js/rest/flex-order.js
- [ ] view/adminhtml/web/js/rest/form-validator.js
- [ ] view/adminhtml/web/js/rest/response-handler.js
- [ ] view/adminhtml/web/css/admin-payment.css
- [ ] view/adminhtml/web/css/form-display.css

### **Configuration Files (7 files)**
- [ ] etc/webapi.xml (NEW)
- [ ] etc/di.xml (UPDATE)
- [ ] etc/routes.xml (UPDATE)
- [ ] etc/extension_attributes.xml (UPDATE)
- [ ] etc/system.xml (UPDATE)
- [ ] etc/config.xml (UPDATE)
- [ ] etc/acl.xml (UPDATE)

### **Module Setup**
- [ ] Create etc/module.xml with dependencies
- [ ] Create registration.php
- [ ] Create composer.json (if needed)

### **Post-File Creation Steps**
1. [ ] Enable module: `php bin/magento module:enable Cybersource_Payment`
2. [ ] Run setup upgrade: `php bin/magento setup:upgrade`
3. [ ] Compile DI: `php bin/magento setup:di:compile`
4. [ ] Deploy static content: `php bin/magento setup:static-content:deploy`
5. [ ] Clear cache: `php bin/magento cache:clean`

### **Testing Phase**
- [ ] Test Flex token generation endpoint
- [ ] Test SOP request data building endpoint
- [ ] Test SOP response handling endpoint
- [ ] Test Flex order creation endpoint
- [ ] Test Vault token deletion endpoint
- [ ] Verify all responses return proper JSON
- [ ] Verify error handling for invalid inputs
- [ ] Test admin order creation flow
- [ ] Verify payment information saved correctly
- [ ] Check logs for any errors

### **Integration Testing**
- [ ] Test complete admin order flow with Flex
- [ ] Test complete admin order flow with SOP
- [ ] Test vault token usage
- [ ] Verify payment gateway integration
- [ ] Test payment capture
- [ ] Verify authorization codes recorded

### **Security & Performance**
- [ ] Verify ACL permissions working
- [ ] Test CORS headers if needed
- [ ] Load test REST endpoints
- [ ] Check response times
- [ ] Verify data validation
- [ ] Audit sensitive data handling

---

## **TESTING CURL EXAMPLES**

```bash
# 1. Generate Token
curl -X POST http://your-store/rest/V1/cybersource/admin/token/generate \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"quote_id": 1, "store_id": 1}'

# 2. Build SOP Request
curl -X POST http://your-store/rest/V1/cybersource/admin/sop/request-data \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"quote_id": 1, "cc_type": "001", "vault_enabled": false}'

# 3. Handle SOP Response
curl -X POST http://your-store/rest/V1/cybersource/admin/sop/response \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"decision": "ACCEPT", "transaction_id": "123456", "quote_id": 1}'

# 4. Place Flex Order
curl -X POST http://your-store/rest/V1/cybersource/admin/flex/place-order \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"quote_id": 1, "token": "eyJ...", "cc_type": "001", "exp_date": "12/2025", "masked_pan": "411111xxxxxx1111"}'

# 5. Delete Vault Token
curl -X DELETE http://your-store/rest/V1/cybersource/admin/vault/token/abc123 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"customer_id": 5}'
```

---

This completes all 63 files with the updated `Cybersource\Payment` namespace ready for implementation.
