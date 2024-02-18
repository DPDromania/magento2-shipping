require(
    [
        'jquery',
        'mage/translate',
    ],
    function($) {

        // =================================================================================
        // DPD TAX RATE TEMPLATE
        function DPD_Tax_Template() {
            var services = '';
            for (service_value in DPDRO_TaxRates_Services) {
                var service = Object.keys(DPDRO_TaxRates_Services).slice(0, 1).shift();
                var service_name = DPDRO_TaxRates_Services[service_value];
                if (service == service_value) {
                    services += '<option selected="selected" value="' + service_value + '">' + service_name + '</option>';
                } else {
                    services += '<option value="' + service_value + '">' + service_name + '</option>';
                }
            }
            var html = `
                <tr class="js-dpdro-taxrate">
                    <td>
                        <select name="js-dpdro-service-id">` + services + `</select>
                    </td>
                    <td>
                        <select name="js-dpdro-based-on">
                            <option value="1" selected="selected">` + DPDRO_TaxRates_Text.basedOnPrice + `</option>
                            <option value="0">` + DPDRO_TaxRates_Text.basedOnWeight + `</option>
                        </select>
                    </td>
                    <td>
                        <input name="js-dpdro-apply-over" type="text" value="0.00" placeholder="0.00" class="form-control">
                    </td>
                    <td>
                        <input name="js-dpdro-tax-rate" type="text" value="0.00" placeholder="0.00" class="form-control">
                    </td>
                    <td>
                        <select name="js-dpdro-calculation-type">
                            <option value="1" selected="selected">` + DPDRO_TaxRates_Text.fixed + `</option>
                            <option value="0">` + DPDRO_TaxRates_Text.percentage + `</option>
                        </select>
                    </td>
                    <td>
                        <select name="js-dpdro-status">
                            <option value="1" selected="selected">` + DPDRO_TaxRates_Text.enabled + `</option>
                            <option value="0">` + DPDRO_TaxRates_Text.disabled + `</option>
                        </select>
                    </td>
                    <td>
                        <button class="button danger js-dpdro-taxrate-delete" type="button">` + DPDRO_TaxRates_Text.delete + `</button>
                        <button class="button success js-dpdro-taxrate-save" type="button">` + DPDRO_TaxRates_Text.save + `</button>
                    </td>
                </tr>
            `;
            return html;
        }

        // =================================================================================
        // DPD JAVASCRIPT
        function DPD_JS() {

            // =============================================================================
            // SETTINGS
            $(document).on('click', '.js-dpdro-save', function(e) {
                e.preventDefault();
                var action = DPDRO_Settings_Save + '?isAjax=true';
                var data = {
                    username: $('[name="js-dpdro-username"]').val(),
                    password: $('[name="js-dpdro-password"]').val(),
                };
                if ($('[name="js-dpdro-connected"]').length > 0) {
                    var services = [];
                    $.each($('[name^="js-dpdro-service-"]'), function(key, value) {
                        if ($(this).is(':checked')) {
                            services.push($(this).val());
                        }
                    });
                    data['packagingMethod'] = $('[name="js-dpdro-packaging-method"]:checked').val();
                    data['services'] = services.join().toString();
                    data['clientContracts'] = $('[name="js-dpdro-client-contracts-selected"]').val();
                    data['officeLocations'] = $('[name="js-dpdro-office-locations-selected"]').val();
                    data['senderPayerInsurance'] = $('[name="js-dpdro-sender-payer-insurance"]:checked').val();
                    data['senderPayerIncludeShipping'] = $('[name="js-dpdro-sender-payer-include-shipping"]:checked').val();
                    data['payerCourier'] = $('[name="js-dpdro-payer-courier-selected"]').val();
                    data['printFormat'] = $('[name="js-dpdro-print-format-selected"]').val();
                    data['printPaperSize'] = $('[name="js-dpdro-print-paper-size-selected"]').val();
                    data['payerCourierThirdParty'] = $('[name="js-dpdro-payer-courier-third-party-id"]').val();
                    data['maxParcelWeight'] = $('[name="js-dpdro-max-parcel-weight"]').val();
                    data['maxParcelWeightAutomat'] = $('[name="js-dpdro-max-parcel-weight-automat"]').val();
                    data['weight'] = $('[name="js-dpdro-weight"]').val();
                    data['status'] = $('[name="js-dpdro-status"]').val();
                    data['advanced'] = $('[name="js-dpdro-advanced-dashboard"]').val();
                }
                $.ajax({
                    showLoader: true,
                    url: action,
                    data: {
                        form_key: window.FORM_KEY,
                        parameters: data
                    },
                    type: 'POST',
                    dataType: 'json',

                    success: function(response, textStatus, jqXHR) {
                        if (response.error) {
                            $('.js-dpdro-alert').addClass('danger');
                            $('.js-dpdro-alert span').html(response.message);
                        } else {
                            location.reload();
                        }
                    },

                    fail: function() {
                        $('.js-dpdro-alert').addClass('danger');
                        $('.js-dpdro-alert span').html(DPDRO_Settings_Text.error);
                    }                    
                });

                return false;
            });
            $(document).on('click', '.js-dpdro-alert-close', function(e) {
                e.preventDefault();
                $(this).closest('.js-dpdro-alert').css('display', 'none');
                return false;
            });
            $(document).on('click', '.js-dpdro-select', function(e) {
                e.preventDefault();
                $.each($('[name^="js-dpdro-service-"]'), function(key, value) {
                    $(this).prop('checked', true);
                });
                return false;
            });
            $(document).on('click', '.js-dpdro-unselect', function(e) {
                e.preventDefault();
                $.each($('[name^="js-dpdro-service-"]'), function(key, value) {
                    $(this).prop('checked', false);
                });
                return false;
            });
            $(document).on('change', '[name="js-dpdro-client-contracts-selected"]', function() {
                $('[name="js-dpdro-office-locations-selected"]').val('0');
            });
            $(document).on('change', '[name="js-dpdro-office-locations-selected"]', function() {
                $('[name="js-dpdro-client-contracts-selected"]').val('0');
            });
            $(document).on('change', '[name="js-dpdro-payer-courier-selected"]', function() {
                if ($(this).val() == 'THIRD_PARTY') {
                    $('.js-dpdro-third-party').removeClass('hidden');
                } else {
                    $('.js-dpdro-third-party').addClass('hidden');
                }
                if ($(this).val() == 'RECIPIENT') {
                    $('.js-dpdro-include-shipping-price-field').addClass('hidden');
                } else {
                    $('.js-dpdro-include-shipping-price-field').removeClass('hidden');
                }
            });

            // =============================================================================
            // PAYMENT
            $(document).on('click', '.js-dpdro-payment-tax-save', function(e) {
                e.preventDefault();
                var action = DPDRO_PaymentTax_Save + '?isAjax=true';
                var data = [];
                $.each($('.js-dpdro-payment-tax'), function(key, value) {
                    var tax = {
                        country: $(this).attr('data-country'),
                        tax: $(this).find('[name="js-dpdro-tax"]').val(),
                        vat: $(this).find('[name="js-dpdro-vat"]').val(),
                        status: $(this).find('[name="js-dpdro-status"]').val(),
                    };
                    data.push(tax);
                });
                $.ajax({
                    showLoader: true,
                    url: action,
                    data: {
                        form_key: window.FORM_KEY,
                        parameters: data
                    },
                    type: 'POST',
                    dataType: 'json',

                    success: function(response, textStatus, jqXHR) {
                        if (response.error) {
                            $('.js-dpdro-alert').addClass('danger');
                            $('.js-dpdro-alert span').html(response.message);
                        } else {
                            location.reload();
                        }
                    },

                    fail: function() {
                        $('.js-dpdro-alert').addClass('danger');
                        $('.js-dpdro-alert span').html(DPDRO_PaymentTax_Text.error);
                    }

                });

                return false;
            });

            // =============================================================================
            // REGIONS
            $(document).on('click', '.js-dpdro-region-edit', function(e) {
                e.preventDefault();
                var region = $(this).closest('.js-dpdro-region');
                region.find('.code').html('<input type="text" name="" value="' + region.find('.code').attr('data-value') + '" />');
                region.find('.name').html('<input type="text" name="" value="' + region.find('.name').attr('data-value') + '" />');
                region.find('.js-dpdro-region-edit').addClass('hide');
                region.find('.js-dpdro-region-delete').addClass('hide');
                region.find('.js-dpdro-region-update').removeClass('hide');
                region.find('.js-dpdro-region-cancel').removeClass('hide');
                return false;
            });
            $(document).on('click', '.js-dpdro-region-cancel', function(e) {
                e.preventDefault();
                var region = $(this).closest('.js-dpdro-region');
                region.find('.code').html(region.find('.code').attr('data-value'));
                region.find('.name').html(region.find('.name').attr('data-value'));
                region.find('.js-dpdro-region-update').addClass('hide');
                region.find('.js-dpdro-region-cancel').addClass('hide');
                region.find('.js-dpdro-region-edit').removeClass('hide');
                region.find('.js-dpdro-region-delete').removeClass('hide');
                return false;
            });
            $(document).on('click', '.js-dpdro-region-add', function(e) {
                e.preventDefault();
                var region = $(this).closest('.js-dpdro-region');
                var country = region.find('.country');
                var code = region.find('.code');
                var name = region.find('.name');
                code.removeClass('error');
                name.removeClass('error');
                if (code.val() != '' && name.val() != '') {
                    var action = DPDRO_Regions_Save + '?isAjax=true';
                    $.ajax({
                        showLoader: true,
                        url: action,
                        data: {
                            form_key: window.FORM_KEY,
                            parameters: {
                                country: country.val(),
                                code: code.val(),
                                name: name.val(),
                            },
                            action: 'save'
                        },
                        type: 'POST',
                        dataType: 'json',

                        success: function(response, textStatus, jqXHR) {
                            if (response.error) {
                                $('.js-dpdro-alert').addClass('danger');
                                $('.js-dpdro-alert span').html(response.message);
                            } else {
                                location.reload();
                            }
                        },

                        fail: function() {
                            $('.js-dpdro-alert').addClass('danger');
                            $('.js-dpdro-alert span').html(DPDRO_Regions_Text.error);
                        }
                    });

                } else {
                    if (code.val() == '') {
                        code.addClass('error');
                    }
                    if (name.val() == '') {
                        name.addClass('error');
                    }
                }
                return false;
            });
            $(document).on('click', '.js-dpdro-region-update', function(e) {
                e.preventDefault();
                var region = $(this).closest('.js-dpdro-region');
                var code = region.find('.code input');
                var name = region.find('.name input');
                code.removeClass('error');
                name.removeClass('error');
                if (code.val() != '' && name.val() != '') {
                    var action = DPDRO_Regions_Save + '?isAjax=true';
                    $.ajax({
                        showLoader: true,
                        url: action,
                        data: {
                            form_key: window.FORM_KEY,
                            parameters: {
                                region: $(this).attr('data-id'),
                                country: $(this).attr('data-country'),
                                code: code.val(),
                                name: name.val(),
                            },
                            action: 'save'
                        },
                        type: 'POST',
                        dataType: 'json',

                        success: function(response, textStatus, jqXHR) {
                            if (response.error) {
                                $('.js-dpdro-alert').addClass('danger');
                                $('.js-dpdro-alert span').html(response.message);
                            } else {
                                location.reload();
                            }
                        },

                        fail: function() {
                            $('.js-dpdro-alert').addClass('danger');
                            $('.js-dpdro-alert span').html(DPDRO_Regions_Text.error);
                        }
                    });
                } else {
                    if (code.val() == '') {
                        code.addClass('error');
                    }
                    if (name.val() == '') {
                        name.addClass('error');
                    }
                }
                return false;
            });
            $(document).on('click', '.js-dpdro-region-delete', function(e) {
                e.preventDefault();
                if ($(this).attr('data-id')) {
                    var data = {
                        region: $(this).attr('data-id')
                    };
                    var action = DPDRO_Regions_Save + '?isAjax=true';
                    $.ajax({
                        showLoader: true,
                        url: action,
                        data: {
                            form_key: window.FORM_KEY,
                            parameters: data,
                            action: 'delete'
                        },
                        type: 'POST',
                        dataType: 'json',

                        success: function(response, textStatus, jqXHR) {
                            if (response.error) {
                                $('.js-dpdro-alert').addClass('danger');
                                $('.js-dpdro-alert span').html(response.message);
                            } else {
                                location.reload();
                            }
                        },

                        fail: function() {
                            $('.js-dpdro-alert').addClass('danger');
                            $('.js-dpdro-alert span').html(DPDRO_Regions_Text.error);
                        }
                    });
                }
                return false;
            });
            $(document).on('click', '.js-dpdro-region-import', function(e) {
                e.preventDefault();
                if ($(this).attr('data-country') != '') {
                    var action = DPDRO_Regions_Import + '?isAjax=true';
                    $.ajax({
                        showLoader: true,
                        url: action,
                        data: {
                            form_key: window.FORM_KEY,
                            parameters: {
                                country: $(this).attr('data-country')
                            },
                            action: 'import'
                        },
                        type: 'POST',
                        dataType: 'json',

                        success: function(response, textStatus, jqXHR) {
                            if (response.error) {
                                $('.js-dpdro-alert').addClass('danger');
                                $('.js-dpdro-alert span').html(response.message);
                            } else {
                                location.reload();
                            }
                        },

                        fail: function() {
                            $('.js-dpdro-alert').addClass('danger');
                            $('.js-dpdro-alert span').html(DPDRO_Regions_Text.error);
                        }
                    });
                }
                return false;
            });

            // =============================================================================
            // TAX RATES
            $(document).on('click', '.js-dpdro-taxrate-add', function(e) {
                e.preventDefault();
                var taxrate = DPD_Tax_Template();
                $('.js-dpdro-taxrates').append(taxrate);
                return false;
            });
            $(document).on('click', '.js-dpdro-taxrate-delete', function(e) {
                e.preventDefault();
                if ($(this).attr('data-id')) {
                    var data = {
                        taxID: $(this).attr('data-id')
                    };
                    var action = DPDRO_TaxRates_Save + '?isAjax=true';
                    $.ajax({
                        showLoader: true,
                        url: action,
                        data: {
                            form_key: window.FORM_KEY,
                            parameters: data,
                            action: 'delete'
                        },
                        type: 'POST',
                        dataType: 'json',

                        success: function(response, textStatus, jqXHR) {
                            if (response.error) {
                                $('.js-dpdro-alert').addClass('danger');
                            } else {
                                $('.js-dpdro-alert').addClass('success');
                            }
                            $('.js-dpdro-alert span').html(response.message);
                        },

                        fail: function() {
                            $('.js-dpdro-alert').addClass('danger');
                            $('.js-dpdro-alert span').html(DPDRO_TaxRates_Text.error);
                        }
                    });
                            
                }
                var taxrates = $('.js-dpdro-taxrates .js-dpdro-taxrate').length;
                $(this).closest('.js-dpdro-taxrate').remove();
                if (taxrates == 1) {
                    var taxrate = DPD_Tax_Template();
                    $('.js-dpdro-taxrates').append(taxrate);
                }
                return false;
            });
            $(document).on('click', '.js-dpdro-taxrate-save', function(e) {
                e.preventDefault();
                var data = {
                    taxID: 'false',
                    serviceID: $(this).closest('.js-dpdro-taxrate').find('[name="js-dpdro-service-id"]').val(),
                    basedOn: $(this).closest('.js-dpdro-taxrate').find('[name="js-dpdro-based-on"]').val(),
                    applyOver: $(this).closest('.js-dpdro-taxrate').find('[name="js-dpdro-apply-over"]').val(),
                    taxRate: $(this).closest('.js-dpdro-taxrate').find('[name="js-dpdro-tax-rate"]').val(),
                    calculationType: $(this).closest('.js-dpdro-taxrate').find('[name="js-dpdro-calculation-type"]').val(),
                    status: $(this).closest('.js-dpdro-taxrate').find('[name="js-dpdro-status"]').val(),
                };
                if ($(this).attr('data-id')) {
                    data['taxID'] = $(this).attr('data-id');
                }
                var action = DPDRO_TaxRates_Save + '?isAjax=true';
                $.ajax({
                    showLoader: true,
                    url: action,
                    data: {
                        form_key: window.FORM_KEY,
                        parameters: data,
                        action: 'save'
                    },
                    type: 'POST',
                    dataType: 'json',

                    success: function(response, textStatus, jqXHR) {
                        if (response.error) {
                            $('.js-dpdro-alert').addClass('danger');
                            $('.js-dpdro-alert span').html(response.message);
                        } else {
                            location.reload();
                        }
                    },

                    fail: function() {
                        $('.js-dpdro-alert').addClass('danger');
                        $('.js-dpdro-alert span').html(DPDRO_TaxRates_Text.error);
                    }
                });

                return false;
            });

            // =============================================================================
            // ORDERS Popup
            $(document).on('click', '.js-dpdro-popup-open', function(e) {
                e.preventDefault();
                $('.js-dpdro-popup').removeClass('active');
                $(this).closest('td').find('.js-dpdro-popup').addClass('active');
                $('body').addClass('dpdro-hidden');
                return false;
            });
            $(document).on('click', '.js-dpdro-popup-close', function(e) {
                e.preventDefault();
                $(this).closest('.js-dpdro-popup').removeClass('active');
                $('body').removeClass('dpdro-hidden');
                return false;
            });

            // =============================================================================
            // ORDERS Validation
            $(document).on('click', '.js-dpdro-order-validation-validate', function(e) {
                e.preventDefault();
                var action = DPDRO_Orders_Validation + '?isAjax=true';
                var shipment = $(this).closest('.js-dpdro-popup');
                var alertMessage = shipment.find('.body');
                var data = {
                    orderID: $(this).attr('data-id'),
                    country: shipment.find('.country-id').val(),
                    city: shipment.find('.city').val(),
                    streetID: shipment.find('.street-id').val(),
                    streetName: shipment.find('.street-name').val(),
                    streetType: shipment.find('.street-type').val(),
                    number: shipment.find('.number').val(),
                    block: shipment.find('.block').val(),
                    apartment: shipment.find('.apartment').val(),
                    scale: shipment.find('.scale').val(),
                    floor: shipment.find('.floor').val(),
                    postcode: shipment.find('.postcode').val(),
                };
                shipment.find('.js-dpdro-loader').addClass('active');
                $.ajax({
                    showLoader: true,
                    url: action,
                    data: {
                        form_key: window.FORM_KEY,
                        parameters: data,
                        action: 'validated'
                    },
                    type: 'POST',
                    dataType: 'json',

                    success: function(response, textStatus, jqXHR) {
                        shipment.find('.js-dpdro-loader').removeClass('active');
                        if (response.error) {
                            var alert = `
                                <div class="dpdro-alert danger" role="alert">
                                    <span>` + response.message + `</span>
                                </div>
                            `;
                            alertMessage.prepend(alert);
                        } else {
                            shipment.find('.js-dpdro-alert').removeClass('danger').addClass('success').find('span').html(response.message);
                            shipment.find('.js-dpdro-disabled').removeClass('active');
                            shipment.find('.js-dpdro-order-address-validation-table').css('display', 'none');
                        }
                    },

                    fail: function() {
                        shipment.find('.js-dpdro-loader').removeClass('active');
                        var alert = `
                            <div class="dpdro-alert danger" role="alert">
                                <span>` + DPDRO_Orders_Text.error + `</span>
                            </div>
                        `;
                        alertMessage.prepend(alert);
                    }
                });

                return false;
            });
            $(document).on('click', '.js-dpdro-order-validation-skip', function(e) {
                e.preventDefault();
                var action = DPDRO_Orders_Validation + '?isAjax=true';
                var shipment = $(this).closest('.js-dpdro-popup');
                var alertMessage = shipment.find('.body');
                var data = {
                    orderID: $(this).attr('data-id')
                };
                shipment.find('.js-dpdro-loader').addClass('active');
                $.ajax({
                    showLoader: true,
                    url: action,
                    data: {
                        form_key: window.FORM_KEY,
                        parameters: data,
                        action: 'skip'
                    },
                    type: 'POST',
                    dataType: 'json',

                    success: function(response, textStatus, jqXHR) {
                        shipment.find('.js-dpdro-loader').removeClass('active');
                        if (response.error) {
                            var alert = `
                                <div class="dpdro-alert danger" role="alert">
                                    <span>` + response.message + `</span>
                                </div>
                            `;
                            alertMessage.prepend(alert);
                        } else {
                            shipment.find('.js-dpdro-alert').css('display', 'none');
                            shipment.find('.js-dpdro-disabled').removeClass('active');
                            shipment.find('.js-dpdro-order-validation-skip').css('display', 'none');
                            shipment.find('.js-dpdro-order-validation-normalize').css('display', 'inline-block');
                            shipment.find('.js-dpdro-order-address-validation-table-head').css('display', 'none');
                            shipment.find('.js-dpdro-order-address-validation-table-body').css('display', 'none');
                        }
                    },

                    fail: function() {
                        shipment.find('.js-dpdro-loader').removeClass('active');
                        var alert = `
                            <div class="dpdro-alert danger" role="alert">
                                <span>` + DPDRO_Orders_Text.error + `</span>
                            </div>
                        `;
                        alertMessage.prepend(alert);
                    }
                    
                });

                return false;
            });
            $(document).on('click', '.js-dpdro-order-validation-normalize', function(e) {
                e.preventDefault();
                var action = DPDRO_Orders_Validation + '?isAjax=true';
                var shipment = $(this).closest('.js-dpdro-popup');
                var alertMessage = shipment.find('.body');
                var data = {
                    orderID: $(this).attr('data-id')
                };
                shipment.find('.js-dpdro-loader').addClass('active');
                $.ajax({
                    showLoader: true,
                    url: action,
                    data: {
                        form_key: window.FORM_KEY,
                        parameters: data,
                        action: 'normalize'
                    },
                    type: 'POST',
                    dataType: 'json',

                    success: function(response, textStatus, jqXHR) {
                        shipment.find('.js-dpdro-loader').removeClass('active');
                        if (response.error) {
                            var alert = `
                                <div class="dpdro-alert danger" role="alert">
                                    <span>` + response.message + `</span>
                                </div>
                            `;
                            alertMessage.prepend(alert);
                        } else {
                            shipment.find('.js-dpdro-alert').css('display', 'inline-block');
                            shipment.find('.js-dpdro-disabled').addClass('active');
                            shipment.find('.js-dpdro-order-validation-skip').css('display', 'inline-block');
                            shipment.find('.js-dpdro-order-validation-normalize').css('display', 'none');
                            shipment.find('.js-dpdro-order-address-validation-table-head').css('display', 'table-row');
                            shipment.find('.js-dpdro-order-address-validation-table-body').css('display', 'table-row');
                        }
                    },

                    fail: function() {
                        shipment.find('.js-dpdro-loader').removeClass('active');
                        var alert = `
                            <div class="dpdro-alert danger" role="alert">
                                <span>` + DPDRO_Orders_Text.error + `</span>
                            </div>
                        `;
                        alertMessage.prepend(alert);
                    }
                });

                return false;
            });

            $(document).on('keyup', '.js-dpdro-order-street-search-field', function(e) {
                if ($(this).val().length > 0) {
                    var action = DPDRO_Orders_Validation + '?isAjax=true';
                    var data = {
                        countryID: $(this).closest('.js-dpdro-order-address-validation').find('.country-id').val().trim(),
                        cityID: $(this).closest('.js-dpdro-order-address-validation').find('.city-id').val().trim(),
                        search: $(this).val().trim(),
                    };
                    var drowpdown = $(this).closest('.js-dpdro-order-street-search').find('.js-dpdro-order-street-search-container');
                    drowpdown.addClass('active').html($('<div class="loader"><span></span></div>'));
                    $.ajax({
                        showLoader: false,
                        url: action,
                        data: {
                            form_key: window.FORM_KEY,
                            parameters: data,
                            action: 'search'
                        },
                        type: 'POST',
                        dataType: 'json',

                        success: function(response, textStatus, jqXHR) {
                            var html = '';
                            $.each(response, function(key, value) {
                                html += '<button type="button" data-id="' + value['id'] + '" data-type="' + value['type'] + '" data-name="' + value['name'] + '">' + value['text'] + '</button>';
                            });
                            drowpdown.html(html);
                        },

                        fail: function() {
                            $('.js-dpdro-alert').addClass('danger');
                            $('.js-dpdro-alert span').html(DPDRO_Orders_Text.error);
                        }
                    });
                }
                return false;
            });
            $(document).on('click', '.js-dpdro-order-street-search-field', function(e) {
                if ($(this).val().length > 0) {
                    var action = DPDRO_Orders_Validation + '?isAjax=true';
                    var data = {
                        countryID: $(this).closest('.js-dpdro-order-address-validation').find('.country-id').val().trim(),
                        cityID: $(this).closest('.js-dpdro-order-address-validation').find('.city-id').val().trim(),
                        search: $(this).val().trim(),
                    };
                    var drowpdown = $(this).closest('.js-dpdro-order-street-search').find('.js-dpdro-order-street-search-container');
                    drowpdown.addClass('active').html($('<div class="loader"><span></span></div>'));
                    $.ajax({
                        showLoader: false,
                        url: action,
                        data: {
                            form_key: window.FORM_KEY,
                            parameters: data,
                            action: 'search'
                        },
                        type: 'POST',
                        dataType: 'json',

                        success: function(response, textStatus, jqXHR) {
                            var html = '';
                            $.each(response, function(key, value) {
                                html += '<button type="button" data-id="' + value['id'] + '" data-type="' + value['type'] + '" data-name="' + value['name'] + '">' + value['text'] + '</button>';
                            });
                            drowpdown.html(html);
                        },

                        fail:function() {
                            $('.js-dpdro-alert').addClass('danger');
                            $('.js-dpdro-alert span').html(DPDRO_Orders_Text.error);
                        }
                    });
                }
                
                return false;
            });
            $(document).on('click', '.js-dpdro-order-street-search-container button', function(e) {
                e.preventDefault();
                var streetID = $(this).attr('data-id');
                var streetType = $(this).attr('data-type');
                var streetName = $(this).attr('data-name');
                $(this).closest('tbody').find('.street-id').val(streetID);
                $(this).closest('tbody').find('.street-name').val(streetName);
                $(this).closest('tbody').find('.street-type').val(streetType);
                $(this).closest('.js-dpdro-order-street-search-container').removeClass('active').html('');
                return false;
            });
            $(document).on('click', function(e) {
                if (!$(e.target).hasClass('.js-order-street-search-field')) {
                    $('.js-order-street-search-container').removeClass('active').html('');
                }
            });


            // =============================================================================
            // ORDERS Shipment
            $(document).on('click', '.js-dpdro-order-shipment-create', function(e) {
                e.preventDefault();
                var action = DPDRO_Orders_Shipment + '?isAjax=true';
                var shipment = $(this).closest('.js-dpdro-popup');
                var alertMessage = shipment.find('.body');
                var shipmentProducts = [];
                var shipmentParcels = [];
                var shipmentAddress = [];
                var shipmentValidation = [];
                $.each(shipment.find('.js-dpdro-shipment-products'), function(key, value) {
                    shipmentProducts.push({
                        'id': $(this).find('.id').val(),
                        'name': $(this).find('.name').val(),
                        'weight': $(this).find('.weight').val(),
                        'parcel': $(this).find('.parcel').val(),
                    });
                });
                $.each(shipment.find('.js-dpdro-shipment-parcels'), function(key, value) {
                    shipmentParcels.push({
                        'id': $(this).find('.id').val(),
                        'description': $(this).find('.description').val(),
                    });
                });
                if (!shipment.find('.js-dpdro-shipment-address-pickup').length > 0) {
                    var address = shipment.find('.js-dpdro-order-address-validation');
                    if (address.find('.street-type').length && address.find('.street-type').val() != '') {
                        shipmentAddress.push(address.find('.street-type').val() + ' ' + address.find('.street-name').val());
                    } else {
                        shipmentAddress.push(address.find('.street-name').val());
                    }
                    if (address.find('.number').length && address.find('.number').val() != '') {
                        shipmentAddress.push('nr.' + address.find('.number').val());
                    }
                    if (address.find('.block').length && address.find('.block').val() != '') {
                        shipmentAddress.push('bl.' + address.find('.block').val());
                    }
                    if (address.find('.apartment').length && address.find('.apartment').val() != '') {
                        shipmentAddress.push('ap.' + address.find('.apartment').val());
                    }
                    if (address.find('.scale').length && address.find('.scale').val() != '') {
                        shipmentAddress.push('sc.' + address.find('.scale').val());
                    }
                    if (address.find('.floor').length && address.find('.floor').val() != '') {
                        shipmentAddress.push('et.' + address.find('.floor').val());
                    }
                    if (!$(this).closest('.js-dpdro-popup').find('.js-dpdro-shipment-address-pickup').length > 0) {
                        shipmentValidation = {
                            'city': address.find('.city').val(),
                            'streetName': address.find('.street-name').val(),
                            'streetType': address.find('.street-type').val(),
                            'number': address.find('.number').val(),
                        };
                    }
                }
                var data = {
                    orderID: $(this).attr('data-id'),
                    shipmentProducts: JSON.stringify(shipmentProducts),
                    shipmentParcels: JSON.stringify(shipmentParcels),
                    shipmentAddress: shipmentAddress.join(', '),
                    shipmentValidation: shipmentValidation,
                    shipmentSwap: shipment.find('.js-dpdro-shipment-swap').is(':checked') ? true : false,
                    shipmentRod: shipment.find('.js-dpdro-shipment-rod').is(':checked') ? true : false,
                    shipmentVoucher: shipment.find('.js-dpdro-shipment-voucher').is(':checked') ? true : false,
                    shipmentVoucherSender: shipment.find('.js-dpdro-shipment-voucher-sender').val(),
                    shipmentPrivate: shipment.find('.js-dpdro-shipment-private').is(':checked') ? true : false,
                    shipmentPrivatePerson: shipment.find('.js-dpdro-shipment-private-person').val(),
                    shipmentNotes: shipment.find('.js-dpdro-shipment-notes').val(),
                    shipmentRef2: shipment.find('.js-dpdro-shipment-ref2').val(),
                };
                shipment.find('.js-dpdro-shipment-private-person').removeClass('error');
                if (shipment.find('.js-dpdro-shipment-private').is(':checked')) {
                    if (shipment.find('.js-dpdro-shipment-private-person').val() == '') {
                        shipment.find('.js-dpdro-shipment-private-person').addClass('error');
                        return false;
                    }
                }
                shipment.find('.js-dpdro-loader').addClass('active');
                $.ajax({
                    showLoader: true,
                    url: action,
                    data: {
                        form_key: window.FORM_KEY,
                        parameters: data,
                        action: 'create'
                    },
                    type: 'POST',
                    dataType: 'json',

                    success: function(response, textStatus, jqXHR) {
                        if (response.error) {
                            shipment.find('.js-dpdro-loader').removeClass('active');
                            var alert = `
                                <div class="dpdro-alert danger" role="alert">
                                    <span>` + response.message + `</span>
                                </div>
                            `;
                            alertMessage.prepend(alert);
                        } else {
                            location.reload();
                        }
                    },

                    fail: function() {
                        shipment.find('.js-dpdro-loader').removeClass('active');
                        var alert = `
                            <div class="dpdro-alert danger" role="alert">
                                <span>` + DPDRO_Orders_Text.error + `</span>
                            </div>
                        `;
                        alertMessage.prepend(alert);
                    }
                });

                return false;
            });

            $(document).on('click', '.js-dpdro-order-shipment-delete', function(e) {
                e.preventDefault();
                var action = DPDRO_Orders_Shipment + '?isAjax=true';
                var data = {
                    orderID: $(this).attr('data-id'),
                    shipmentID: $(this).attr('data-shipment-id')
                };
                $.ajax({
                    showLoader: true,
                    url: action,
                    data: {
                        form_key: window.FORM_KEY,
                        parameters: data,
                        action: 'delete'
                    },
                    type: 'POST',
                    dataType: 'json',

                    success: function(response, textStatus, jqXHR) {
                        if (response.error) {
                            $('.js-dpdro-alert').addClass('danger');
                            $('.js-dpdro-alert span').html(response.message);
                        } else {
                            location.reload();
                        }   
                    },

                    fail: function() {
                        $('.js-dpdro-alert').addClass('danger');
                        $('.js-dpdro-alert span').html(DPDRO_Orders_Text.error);
                    }
                });

                return false;
            });

            // =============================================================================
            // ORDERS Requests
            $(document).on('click', '.js-dpdro-order-pickup', function(e) {
                e.preventDefault();
                var action = DPDRO_Orders_Request + '?isAjax=true';
                var data = {
                    orderID: $(this).attr('data-id')
                };
                $.ajax({
                    showLoader: true,
                    url: action,
                    data: {
                        form_key: window.FORM_KEY,
                        parameters: data,
                        action: 'pickup'
                    },
                    type: 'POST',
                    dataType: 'json',

                    success: function(response, textStatus, jqXHR) {
                        if (response.error) {
                            $('.js-dpdro-alert').addClass('danger');
                            $('.js-dpdro-alert span').html(response.message);
                        } else {
                            location.reload();
                        }
                    },

                    fail: function() {
                        $('.js-dpdro-alert').addClass('danger');
                        $('.js-dpdro-alert span').html(DPDRO_Orders_Text.error);
                    }
                });

                return false;
            });

            $(document).on('click', '.js-dpdro-order-request', function(e) {
                e.preventDefault();
                var action = DPDRO_Orders_Request + '?isAjax=true';
                var requests = [];
                $.each($('[name="js-dpdro-shipment-request"]'), function(key, value) {
                    if ($(this).is(':checked')) {
                        requests.push($(this).val());
                    }
                });
                if (requests.length > 0) {
                    var data = {
                        ordersIDS: JSON.stringify(requests)
                    };
                    $.ajax({
                        showLoader: true,
                        url: action,
                        data: {
                            form_key: window.FORM_KEY,
                            parameters: data,
                            action: 'courier'
                        },
                        type: 'POST',
                        dataType: 'json'
                    }).success(function(response, textStatus, jqXHR) {
                        if (response.error) {
                            $('.js-dpdro-alert').addClass('danger');
                            $('.js-dpdro-alert span').html(response.message);
                        } else {
                            location.reload();
                        }
                    }).fail(function() {
                        $('.js-dpdro-alert').addClass('danger');
                        $('.js-dpdro-alert span').html(DPDRO_Orders_Text.error);
                    });
                }
                return false;
            });

        };

        $(document).ready(function() {
            DPD_JS();
        });
    }
);