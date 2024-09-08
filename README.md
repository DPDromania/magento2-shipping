# Magento DPD Ro Shipping extension


## 1. How to install Magento DPD Ro Shipping

Add the following lines into your composer.json
 
```
"require":{
    ...
    "dpdro/magento2-shipping":"{version}"
 }
```
or install via composer

```
composer require dpdro/magento2-shipping
```

Then execute the following commands:

```
$ composer update
$ bin/magento setup:upgrade
$ bin/magento setup:di:compile
$ bin/magento setup:static-content:deploy
```

## Locator Widget

The locator widget allows you to choose your preferred pickup location

![ScreenShot](https://raw.githubusercontent.com/DPDromania/magento2-shipping/main/Snapshot/locator-widget-map.png)

![ScreenShot](https://raw.githubusercontent.com/DPDromania/magento2-shipping/main/Snapshot/locator-widget-selection.png)