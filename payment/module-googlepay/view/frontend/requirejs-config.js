var config = {
    paths: {
        googlePayApi: 'https://pay.google.com/gp/p/js/pay'
    },
    shim: {
        googlePayApi: {
            exports: 'google.payments.api'
        }
    }
};
