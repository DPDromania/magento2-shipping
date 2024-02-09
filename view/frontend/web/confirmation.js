define(
    [
        'jquery',
        'uiRegistry',
        'uiComponent',
        'mage/translate',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/shipping-rate-registry',
    ],
    function($, uiRegistry, component, translate, quote, customer, rate) {

        'use strict';

        const connected = window.checkoutConfig.dpdro.connected;
        const checkActive = window.checkoutConfig.dpdro.active;
        const addresses = window.checkoutConfig.dpdro.addresses;
        const offices = window.checkoutConfig.dpdro.offices;
        const officesGroup = window.checkoutConfig.dpdro.officesGroup;
        const session = window.checkoutConfig.dpdro.session;
        const ajax = window.checkoutConfig.dpdro.ajax;

        window.dpdro = {
            method: 'address',
            pickup: '',
        };
        if (connected == 'success' && checkActive) {
            if (session) {
                if (session['method']) {
                    window.dpdro['method'] = session['method'];
                }
                if (session['pickup']) {
                    window.dpdro['pickup'] = session['pickup'];
                }
            }
        }

        // =================================================================================
        // CONFIRMATION HTML
        function DPD_Confirmation(address, country, city) {
            var methodAddress = 'checked="checked"';
            var methodPickup = '';
            if (window.dpdro && window.dpdro['method']) {
                if (window.dpdro['method'] == 'pickup') {
                    methodAddress = '';
                    methodPickup = 'checked="checked"';
                }
            }
            var options = '';
            if (country && city) {
                var cityName = city.split('(');
                if (cityName && cityName.length > 0) {
                    city = cityName[0].trim();
                }
                if (officesGroup[country] && officesGroup[country] != '') {
                    if (officesGroup[country][city] && officesGroup[country][city] != '') {
                        $.each(officesGroup[country][city], function(key, value) {
                            if (window.dpdro && window.dpdro['pickup'] && window.dpdro['pickup'] == key) {
                                options += '<option selected value="' + key + '">' + value + '</option>';
                            } else {
                                options += '<option value="' + key + '">' + value + '</option>';
                            }
                        });
                    }
                }
            }
            var html = false;
            if (address && address != '' && options && options != '') {
                html = `
                    <h3>` + translate('DPD RO Shipping Method') + `</h3>
                    <p>` + translate('Confirma adresa introdusa:') + `</p>
                    <ul>
                        <li>
                            <input id="dpdro-shipping-method-address" type="radio" value="address" name="js-dpdro-shipping-method" ` + methodAddress + ` />
                            <label for="dpdro-shipping-method-address">
                                <span>` + translate('Adresa de livrare:') + `</span>
                                <b>` + address + `</b>
                            </label>
                        </li>
                        <li>
                            <input id="dpdro-shipping-method-pickup" type="radio" value="pickup" name="js-dpdro-shipping-method" ` + methodPickup + ` />
                            <label for="dpdro-shipping-method-pickup">
                                <span>` + translate('Ridica din dpdBox:') + `</span>
                                <select name="js-dpdro-shipping-method-pickup">
                                    <option value="">` + translate(' --- Selecteaza dpdBox --- ') + `</option>
                                    ` + options + `
                                </select>
                            </label>
                        </li>
                    </ul>
                `;
            }
            return html;
        }

        // =================================================================================
        // DPD ADDRESS LIST
        function DPD_GetAddresses(country = false, region = false, name = false, city = false) {
            if (country && country != '') {
                if (region && region != '') {
                    var countryKey = '642';
                    var regionKey = region;
                    if (country == 'BG') {
                        countryKey = '100';
                    }
                    if (addresses[countryKey] && addresses[countryKey] != '') {
                        if (addresses[countryKey][regionKey] && addresses[countryKey][regionKey] != '') {
                            if (addresses[countryKey][regionKey]['streets'] && addresses[countryKey][regionKey]['streets'] != '') {
                                if (city) {
                                    var cityData = [];
                                    $.each(addresses[countryKey][regionKey]['streets'], function(key, value) {
                                        if (value['name'] == city) {
                                            cityData = value;
                                        }
                                    });
                                    if (Object.keys(cityData).length > 0) {
                                        return cityData;
                                    }
                                } else {
                                    if (name) {
                                        return addresses[countryKey][regionKey]['name'];
                                    }
                                    return addresses[countryKey][regionKey]['streets'];
                                }
                            }
                        }
                    }
                }
            }
            return false;
        }

        // =================================================================================
        // RELOAD BY QUOTE
        function MAGENTO_ReloadShipping() {
            var address = quote.shippingAddress();
            rate.set(address.getKey(), null);
            rate.set(address.getCacheKey(), null);
            address.countryId = $('#shipping [name="country_id"]').val();
            address.regionId = $('#shipping [name="region_id"]').val();
            address.region = $('#shipping [name="region_id"] option:selected').text();
            address.city = $('#shipping [name="city"]').val();
            address.postcode = $('#shipping [name="postcode"]').val();
            quote.shippingAddress(address);
        }

        // =================================================================================
        // MAGENTO CITY
        function MAGENTO_ChangeCity(shippingAddress) {
            $('#shipping .js-dpdro-shipping-city-select').remove();
            var country = $('#shipping [name="country_id"]').val();
            var region = $('#shipping [name="region_id"]').val();
            var city = $('#shipping [name="city"]').val();
            if (shippingAddress && shippingAddress != '') {
                if (shippingAddress['country_id'] && shippingAddress['country_id'] != '') {
                    if (country == '') {
                        country = shippingAddress['country_id'];
                    }
                }
                if (shippingAddress['region_id'] && shippingAddress['region_id'] != '') {
                    if (region == '') {
                        region = shippingAddress['region_id'];
                    }
                }
                if (shippingAddress['city'] && shippingAddress['city'] != '') {
                    if (city == '') {
                        city = shippingAddress['city'];
                    }
                }
            }
            if ((country == 'RO' || country == 'BG') && region != '') {
                $('#shipping [name="city"]').addClass('dpdro-hide');
                var options = '';
                var disabled = 'disabled';
                var list = DPD_GetAddresses(country, region);
                var postcode = '';
                if (list && list.length > 0) {
                    for (var i = 0; i < list.length; i++) {
                        var addressName = list[i].nameComplete.replace('"', '').replace("'", "");
                        if (city && city != '' && city == list[i].name) {
                            postcode = list[i].postcode;
                            options += "<option selected data-id=" + list[i].id + " data-postcode=" + list[i].postcode + " value=" + addressName + ">" + addressName + "</option>";
                        } else {
                            options += "<option data-id=" + list[i].id + " data-postcode=" + list[i].postcode + " value=" + addressName + ">" + addressName + "</option>";
                        }
                    }
                    disabled = '';
                }
                var field = `
                    <select class="js-dpdro-shipping-city-select" ` + disabled + `>
                        <option value>` + translate(' --- Please Select --- ') + `</option>
                        ` + options + `
                    </select>
                `;
                $('#shipping [name="city"]').closest('.field').append(field);
                $('#shipping [name="postcode"]').val(postcode).trigger('change');
            } else {
                $('#shipping [name="city"]').val('').trigger('change');
                $('#shipping [name="postcode"]').val('').trigger('change');
                $('#shipping [name="city"]').removeClass('dpdro-hide');
            }
        }

        function MAGENTO_ChangeCityNew(country, region, city = false) {
            $('#shipping-new-address-form .js-dpdro-shipping-city-select').remove();
            if ((country == 'RO' || country == 'BG') && region != '') {
                $('#shipping-new-address-form [name="city"]').addClass('dpdro-hide');
                var options = '';
                var disabled = 'disabled';
                var list = DPD_GetAddresses(country, region);
                var postcode = '';
                if (list && list.length > 0) {
                    for (var i = 0; i < list.length; i++) {
                        if (city && city != '' && city == list[i].name) {
                            postcode = list[i].postcode;
                            options += "<option selected data-id=" + list[i].id + " data-postcode=" + list[i].postcode + " value=" + list[i].name + ">" + list[i].nameComplete + "</option>";
                        } else {
                            options += "<option data-id=" + list[i].id + " data-postcode=" + list[i].postcode + " value=" + list[i].name + ">" + list[i].nameComplete + "</option>";
                        }
                    }
                    disabled = '';
                }
                var field = `
                    <select class="js-dpdro-shipping-city-select" ` + disabled + `>
                        <option value>` + translate(' --- Please Select --- ') + `</option>
                        ` + options + `
                    </select>
                `;
                $('#shipping-new-address-form [name="city"]').closest('.field').append(field);
                $('#shipping-new-address-form [name="postcode"]').val(postcode).trigger('change');
            } else {
                $('#shipping-new-address-form [name="city"]').val('').trigger('change');
                $('#shipping-new-address-form [name="postcode"]').val('').trigger('change');
                $('#shipping-new-address-form [name="city"]').removeClass('dpdro-hide');
            }
        }

        $(document).ready(function() {
            if (connected == 'success' && checkActive) {

                // =============================================================================
                // ADDRESS
                if (customer.isLoggedIn()) {
                    $(document).on('change', '#shipping-new-address-form [name="country_id"]', function() {
                        var country = $(this).val();
                        var region = $('#shipping-new-address-form [name="region_id"]').val();
                        MAGENTO_ChangeCityNew(country, region);
                    });
                    $(document).on('change', '#shipping-new-address-form [name="region_id"]', function() {
                        var country = $('#shipping-new-address-form [name="country_id"]').val();
                        var region = $(this).val();
                        MAGENTO_ChangeCityNew(country, region);
                    });
                    $(document).on('change', '#shipping-new-address-form .js-dpdro-shipping-city-select', function() {
                        $('#shipping-new-address-form [name="city"]').val($(this).find('option:selected').text()).trigger('change');
                        $('#shipping-new-address-form [name="postcode"]').val($(this).find('option:selected').attr('data-postcode')).trigger('change');
                    });
                    $(document).on('click', '.action-select-shipping-item, .action-save-address', function() {
                        var addressList = [];
                        var shippingAddress = quote.shippingAddress();
                        if (shippingAddress) {
                            var country = false;
                            if (shippingAddress['countryId'] != '') {
                                country = shippingAddress['countryId'];
                                if (shippingAddress['countryId'] == 'BG') {
                                    addressList.push('Bulgaria');
                                } else if (shippingAddress['countryId'] == 'RO') {
                                    addressList.push('Romania');
                                }
                            }
                            if (shippingAddress['region']) {
                                addressList.push(shippingAddress['region']);
                            }
                            var city = false;
                            if (shippingAddress['city']) {
                                city = shippingAddress['city'];
                                addressList.push(shippingAddress['city']);
                            }
                            if (shippingAddress['street']) {
                                var shippingAddressStreet = shippingAddress['street'];
                                var shippingAddressStreetString = '';
                                if (shippingAddressStreet && shippingAddressStreet.length > 0) {
                                    shippingAddressStreet.forEach(function(item, index) {
                                        if (shippingAddressStreetString == '') {
                                            shippingAddressStreetString += item;
                                        } else {
                                            shippingAddressStreetString += ' '.item;
                                        }
                                    });
                                }
                                addressList.push(shippingAddressStreetString);
                            }
                            if (country && (country == 'RO' || country == 'BG')) {
                                var address = addressList.join(', ');
                                var html = DPD_Confirmation(address, country, city);
                                if (html) {
                                    if ($('.js-dpdro-confirmation').length > 0) {
                                        $('.js-dpdro-confirmation').html(html);
                                    } else {
                                        $('<div class="dpdro-confirmation js-dpdro-confirmation">' + html + '</div>').insertBefore('#checkout-step-shipping_method');
                                    }
                                    $('.js-dpdro-confirmation').removeClass('dpdro-hide');
                                } else {
                                    $('.js-dpdro-confirmation').addClass('dpdro-hide');
                                }
                            } else {
                                $('.js-dpdro-confirmation').remove();
                            }
                        }
                    });
                } else {
                    $(document).on('change', '#shipping [name="country_id"]', function() {
                        var shippingAddress = uiRegistry.get('checkoutProvider').shippingAddress;
                        var country = $(this).val();
                        var city = $('#shipping [name="city"]').val();
                        if (shippingAddress && shippingAddress != '') {
                            if (shippingAddress['country_id'] && shippingAddress['country_id'] != '') {
                                if (country == '') {
                                    country = shippingAddress['country_id'];
                                }
                            }
                            if (shippingAddress['city'] && shippingAddress['city'] != '') {
                                if (city == '') {
                                    city = shippingAddress['city'];
                                }
                            }
                        }
                        MAGENTO_ChangeCity(shippingAddress);
                        if (country == 'RO' || country == 'BG') {
                            if (city == '') {
                                $('.js-dpdro-confirmation').addClass('dpdro-hide');
                            }
                        } else {
                            $('.js-dpdro-confirmation').remove();
                        }
                    });
                    $(document).on('change', '#shipping [name="region_id"]', function() {
                        var shippingAddress = uiRegistry.get('checkoutProvider').shippingAddress;
                        var country = $(this).val();
                        var city = $('#shipping [name="city"]').val();
                        if (shippingAddress && shippingAddress != '') {
                            if (shippingAddress['country_id'] && shippingAddress['country_id'] != '') {
                                if (country == '') {
                                    country = shippingAddress['country_id'];
                                }
                            }
                            if (shippingAddress['city'] && shippingAddress['city'] != '') {
                                if (city == '') {
                                    city = shippingAddress['city'];
                                }
                            }
                        }
                        MAGENTO_ChangeCity(shippingAddress);
                        if (country == 'RO' || country == 'BG') {
                            if (city == '') {
                                $('.js-dpdro-confirmation').addClass('dpdro-hide');
                            }
                        } else {
                            $('.js-dpdro-confirmation').remove();
                        }
                    });
                    $(document).on('change', '#shipping .js-dpdro-shipping-city-select', function() {
                        $('#shipping [name="city"]').val($(this).find('option:selected').text()).trigger('change');
                        $('#shipping [name="postcode"]').val($(this).find('option:selected').attr('data-postcode')).trigger('change');
                        MAGENTO_ReloadShipping();
                        var countryID = $('#shipping [name="country_id"] option:selected').val();
                        var country = $('#shipping [name="country_id"] option:selected').text();
                        var region = '';
                        var city = $(this).find('option:selected').text();
                        var street = '';
                        if ($('#shipping [name="region_id"] option:selected').text() != '') {
                            region = $('#shipping [name="region_id"] option:selected').text();
                        }
                        if ($('#shipping [name="street[0]"]').val() != '') {
                            street = $('#shipping [name="street[0]"]').val();
                        }
                        var shippingAddress = uiRegistry.get('checkoutProvider').shippingAddress;
                        if (shippingAddress && shippingAddress != '') {
                            if (shippingAddress['country_id'] && shippingAddress['country_id'] != '') {
                                countryID = shippingAddress['country_id'];
                                if (shippingAddress['country_id'] == 'RO') {
                                    country = 'Romania';
                                } else if (shippingAddress['country_id'] == 'BG') {
                                    country = 'Bulgaria';
                                }
                            }
                            if (shippingAddress['region'] && shippingAddress['region'] != '') {
                                region = shippingAddress['region'];
                            }
                            if (shippingAddress['city'] && shippingAddress['city'] != '') {
                                city = shippingAddress['city'];
                            }
                            if (shippingAddress['street']) {
                                var shippingAddressStreet = shippingAddress['street'];
                                var shippingAddressStreetString = '';
                                if (shippingAddressStreet && shippingAddressStreet.length > 0) {
                                    shippingAddressStreet.forEach(function(item, index) {
                                        if (shippingAddressStreetString == '') {
                                            shippingAddressStreetString += item;
                                        } else {
                                            shippingAddressStreetString += ' '.item;
                                        }
                                    });
                                }
                                street = shippingAddressStreetString;
                            }
                        }
                        var address = '';
                        var addressList = [];
                        if (country != '') {
                            addressList.push(country);
                        }
                        if (region != '') {
                            addressList.push(region);
                        }
                        if (city != '') {
                            addressList.push(city);
                        }
                        if (street != '') {
                            addressList.push(street);
                        }
                        address = addressList.join(', ');
                        var html = DPD_Confirmation(address, countryID, city);
                        if (html) {
                            if ($('.js-dpdro-confirmation').length > 0) {
                                $('.js-dpdro-confirmation').html(html);
                            } else {
                                $('<div class="dpdro-confirmation js-dpdro-confirmation">' + html + '</div>').insertBefore('#checkout-step-shipping_method');
                            }
                            $('.js-dpdro-confirmation').removeClass('dpdro-hide');
                        } else {
                            $('.js-dpdro-confirmation').addClass('dpdro-hide');
                        }
                    });
                    $(document).on('change', '#shipping [name="street[0]"]', function() {
                        var countryID = $('#shipping [name="country_id"] option:selected').val();
                        var country = $('#shipping [name="country_id"] option:selected').text();
                        var region = '';
                        var city = '';
                        var street = $(this).val();
                        if ($('#shipping [name="region_id"] option:selected').text() != '') {
                            region = $('#shipping [name="region_id"] option:selected').text();
                        }
                        if ($('#shipping [name="city"]').val() != '') {
                            city = $('#shipping [name="city"]').val();
                        }
                        var shippingAddress = uiRegistry.get('checkoutProvider').shippingAddress;
                        if (shippingAddress && shippingAddress != '') {
                            if (shippingAddress['country_id'] && shippingAddress['country_id'] != '') {
                                countryID = shippingAddress['country_id'];
                                if (shippingAddress['country_id'] == 'RO') {
                                    country = 'Romania';
                                } else if (shippingAddress['country_id'] == 'BG') {
                                    country = 'Bulgaria';
                                }
                            }
                            if (shippingAddress['region'] && shippingAddress['region'] != '') {
                                region = shippingAddress['region'];
                            }
                            if (shippingAddress['city'] && shippingAddress['city'] != '') {
                                city = shippingAddress['city'];
                            }
                            if (shippingAddress['street']) {
                                var shippingAddressStreet = shippingAddress['street'];
                                var shippingAddressStreetString = '';
                                if (shippingAddressStreet && shippingAddressStreet.length > 0) {
                                    shippingAddressStreet.forEach(function(item, index) {
                                        if (shippingAddressStreetString == '') {
                                            shippingAddressStreetString += item;
                                        } else {
                                            shippingAddressStreetString += ' '.item;
                                        }
                                    });
                                }
                                street = shippingAddressStreetString;
                            }
                        }
                        var address = '';
                        var addressList = [];
                        if (country != '') {
                            addressList.push(country);
                        }
                        if (region != '') {
                            addressList.push(region);
                        }
                        if (city != '') {
                            addressList.push(city);
                        }
                        if (street != '') {
                            addressList.push(street);
                        }
                        address = addressList.join(', ');
                        var html = DPD_Confirmation(address, countryID, city);
                        if (html) {
                            if ($('.js-dpdro-confirmation').length > 0) {
                                $('.js-dpdro-confirmation').html(html);
                            } else {
                                $('<div class="dpdro-confirmation js-dpdro-confirmation">' + html + '</div>').insertBefore('#checkout-step-shipping_method');
                            }
                            $('.js-dpdro-confirmation').removeClass('dpdro-hide');
                        } else {
                            $('.js-dpdro-confirmation').addClass('dpdro-hide');
                        }
                    });
                }

                // =============================================================================
                // CONFIRMATION
                $(document).on('change', '[name="js-dpdro-shipping-method"]:checked', function() {
                    var method = $(this).val();
                    var pickup = '';
                    if (method == 'pickup') {
                        if ($('[name="js-dpdro-shipping-method-pickup"]').val() == '') {
                            return;
                        } else {
                            pickup = $('[name="js-dpdro-shipping-method-pickup"]').val();
                        }
                    }
                    $.ajax({
                        type: "POST",
                        url: ajax,
                        data: {
                            'action': 'set',
                            'type': 'confirmation',
                            'parameters': {
                                'method': method,
                                'pickup': pickup
                            }
                        },
                        async: false,
                        success: function(response) {
                            window.dpdro['method'] = method;
                            window.dpdro['pickup'] = pickup;
                            MAGENTO_ReloadShipping();
                        }
                    });
                });
                $(document).on('change', '[name="js-dpdro-shipping-method-pickup"]', function() {
                    var method = 'pickup';
                    var pickup = $(this).val();
                    if (pickup == '') {
                        $('[name="js-dpdro-shipping-method"][value="address"]').prop('checked', true);
                        $('[name="js-dpdro-shipping-method"][value="pickup"]').prop('checked', false);
                        return;
                    }
                    $('[name="js-dpdro-shipping-method"][value="address"]').prop('checked', false);
                    $('[name="js-dpdro-shipping-method"][value="pickup"]').prop('checked', true);
                    $.ajax({
                        type: "POST",
                        url: ajax,
                        data: {
                            'action': 'set',
                            'type': 'confirmation',
                            'parameters': {
                                'method': method,
                                'pickup': pickup
                            }
                        },
                        async: false,
                        success: function(response) {
                            window.dpdro['method'] = method;
                            window.dpdro['pickup'] = pickup;
                            MAGENTO_ReloadShipping();
                        }
                    });
                });

                // =============================================================================
                // SET / UNSET ADDRESS
                $(document).on('click', '#shipping-method-buttons-container button', function() {
                    if (customer.isLoggedIn()) {
                        var checkoutQuote = quote.shippingAddress();
                        var cityData = DPD_GetAddresses(checkoutQuote['countryId'], checkoutQuote['regionId'], false, checkoutQuote['city']);
                        $.ajax({
                            type: "POST",
                            url: ajax,
                            data: {
                                'action': 'set',
                                'type': 'address',
                                'parameters': {
                                    'cityID': cityData['id'],
                                    'cityName': cityData['name']
                                }
                            },
                            async: false,
                            success: function(response) {}
                        });
                    } else if ($('#shipping .js-dpdro-shipping-city-select').length > 0) {
                        var addressCityID = $('#shipping .js-dpdro-shipping-city-select option:selected').attr('data-id');
                        var addressCityName = $('#shipping .js-dpdro-shipping-city-select option:selected').val();
                        $.ajax({
                            type: "POST",
                            url: ajax,
                            data: {
                                'action': 'set',
                                'type': 'address',
                                'parameters': {
                                    'cityID': addressCityID,
                                    'cityName': addressCityName
                                }
                            },
                            async: false,
                            success: function(response) {}
                        });
                    } else {
                        $.ajax({
                            type: "POST",
                            url: ajax,
                            data: {
                                'action': 'unset',
                                'type': 'address'
                            },
                            async: false,
                            success: function(response) {}
                        });
                    }
                });

            }
        });

        return component.extend({
            defaults: {
                template: 'DpdRo_Shipping/confirmation',
            },
            confirmation: function() {
                // =================================================================================
                // GET ADDRESS
                var shippingAddress = {
                    country_id: '',
                    region_id: '',
                    country: '',
                    region: '',
                    city: '',
                    street: ''
                };
                var checkoutProvider = uiRegistry.get('checkoutProvider').shippingAddress;
                if (checkoutProvider['country_id']) {
                    shippingAddress['country_id'] = checkoutProvider['country_id'];
                    if (checkoutProvider['country_id'] == 'BG') {
                        shippingAddress['country'] = 'Bulgaria';
                    } else if (checkoutProvider['country_id'] == 'RO') {
                        shippingAddress['country'] = 'Romania';
                    }
                }
                if (checkoutProvider['region_id']) {
                    shippingAddress['region_id'] = checkoutProvider['region_id'];
                }
                if (checkoutProvider['city']) {
                    shippingAddress['city'] = checkoutProvider['city'];
                }
                if (checkoutProvider['street']) {
                    var shippingAddressStreet = checkoutProvider['street'];
                    var shippingAddressStreetString = '';
                    if (shippingAddressStreet && shippingAddressStreet.length > 0) {
                        shippingAddressStreet.forEach(function(item, index) {
                            if (shippingAddressStreetString == '') {
                                shippingAddressStreetString += item;
                            } else {
                                shippingAddressStreetString += ' '.item;
                            }
                        });
                    }
                    shippingAddress['street'] = shippingAddressStreetString;
                }
                if (customer.isLoggedIn()) {
                    var checkoutQuote = quote.shippingAddress();
                    if (checkoutQuote['countryId']) {
                        shippingAddress['country_id'] = checkoutQuote['countryId'];
                        if (checkoutQuote['country_id'] == 'BG') {
                            shippingAddress['country'] = 'Bulgaria';
                        } else if (checkoutQuote['country_id'] == 'RO') {
                            shippingAddress['country'] = 'Romania';
                        }
                    }
                    if (checkoutQuote['regionId']) {
                        shippingAddress['region_id'] = checkoutQuote['regionId'];
                    }
                    if (checkoutQuote['region']) {
                        shippingAddress['region'] = checkoutQuote['region'];
                    }
                    if (checkoutQuote['city']) {
                        shippingAddress['city'] = checkoutQuote['city'];
                    }
                    if (checkoutQuote['street']) {
                        var shippingAddressStreet = checkoutProvider['street'];
                        var shippingAddressStreetString = '';
                        if (shippingAddressStreet && shippingAddressStreet.length > 0) {
                            shippingAddressStreet.forEach(function(item, index) {
                                if (shippingAddressStreetString == '') {
                                    shippingAddressStreetString += item;
                                } else {
                                    shippingAddressStreetString += ' '.item;
                                }
                            });
                        }
                        shippingAddress['street'] = shippingAddressStreetString;
                    }
                }
                if (connected == 'success' && checkActive) {
                    if (shippingAddress['country_id'] && (shippingAddress['country_id'] == 'RO' || shippingAddress['country_id'] == 'BG')) {
                        // =================================================================================
                        // BUILD ADDRESS
                        var address = '';
                        var addressList = [];
                        addressList.push(shippingAddress['country']);
                        if (shippingAddress['region'] != '') {
                            addressList.push(shippingAddress['region']);
                        } else {
                            if (shippingAddress['country_id'] != '' && shippingAddress['region_id'] != '') {
                                addressList.push(DPD_GetAddresses(shippingAddress['country_id'], shippingAddress['region_id'], true));
                            }
                        }
                        if (shippingAddress['city'] != '') {
                            addressList.push(shippingAddress['city']);
                        }
                        if (shippingAddress['street'] != '') {
                            addressList.push(shippingAddress['street']);
                        }
                        address = addressList.join(', ');
                        if (shippingAddress['region_id'] != '' && shippingAddress['city'] != '') {
                            var html = DPD_Confirmation(address, shippingAddress['country_id'], shippingAddress['city']);
                            if (html) {
                                $('.js-dpdro-confirmation').html(html);
                                $('.js-dpdro-confirmation').removeClass('dpdro-hide');
                            } else {
                                $('.js-dpdro-confirmation').addClass('dpdro-hide');
                            }
                        } else {
                            $('.js-dpdro-confirmation').addClass('dpdro-hide');
                        }
                    } else {
                        $('.js-dpdro-confirmation').remove();
                    }
                }
            },
            connected: false,
            initialize: function() {
                if (connected == 'success' && checkActive) {
                    this.connected = true;
                }
                this._super();
            }
        });
    }
);