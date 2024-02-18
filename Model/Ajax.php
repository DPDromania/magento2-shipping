<?php

namespace DpdRo\Shipping\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;

class Ajax
{
	const DPD_API_URL = 'https://api.dpd.ro/v1/';

	// ========================================================================================
	// GENERAL
	// ========================================================================================
	public function DB_GetTable($table)
	{
		$obj = ObjectManager::getInstance();
		$resource = $obj->get('Magento\Framework\App\ResourceConnection');
		$resourceConnection = $resource->getConnection();
		$response = $resourceConnection->getTableName($table);
		// if ($response == $table) {
		// 	$response = 'msrt_' . $response;
		// }
		return $response;
	}
	public function DB_Fetch($query)
	{
		$obj = ObjectManager::getInstance();
		$resource = $obj->get('Magento\Framework\App\ResourceConnection');
		$resourceConnection = $resource->getConnection();
		$response = $resourceConnection->fetchAll($query);
		return $response;
	}
	public function DB_Query($query)
	{
		$obj = ObjectManager::getInstance();
		$resource = $obj->get('Magento\Framework\App\ResourceConnection');
		$resourceConnection = $resource->getConnection();
		$response = $resourceConnection->query($query);
		return $response;
	}
	public function ApiUrl()
	{
		return self::DPD_API_URL;
	}
	public function Settings($express = false)
	{
		$response = [
			'username'                   => $this->SettingsByName('username'),
			'password'                   => $this->SettingsByName('password'),
			'packagingMethod'            => $this->SettingsByName('packagingMethod'),
			'services'                   => explode(',', $this->SettingsByName('services')),
			'clientContracts'            => $this->SettingsByName('clientContracts'),
			'officeLocations'            => $this->SettingsByName('officeLocations'),
			'senderPayerInsurance'       => $this->SettingsByName('senderPayerInsurance'),
			'senderPayerIncludeShipping' => $this->SettingsByName('senderPayerIncludeShipping'),
			'payerCourier'               => $this->SettingsByName('payerCourier'),
			'payerCourierThirdParty'     => $this->SettingsByName('payerCourierThirdParty'),
			'printFormat'                => $this->SettingsByName('printFormat'),
			'printPaperSize'             => $this->SettingsByName('printPaperSize'),
			'maxParcelWeight'            => $this->SettingsByName('maxParcelWeight'),
			'maxParcelWeightAutomat'     => $this->SettingsByName('maxParcelWeightAutomat'),
			'status'                     => $this->SettingsByName('status'),
			'advanced'                   => $this->SettingsByName('advanced'),
		];
		if ($express) {
			return $response[$express];
		}
		return $response;
	}
	public function SettingsByName($name)
	{
		$tableName = $this->DB_GetTable('dpdro_settings');
		$query = "
			SELECT 
				`value` 
			FROM 
				`{$tableName}` 
			WHERE 
				`name` = '{$name}'
		";
		$response = $this->DB_Fetch($query);
		if ($response && !empty($response)) {
			return $response[0]['value'];
		}
		return false;
	}
	public function ApiRequest($parameters, $json = true)
	{
		$settings = $this->Settings();
		$parameters['data']['userName'] = $settings['username'];
		$parameters['data']['password'] = $settings['password'];
		$requestData = json_encode($parameters['data']);
		$requestConnection = curl_init();
		curl_setopt_array($requestConnection, array(
			CURLOPT_SSL_VERIFYHOST  => false,
			CURLOPT_SSL_VERIFYPEER  => false,
			CURLOPT_URL             => self::DPD_API_URL . $parameters['api'],
			CURLOPT_RETURNTRANSFER  => true,
			CURLOPT_ENCODING        => "",
			CURLOPT_MAXREDIRS       => 10,
			CURLOPT_TIMEOUT         => 120,
			CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST   => $parameters['method'],
			CURLOPT_POSTFIELDS      => $requestData,
			CURLOPT_HTTPHEADER      => array(
				"cache-control: no-cache",
				"content-type: application/json"
			),
		));
		$responseSuccess = curl_exec($requestConnection);
		$responseError = curl_error($requestConnection);
		curl_close($requestConnection);
		$logs = [
			'request' => $parameters['data'],
			'timestamp' => date('Y-m-d h:i:s')
		];
		if ($responseError) {
			$logs['response'] = $responseError;
		} else {
			$logs['response'] = __('Successfully connected!');
		}
		$json_logs = json_encode($logs);
		file_put_contents('dpdro-logs.json', print_r($json_logs, true) . "\n", FILE_APPEND);
		if ($responseError) {
			return $responseError;
		} else {
			if ($json) {
				return json_decode($responseSuccess, true);
			} else {
				return $responseSuccess;
			}
		}
	}
	public function CheckConnection()
	{
		$settings = $this->Settings();
		$username = $settings['username'];
		$password = $settings['password'];
		if ($username && !empty($username) && $password && !empty($password)) {
			$parameters = [
				'api'    => 'location/country',
				'method' => 'POST',
				'data'   => [
					'name' => 'ROMA'
				]
			];
			$response = $this->ApiRequest($parameters);
			if ($response && !array_key_exists('error', $response)) {
				return 'success';
			} else {
				return $response['error']['message'];
			}
		}
		return false;
	}
	public function CheckActive()
	{
		$checkActive = $this->Settings('status');
		if ($checkActive) {
			return true;
		}
		return false;
	}
	public function ReplaceDiacritics($string)
	{
		if ($string) {
			$keywords = [
				'Ă' => 'A', 'ă' => 'a', 'Â' => 'A', 'â' => 'a', 'Î' => 'I', 'î' => 'i', 'Ș' => 'S', 'ş' => 's', 'ș' => 's', 'Ț' => 'T', 'ț' => 't',
				'Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
				'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
				'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
				'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
				'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y'
			];
			$string = strtr($string, $keywords);
			$string = trim($string);
		}
		return $string;
	}
	public function GetCountryByID($countryID)
	{
		$parameters = [
			'api'    => 'location/country',
			'method' => 'POST',
			'data'   => [
				'isoAlpha2' => $countryID
			]
		];
		$response = $this->ApiRequest($parameters);
		if (!is_array($response) || array_key_exists('error', $response)) {
			return $response;
		} else {
			return $response['countries'][0];
		}
	}
	public function GetCityByName($country, $region, $city)
	{
		if ($country == 'RO' || $country == 'BG') {
			$countryId = 642;
			if ($country == 'BG') {
				$countryId = 100;
			}
			$parameters = [
				'api'    => 'location/site',
				'method' => 'POST',
				'data'   => [
					'countryId' => $countryId,
					'region'    => $this->ReplaceDiacritics($region),
					'name'      => $this->ReplaceDiacritics($city)
				]
			];
			$response = $this->ApiRequest($parameters);
			if ($response && is_array($response) && array_key_exists('sites', $response) && !empty($response['sites'])) {
				return $response['sites'][0];
			}
		}
		return false;
	}
	public function GetStreetsType($ISO = false)
	{
		if ($ISO) {
			$countryData = $this->GetCountryByID($ISO);
			$response = [];
			if (is_array($countryData) && array_key_exists('streetTypes', $countryData) && !empty($countryData['streetTypes'])) {
				foreach ($countryData['streetTypes'] as $type) {
					if (is_array($type) && array_key_exists('name', $type) && !empty($type['name'])) {
						array_push($response, $type['name']);
					}
				}
			}
			return $response;
		}
		return false;
	}
	// ========================================================================================
	// DPD LISTING
	// ========================================================================================
	public function ListServices()
	{
		$parameters = [
			'api'    => 'services',
			'method' => 'POST',
		];
		$response = $this->ApiRequest($parameters);
		if (is_array($response) && !empty($response) && array_key_exists('services', $response) && !empty($response['services'])) {
			$services = [];
			foreach ($response['services'] as $service) {
				$services[$service['id']] = $service['name'];
			}
			asort($services);
			return $services;
		} else {
			return [];
		}
	}
	public function ListAddresses($countryID)
	{
		$parameters = [
			'api'    => 'location/site/csv/' . $countryID,
			'method' => 'GET',
		];
		$response = $this->ApiRequest($parameters);
		if (is_array($response) && !empty($response)) {
			return $response;
		} else {
			return [];
		}
	}
	public function ListClientContracts()
	{
		$parameters = [
			'api'    => 'client/contract',
			'method' => 'POST',
		];
		$response = $this->ApiRequest($parameters);
		if (is_array($response) && !empty($response) && array_key_exists('clients', $response) && !empty($response['clients'])) {
			$clients = [];
			foreach ($response['clients'] as $client) {
				$clients[(string) $client['clientId']] = $client['address']['fullAddressString'];
			}
			return $clients;
		} else {
			return [];
		}
	}
	public function ListOfficeLocations()
	{
		$parameters = [
			'api'    => 'location/office',
			'method' => 'POST',
		];
		$response = $this->ApiRequest($parameters);
		if (is_array($response) && !empty($response) && array_key_exists('offices', $response) && !empty($response['offices'])) {
			$offices = [];
			foreach ($response['offices'] as $office) {
				$offices[(string) $office['id']] = $office['name'];
			}
			return $offices;
		} else {
			return [];
		}
	}
	public function ListOfficeLocationsGroups()
	{
		$countries = [
			100 => 'BG',
			642 => 'RO',
		];
		$offices = [];
		foreach ($countries as $id => $country) {
			$offices[$country] = [];
			$parameters = [
				'api'    => 'location/office',
				'method' => 'POST',
				'data'   => [
					'countryId' => $id,
					'name'      => ''
				]
			];
			$response = $this->ApiRequest($parameters);
			if (is_array($response) && !empty($response) && array_key_exists('offices', $response) && !empty($response['offices'])) {
				foreach ($response['offices'] as $office) {
					if (!isset($offices[$country][(string) $office['address']['siteName']])) {
						$offices[$country][(string) $office['address']['siteName']] = [];
					}
					$offices[$country][(string) $office['address']['siteName']][(string) $office['id']] = $office['name'];
				}
			}
		}
		return $offices;
	}
	public function ListPayerCourier()
	{
		$response = array(
			'SENDER'       => 'Sender',
			'RECIPIENT'    => 'Recipient',
			'THIRD_PARTY'  => 'Third party'
		);
		return $response;
	}
	public function ListPrintFormat()
	{
		$response = array(
			'pdf'      => 'pdf',
			'zpl'      => 'zpl',
			'html'     => 'html'
		);
		return $response;
	}
	public function ListPrintPaperSize()
	{
		$response = array(
			'A4_4xA6'  => 'A4 4xA6',
			'A4'       => 'A4',
			'A6'       => 'A6',
		);
		return $response;
	}
	// ========================================================================================
	// DPD DATABASE
	// ========================================================================================
	public function DPD_GetPaymentTax()
	{
		$tableName = $this->DB_GetTable('dpdro_payment');
		$query = "
			SELECT * 
			FROM 
				`{$tableName}`
		";
		$response = $this->DB_Fetch($query);
		if ($response && !empty($response)) {
			return $response;
		}
		return false;
	}
	public function DPD_GetPaymentTaxByCountryID($countryID = false)
	{
		$tableName = $this->DB_GetTable('dpdro_payment');
		$query = "
			SELECT * 
			FROM 
				`{$tableName}`
			WHERE 
				`countryID` = '{$countryID}' AND
				`status`    = '1'
		";
		$response = $this->DB_Fetch($query);
		if ($response && !empty($response)) {
			return $response;
		}
		return false;
	}
	public function DPD_GetTaxRates()
	{
		$tableName = $this->DB_GetTable('dpdro_order_tax_rates');
		$query = "
			SELECT * 
			FROM 
				`{$tableName}`
		";
		$response = $this->DB_Fetch($query);
		if ($response && !empty($response)) {
			return $response;
		}
		return false;
	}
	public function DPD_GetTaxRateByServiceId($serviceID, $products)
	{
		$tableName = $this->DB_GetTable('dpdro_order_tax_rates');
		$totalPrice = 0.0;
		$totalWeight = 0.0;
		foreach ($products as $product) {
			$totalWeight = (float) $totalWeight + ((float) $product['weight'] * (float) $product['quantity']);
			$totalPrice  = (float) $totalPrice + ((float) $product['price'] * (float) $product['quantity']);
		}
		if ($totalPrice == 0) {
			$cartData = $this->Magento_GetCartData(true);
			if ($cartData && isset($cartData['total'])) {
				$totalPrice = $cartData['total'];
			}
		}
		if ($totalWeight == 0) {
			$cartData = $this->Magento_GetCartData(true);
			if ($cartData && isset($cartData['products'])) {
				foreach ($cartData['products'] as $product) {
					$totalWeight = (float) $totalWeight + ((float) $product['weight'] * (float) $product['quantity']);
				}
			}
		}
		$query = "
			(
				SELECT * 
				FROM 
					`{$tableName}`
				WHERE 
					`serviceID` = {$serviceID} AND
					`applyOver` <= {$totalPrice} AND
					`status` = 1
				ORDER BY 
					`applyOver` DESC
			) UNION (
				SELECT * 
				FROM 
					`{$tableName}`
				WHERE 
					`serviceID` = {$serviceID} AND
					`applyOver` <= {$totalWeight} AND
					`status` = 1
				ORDER BY 
					`applyOver` DESC
			)
		";
		$response = $this->DB_Fetch($query);
		if ($response && !empty($response)) {
			$serviceCustomTaxes = array();
			foreach ($response as $taxRate) {
				if ($taxRate['basedOn']) {
					if ((float) $taxRate['applyOver'] <= (float) $totalPrice) {
						$serviceCustomTaxes[$taxRate['applyOver']] = $taxRate;
					}
				} else {
					if ((float) $taxRate['applyOver'] <= (float) $totalWeight) {
						$serviceCustomTaxes[$taxRate['applyOver']] = $taxRate;
					}
				}
			}
			arsort($serviceCustomTaxes);
			if (!empty($serviceCustomTaxes)) {
				return reset($serviceCustomTaxes);
			}
		}
		return false;
	}
	public function DPD_GetShipmentByOrderID($orderID)
	{
		$tableName = $this->DB_GetTable('dpdro_order_shipment');
		$orderID = (int) $orderID;
		$query = "
			SELECT * 
			FROM 
				`{$tableName}` 
			WHERE 
				`orderID` = {$orderID}
		";
		$response = $this->DB_Fetch($query);
		if ($response && !empty($response)) {
			return $response[0];
		}
		return false;
	}
	public function DPD_GetAddressNormalizedByOrderID($orderID)
	{
		$tableName = $this->DB_GetTable('dpdro_order_address');
		$orderID = (int) $orderID;
		$query = "
			SELECT * 
			FROM 
				`{$tableName}` 
			WHERE 
				`orderID` = {$orderID}
		";
		$response = $this->DB_Fetch($query);
		if ($response && !empty($response)) {
			return $response[0];
		}
		return false;
	}
	public function DPD_GetSettingsByOrderID($orderID)
	{
		$tableName = $this->DB_GetTable('dpdro_order_settings');
		$orderID = (int) $orderID;
		$query = "
			SELECT * 
			FROM 
				`{$tableName}` 
			WHERE 
				`orderID` = {$orderID}
		";
		$response = $this->DB_Fetch($query);
		if ($response && !empty($response)) {
			return $response[0];
		}
		return false;
	}
	public function DPD_GetAllCourierRequests()
	{
		$tableName = $this->DB_GetTable('dpdro_order_courier');
		$query = "
			SELECT * 
			FROM 
				`{$tableName}`
		";
		$response = $this->DB_Fetch($query);
		if ($response && !empty($response)) {
			return $response;
		}
		return false;
	}
	public function DPD_GetAddresses($page = 1, $orderBy = 'id', $orderDirection = 'ASC')
	{
		$tableName = $this->DB_GetTable('dpdro_addresses');
		$limitPage = 0;
		$limitRows = 50;
		if ($page > 1) {
			$limitPage = (int) ($page - 1) * $limitRows;
		}
		$query = "
			SELECT * 
			FROM 
				`{$tableName}`
			ORDER BY
				{$orderBy} {$orderDirection} 
			LIMIT 
				{$limitPage}, {$limitRows}
		";
		$response = $this->DB_Fetch($query);
		if ($response && !empty($response)) {
			return $response;
		}
		return false;
	}
	public function DPD_GetAddressesPagination()
	{
		$tableName = $this->DB_GetTable('dpdro_addresses');
		$query = "
			SELECT COUNT(*) 
			FROM 
				`{$tableName}`
		";
		$response = $this->DB_Fetch($query);
		if ($response && !empty($response)) {
			if (isset($response[0]['COUNT(*)'])) {
				return (int) $response[0]['COUNT(*)'];
			}
		}
		return false;
	}
	public function DPD_GetAddressesData($keys = false)
	{
		$tableName = $this->DB_GetTable('dpdro_addresses');
		$query = "
			SELECT *
			FROM 
				`{$tableName}`
		";
		$addresses = $this->DB_Fetch($query);
		if ($addresses && !empty($addresses)) {
			$response = [];
			foreach ($addresses as $address) {
				$country = 'RO';
				if ($address['countryID'] == '100') {
					$country = 'BG';
				}
				$name = $address['region'];
				$countryKey = $address['countryID'];
				$regionKey = $address['region'];
				if ($keys) {
					$checkRegion = $this->Magento_GetRegionByName($country, $name);
					if ($checkRegion && !empty($checkRegion)) {
						if (isset($checkRegion['region_id']) && !empty($checkRegion['region_id'])) {
							$regionKey = $checkRegion['region_id'];
						}
					}
				}
				$response[$countryKey][$regionKey]['name'] = $address['region'];
				$response[$countryKey][$regionKey]['streets'][] = [
					'id'           => $address['id'],
					'type'         => $address['type'],
					'name'         => $address['name'],
					'nameFull'     => $address['municipality'],
					'nameComplete' => $address['name'] . ' ( ' . $address['municipality'] . ' )',
					'region'       => $address['region'],
					'postcode'     => $address['postCode'],
				];
			}
			return $response;
		}
		return false;
	}
	public function DPD_GetAddressesRegions($countryID)
	{
		$tableName = $this->DB_GetTable('dpdro_addresses');
		$query = "
			SELECT 
				 `region`,
				 `regionEn`
			FROM 
				`{$tableName}`
			WHERE 
				`countryID` = '{$countryID}'
			GROUP BY
				`region`
		";
		$response = $this->DB_Fetch($query);
		if ($response && !empty($response)) {
			return $response;
		}
		return false;
	}
	public function DPD_GetAddressesCities($countryID, $region)
	{
		$tableName = $this->DB_GetTable('dpdro_addresses');
		$query = "
			SELECT 
				 `ID`,
				 `type`,
				 `typeEn`,
				 `name`,
				 `nameEn`,
				 `minicipality`,
				 `minicipalityEn`,
				 `postCode`
			FROM 
				`{$tableName}`
			WHERE 
				`countryID` = '{$countryID}' AND
				`region` = '{$region}'
			GROUP BY
				`region`
		";
		$response = $this->DB_Fetch($query);
		if ($response && !empty($response)) {
			return $response;
		}
		return false;
	}
	public function DPD_GetShippingTax($serviceID = false, $includeShipping = false, $orderID = false, $salesAddress = false)
	{
		$apiSettings = $this->Settings();
		$parameters = [
			'api' => 'calculate',
			'method' => 'POST',
			'data' => [
				'service' => [
					'serviceIds' => $apiSettings['services'],
					'autoAdjustPickupDate' => true,
				],
				'payment' => [
					'courierServicePayer' => $apiSettings['payerCourier']
				],
				'shipmentNote' => '',
			]
		];
		if ($serviceID) {
			$parameters['data']['service']['serviceIds'] = array($serviceID);
		}
		if ($apiSettings['payerCourier'] === 'THIRD_PARTY') {
			$parameters['data']['payment']['thirdPartyClientId'] = $apiSettings['payerCourierThirdParty'];
		}
		// ====================================================
		// Products parameters
		$totalWeight = 0;
		$totalPrice = 0;
		if ($orderID) {
			$order_data = $this->Magento_GetOrderData($orderID);
			foreach ($order_data['products'] as $product) {
				$price       = $product['price'];
				$weight      = $product['weight'];
				$quantity    = $product['quantity'];
				$totalWeight = (float) $totalWeight + ((float) $weight * (float) $quantity);
				$totalPrice  = (float) $totalPrice + ((float) $price * (float) $quantity);
			}
		} else {
			if ($salesAddress) {
				$cart_data = $this->Magento_GetCartData(true);
			} else {
				$cart_data = $this->Magento_GetCartData();
			}
			foreach ($cart_data['products'] as $product) {
				$price       = $product['price'];
				$weight      = $product['weight'];
				$quantity    = $product['quantity'];
				$totalWeight = (float) $totalWeight + ((float) $weight * (float) $quantity);
				$totalPrice  = (float) $totalPrice + ((float) $price * (float) $quantity);
			}
		}
		// ====================================================
		// Parcels parameters
		$parameters['data']['content'] = [
			'parcelsCount' => 1,
			'totalWeight'  => $totalWeight,
			'palletized'   => false,
			'documents'    => false,
			'parcels'      => []
		];
		if ((float) $totalWeight > (float) $apiSettings['maxParcelWeight']) {
			if ($orderID) {
				$products = $this->Magento_GetOrderData($orderID);
			} else {
				if ($salesAddress) {
					$products = $this->Magento_GetCartData(true);
				} else {
					$products = $this->Magento_GetCartData();
				}
			}
			$parcels = $this->DPD_PrepareParcels($products);
			$parameters['data']['content']['parcelsCount'] = (int) count($parcels);
			$parameters['data']['content']['parcels'] = $parcels;
		} else {
			$parameters['data']['content']['parcels'] = [
				0 => [
					'seqNo'  => '1',
					'weight' => $totalWeight
				]
			];
		}
		// ====================================================
		// Address parameters
		if ($orderID) {
			$address = $this->Magento_GetOrderAdress($orderID);
		} else {
			$address = $this->Magento_GetCartAdress($salesAddress);
		}
		$countryData = $this->GetCountryByID($address['country']);
		// ====================================================
		if ($address['country'] == 'RO' || $address['country'] == 'BG') {
			if ($apiSettings['clientContracts'] != '' && $apiSettings['clientContracts'] != '0') {
				$parameters['data']['sender']['clientId'] = (float) $apiSettings['clientContracts'];
			}
			if ($apiSettings['officeLocations'] != '' && $apiSettings['officeLocations'] != '0') {
				$parameters['data']['sender']['dropoffOfficeId'] = (float) $apiSettings['officeLocations'];
			}
			if ($orderID) {
				$shippingAddress = $this->DPD_GetAddressNormalizedByOrderID($orderID);
				$shippingMethod  = $shippingAddress['method'];
				$shippingOffice  = $shippingAddress['officeID'];
			} else {
				$shippingAddress = $this->DPD_GetSessionConfirmation('confirmation');
				if ($shippingAddress && !empty($shippingAddress)) {
					$shippingMethod  = $shippingAddress['method'];
					$shippingOffice  = $shippingAddress['pickup'];
				}
			}
			if (isset($shippingAddress) && !empty($shippingAddress)) {
				if ($shippingMethod && !empty($shippingMethod) && $shippingMethod == 'pickup') {
					$parameters['data']['recipient']['privatePerson'] = false;
					$parameters['data']['recipient']['pickupOfficeId'] = $shippingOffice;
				} else {
					$parameters['data']['recipient'] = [
						'addressLocation' => [
							'countryId' => (int) $countryData['id']
						],
						'privatePerson'   => false
					];
					$checkCityId = $this->GetCityByName($address['country'], $address['state'], $address['city']);
					if ($checkCityId && isset($checkCityId['id']) && !empty($checkCityId['id'])) {
						$parameters['data']['recipient']['addressLocation']['siteId'] = (int) $checkCityId['id'];
					} else {
						$parameters['data']['recipient']['addressLocation']['siteName'] = (string) $address['city'];
						if (array_key_exists('postCodeFormats', $countryData) && !empty($countryData['postCodeFormats']) && is_array($countryData['postCodeFormats'])) {
							$parameters['data']['recipient']['addressLocation']['postCode'] = (string) $address['postcode'];
						}
					}
				}
			} else {
				$parameters['data']['recipient'] = [
					'addressLocation' => [
						'countryId' => (int) $countryData['id']
					],
					'privatePerson'   => true
				];
				$checkCityId = $this->GetCityByName($address['country'], $address['state'], $address['city']);
				if ($checkCityId && isset($checkCityId['id']) && !empty($checkCityId['id'])) {
					$parameters['data']['recipient']['addressLocation']['siteId'] = (int) $checkCityId['id'];
				} else {
					$parameters['data']['recipient']['addressLocation']['siteName'] = (string) $address['city'];
					if (array_key_exists('postCodeFormats', $countryData) && !empty($countryData['postCodeFormats']) && is_array($countryData['postCodeFormats'])) {
						$parameters['data']['recipient']['addressLocation']['postCode'] = (string) $address['postcode'];
					}
				}
			}
		} else {
			$parameters['data']['recipient'] = [
				'addressLocation' => [
					'countryId' => (int) $countryData['id'],
					'siteName'  => (string) $address['city']
				],
				'privatePerson'   => true
			];
			if (array_key_exists('postCodeFormats', $countryData) && !empty($countryData['postCodeFormats']) && is_array($countryData['postCodeFormats'])) {
				$parameters['data']['recipient']['addressLocation']['postCode'] = (string) $address['postcode'];
			}
		}
		if ($serviceID && $includeShipping) {
			$obj = ObjectManager::getInstance();
			$store = $obj->get('Magento\Store\Model\StoreManagerInterface');
			$currency = $store->getStore()->getBaseCurrency()->getCode();
			$parameters['data']['service']['serviceIds'] = array($serviceID);
			$parameters['data']['payment']['courierServicePayer'] = 'SENDER';
			$parameters['data']['service']['additionalServices']['cod'] = [
				'currencyCode'         => $currency,
				'processingType'       => 'CASH',
				'amount'               => (float) number_format((float) $totalPrice, 2),
				'includeShippingPrice' => true
			];
		}
		if ($apiSettings['senderPayerInsurance'] === 'yes') {
			$parameters['data']['service']['additionalServices']['declaredValue']['amount'] = number_format((float) $totalPrice, 2);
		}
		$parameters['data']['recipient']['phone1']['number'] = (string) $address['phone'];
		$parameters['data']['recipient']['email'] = (string) $address['email'];
		$requestResponse = $this->ApiRequest($parameters);
		return $requestResponse;
	}
	public function DPD_GetShippingTaxByRequest($request, $serviceId = false, $includeShipping = false)
	{
		$obj               = ObjectManager::getInstance();
		$objWeight         = $obj->create('\Magento\Framework\App\Config\ScopeConfigInterface');
		$objWeightUnit     = $objWeight->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE);
		$objWeightUnits = [
			'kgs' => 1,
			'kg'  => 1,
			'lbs' => 2.2046,
			'lb'  => 2.2046,
			'gms' => 1000,
			'g'   => 1000,
		];
		$objSession        = $obj->create('\Magento\Checkout\Model\Session');
		$objSessionAddress = $objSession->getQuote()->getShippingAddress();
		// ====================================================
		// Prepare
		$apiSettings = $this->Settings();
		$parameters = [
			'api' => 'calculate',
			'method' => 'POST',
			'data' => [
				'service' => [
					'serviceIds' => $apiSettings['services'],
					'autoAdjustPickupDate' => true,
				],
				'payment' => [
					'courierServicePayer' => $apiSettings['payerCourier']
				],
				'shipmentNote' => '',
			]
		];
		if ($serviceId) {
			$parameters['data']['service']['serviceIds'] = array($serviceId);
		}
		if ($apiSettings['payerCourier'] === 'THIRD_PARTY') {
			$parameters['data']['payment']['thirdPartyClientId'] = $apiSettings['payerCourierThirdParty'];
		}
		// ====================================================
		// Products parameters
		$totalWeight = 0;
		$totalPrice = 0;
		foreach ($request->getAllItems() as $product) {
			$price       = (float) $this->Magento_CurrencyConvert($product->getPrice());
			$weight      = (float) $product->getWeight() * (float) $objWeightUnits[$objWeightUnit];
			$quantity    = $product->getQty();
			$totalWeight = (float) $totalWeight + ((float) $weight * (float) $quantity);
			$totalPrice  = (float) $totalPrice + ((float) $price * (float) $quantity);
		}
		// }
		// ====================================================
		// Parcels parameters
		$parameters['data']['content'] = [
			'parcelsCount' => 1,
			'totalWeight'  => $totalWeight,
			'palletized'   => false,
			'documents'    => false,
			'parcels'      => []
		];
		if ((float) $totalWeight > (float) $apiSettings['maxParcelWeight']) {
			$products = array();
			foreach ($request->getAllItems() as $product) {
				$products[]['weight']   = (float) $product->getWeight() * (float) $objWeightUnits[$objWeightUnit];
				$products[]['quantity'] = $product->getQty();
			}
			$parcels = $this->DPD_PrepareParcels($products);
			$parameters['data']['content']['parcelsCount'] = (int) count($parcels);
			$parameters['data']['content']['parcels'] = $parcels;
		} else {
			$parameters['data']['content']['parcels'] = [
				0 => [
					'seqNo'  => '1',
					'weight' => $totalWeight
				]
			];
		}
		// ====================================================
		// Address parameters
		$regionNameById = $this->Magento_GetRegionById($request->getDestRegionId());
		$regionName = '';
		if ($regionNameById && !empty($regionNameById)) {
			$regionName = $this->ReplaceDiacritics($regionNameById['default_name']);
		}
		$address = [
			'country'  => $this->ReplaceDiacritics($request->getDestCountryId()),
			'state'    => $this->ReplaceDiacritics($regionName),
			'city'     => $this->ReplaceDiacritics($request->getDestCity()),
			'street'   => $this->ReplaceDiacritics($request->getDestStreet()),
			'postcode' => $request->getDestPostcode(),
			'email'    => $objSessionAddress->getEmail(),
			'phone'    => $objSessionAddress->getTelephone(),
		];
		$countryData = $this->GetCountryByID($address['country']);
		// ====================================================
		if ($address['country'] == 'RO' || $address['country'] == 'BG') {
			if ($apiSettings['clientContracts'] != '' && $apiSettings['clientContracts'] != '0') {
				$parameters['data']['sender']['clientId'] = (float) $apiSettings['clientContracts'];
			}
			if ($apiSettings['officeLocations'] != '' && $apiSettings['officeLocations'] != '0') {
				$parameters['data']['sender']['dropoffOfficeId'] = (float) $apiSettings['officeLocations'];
			}
			$shippingAddress = $this->DPD_GetSessionConfirmation('confirmation');
			if ($shippingAddress && !empty($shippingAddress)) {
				$shippingMethod  = $shippingAddress['method'];
				$shippingOffice  = $shippingAddress['pickup'];
			}
			if (isset($shippingAddress) && !empty($shippingAddress)) {
				if ($shippingMethod && !empty($shippingMethod) && $shippingMethod == 'pickup') {
					$parameters['data']['recipient']['privatePerson'] = false;
					$parameters['data']['recipient']['pickupOfficeId'] = $shippingOffice;
				} else {
					$parameters['data']['recipient'] = [
						'addressLocation' => [
							'countryId' => (int) $countryData['id'],
						],
						'privatePerson'   => false
					];
					$checkCityId = $this->GetCityByName($address['country'], $address['state'], $address['city']);
					if ($checkCityId && isset($checkCityId['id']) && !empty($checkCityId['id'])) {
						$parameters['data']['recipient']['addressLocation']['siteId'] = (int) $checkCityId['id'];
					} else {
						$parameters['data']['recipient']['addressLocation']['siteName'] = (string) $address['city'];
						if (array_key_exists('postCodeFormats', $countryData) && !empty($countryData['postCodeFormats']) && is_array($countryData['postCodeFormats'])) {
							$parameters['data']['recipient']['addressLocation']['postCode'] = (string) $address['postcode'];
						}
					}
				}
			} else {
				$parameters['data']['recipient'] = [
					'addressLocation' => [
						'countryId' => (int) $countryData['id'],
					],
					'privatePerson'   => true
				];
				$checkCityId = $this->GetCityByName($address['country'], $address['state'], $address['city']);
				if ($checkCityId && isset($checkCityId['id']) && !empty($checkCityId['id'])) {
					$parameters['data']['recipient']['addressLocation']['siteId'] = (int) $checkCityId['id'];
				} else {
					$parameters['data']['recipient']['addressLocation']['siteName'] = (string) $address['city'];
					if (array_key_exists('postCodeFormats', $countryData) && !empty($countryData['postCodeFormats']) && is_array($countryData['postCodeFormats'])) {
						$parameters['data']['recipient']['addressLocation']['postCode'] = (string) $address['postcode'];
					}
				}
			}
		} else {
			$parameters['data']['recipient'] = [
				'addressLocation' => [
					'countryId' => (int) $countryData['id'],
					'siteName'  => (string) $address['city']
				],
				'privatePerson'   => true
			];
			if (array_key_exists('postCodeFormats', $countryData) && !empty($countryData['postCodeFormats']) && is_array($countryData['postCodeFormats'])) {
				$parameters['data']['recipient']['addressLocation']['postCode'] = (string) $address['postcode'];
			}
		}
		if ($serviceId && $includeShipping) {
			$obj = ObjectManager::getInstance();
			$store = $obj->get('Magento\Store\Model\StoreManagerInterface');
			$currency = $store->getStore()->getBaseCurrency()->getCode();
			$parameters['data']['service']['serviceIds'] = array($serviceId);
			$parameters['data']['payment']['courierServicePayer'] = 'SENDER';
			$parameters['data']['service']['additionalServices']['cod'] = [
				'currencyCode'         => $currency,
				'processingType'       => 'CASH',
				'amount'               => (float) number_format((float) $totalPrice, 2),
				'includeShippingPrice' => true
			];
		}
		if ($apiSettings['senderPayerInsurance'] === 'yes') {
			$parameters['data']['service']['additionalServices']['declaredValue']['amount'] = number_format((float) $totalPrice, 2);
		}
		$parameters['data']['recipient']['phone1']['number'] = (string) $address['phone'];
		$parameters['data']['recipient']['email'] = (string) $address['email'];
		$requestResponse = $this->ApiRequest($parameters);
		return $requestResponse;
	}
	public function DPD_GetShippingTaxByQuote($quote, $serviceId = false, $includeShipping = false)
	{
		$obj               = ObjectManager::getInstance();
		$objWeight         = $obj->create('\Magento\Framework\App\Config\ScopeConfigInterface');
		$objWeightUnit     = $objWeight->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE);
		$objWeightUnits = [
			'kgs' => 1,
			'kg'  => 1,
			'lbs' => 2.2046,
			'lb'  => 2.2046,
			'gms' => 1000,
			'g'   => 1000,
		];
		// ====================================================
		// Prepare
		$apiSettings = $this->Settings();
		$parameters = [
			'api' => 'calculate',
			'method' => 'POST',
			'data' => [
				'service' => [
					'serviceIds' => $apiSettings['services'],
					'autoAdjustPickupDate' => true,
				],
				'payment' => [
					'courierServicePayer' => $apiSettings['payerCourier']
				],
				'shipmentNote' => '',
			]
		];
		if ($serviceId) {
			$parameters['data']['service']['serviceIds'] = array($serviceId);
		}
		if ($apiSettings['payerCourier'] === 'THIRD_PARTY') {
			$parameters['data']['payment']['thirdPartyClientId'] = $apiSettings['payerCourierThirdParty'];
		}
		// ====================================================
		// Products parameters
		$totalWeight = 0;
		$totalPrice = 0;
		foreach ($quote->getAllVisibleItems() as $product) {
			$price       = (float) $this->Magento_CurrencyConvert($product->getPrice());
			$weight      = (float) $product->getWeight() * (float) $objWeightUnits[$objWeightUnit];
			$quantity    = $product->getQty();
			$totalWeight = (float) $totalWeight + ((float) $weight * (float) $quantity);
			$totalPrice  = (float) $totalPrice + ((float) $price * (float) $quantity);
		}
		// }
		// ====================================================
		// Parcels parameters
		$parameters['data']['content'] = [
			'parcelsCount' => 1,
			'totalWeight'  => $totalWeight,
			'palletized'   => false,
			'documents'    => false,
			'parcels'      => []
		];
		if ((float) $totalWeight > (float) $apiSettings['maxParcelWeight']) {
			$products = array();
			foreach ($quote->getAllVisibleItems() as $product) {
				$products[]['weight']   = (float) $product->getWeight() * (float) $objWeightUnits[$objWeightUnit];
				$products[]['quantity'] = $product->getQty();
			}
			$parcels = $this->DPD_PrepareParcels($products);
			$parameters['data']['content']['parcelsCount'] = (int) count($parcels);
			$parameters['data']['content']['parcels'] = $parcels;
		} else {
			$parameters['data']['content']['parcels'] = [
				0 => [
					'seqNo'  => '1',
					'weight' => $totalWeight
				]
			];
		}
		// ====================================================
		// Address parameters
		$regionNameById = $this->Magento_GetRegionById($quote->getShippingAddress()->getRegionId());
		$regionName = '';
		if ($regionNameById && !empty($regionNameById)) {
			$regionName = $this->ReplaceDiacritics($regionNameById['default_name']);
		}
		$address = [
			'country'  => $this->ReplaceDiacritics($quote->getShippingAddress()->getCountryId()),
			'state'    => $this->ReplaceDiacritics($regionName),
			'city'     => $this->ReplaceDiacritics($quote->getShippingAddress()->getCity()),
			'street'   => $this->ReplaceDiacritics(implode(', ', $quote->getShippingAddress()->getStreet())),
			'postcode' => $quote->getShippingAddress()->getPostcode(),
			'email'    => $quote->getShippingAddress()->getEmail(),
			'phone'    => $quote->getShippingAddress()->getTelephone(),
		];
		$countryData = $this->GetCountryByID($address['country']);
		// ====================================================
		if ($address['country'] == 'RO' || $address['country'] == 'BG') {
			if ($apiSettings['clientContracts'] != '' && $apiSettings['clientContracts'] != '0') {
				$parameters['data']['sender']['clientId'] = (float) $apiSettings['clientContracts'];
			}
			if ($apiSettings['officeLocations'] != '' && $apiSettings['officeLocations'] != '0') {
				$parameters['data']['sender']['dropoffOfficeId'] = (float) $apiSettings['officeLocations'];
			}
			$shippingAddress = $this->DPD_GetSessionConfirmation('confirmation');
			if ($shippingAddress && !empty($shippingAddress)) {
				$shippingMethod  = $shippingAddress['method'];
				$shippingOffice  = $shippingAddress['pickup'];
			}
			if (isset($shippingAddress) && !empty($shippingAddress)) {
				if ($shippingMethod && !empty($shippingMethod) && $shippingMethod == 'pickup') {
					$parameters['data']['recipient']['privatePerson'] = false;
					$parameters['data']['recipient']['pickupOfficeId'] = $shippingOffice;
				} else {
					$parameters['data']['recipient'] = [
						'addressLocation' => [
							'countryId' => (int) $countryData['id'],
						],
						'privatePerson'   => false
					];
					$checkCityId = $this->GetCityByName($address['country'], $address['state'], $address['city']);
					if ($checkCityId && isset($checkCityId['id']) && !empty($checkCityId['id'])) {
						$parameters['data']['recipient']['addressLocation']['siteId'] = (int) $checkCityId['id'];
					} else {
						$parameters['data']['recipient']['addressLocation']['siteName'] = (string) $address['city'];
						if (array_key_exists('postCodeFormats', $countryData) && !empty($countryData['postCodeFormats']) && is_array($countryData['postCodeFormats'])) {
							$parameters['data']['recipient']['addressLocation']['postCode'] = (string) $address['postcode'];
						}
					}
				}
			} else {
				$parameters['data']['recipient'] = [
					'addressLocation' => [
						'countryId' => (int) $countryData['id'],
					],
					'privatePerson'   => true
				];
				$checkCityId = $this->GetCityByName($address['country'], $address['state'], $address['city']);
				if ($checkCityId && isset($checkCityId['id']) && !empty($checkCityId['id'])) {
					$parameters['data']['recipient']['addressLocation']['siteId'] = (int) $checkCityId['id'];
				} else {
					$parameters['data']['recipient']['addressLocation']['siteName'] = (string) $address['city'];
					if (array_key_exists('postCodeFormats', $countryData) && !empty($countryData['postCodeFormats']) && is_array($countryData['postCodeFormats'])) {
						$parameters['data']['recipient']['addressLocation']['postCode'] = (string) $address['postcode'];
					}
				}
			}
		} else {
			$parameters['data']['recipient'] = [
				'addressLocation' => [
					'countryId' => (int) $countryData['id'],
					'siteName'  => (string) $address['city']
				],
				'privatePerson'   => true
			];
			if (array_key_exists('postCodeFormats', $countryData) && !empty($countryData['postCodeFormats']) && is_array($countryData['postCodeFormats'])) {
				$parameters['data']['recipient']['addressLocation']['postCode'] = (string) $address['postcode'];
			}
		}
		if ($serviceId && $includeShipping) {
			$obj = ObjectManager::getInstance();
			$store = $obj->get('Magento\Store\Model\StoreManagerInterface');
			$currency = $store->getStore()->getBaseCurrency()->getCode();
			$parameters['data']['service']['serviceIds'] = array($serviceId);
			$parameters['data']['payment']['courierServicePayer'] = 'SENDER';
			$parameters['data']['service']['additionalServices']['cod'] = [
				'currencyCode'         => $currency,
				'processingType'       => 'CASH',
				'amount'               => (float) number_format((float) $totalPrice, 2),
				'includeShippingPrice' => true
			];
		}
		if ($apiSettings['senderPayerInsurance'] === 'yes') {
			$parameters['data']['service']['additionalServices']['declaredValue']['amount'] = number_format((float) $totalPrice, 2);
		}
		$parameters['data']['recipient']['phone1']['number'] = (string) $address['phone'];
		$parameters['data']['recipient']['email'] = (string) $address['email'];
		$requestResponse = $this->ApiRequest($parameters);
		return $requestResponse;
	}
	// ========================================================================================
	// DPD SESSIONS
	// ========================================================================================
	public function DPD_SetSessionConfirmation($data, $type)
	{
		$obj = ObjectManager::getInstance();
		$session = $obj->create('\DpdRo\Shipping\Controller\Api\Session');
		return $session->setSession($data, $type);
	}
	public function DPD_GetSessionConfirmation($type)
	{
		$obj = ObjectManager::getInstance();
		$session = $obj->create('\DpdRo\Shipping\Controller\Api\Session');
		return $session->getSession($type);
	}
	// ========================================================================================
	// DPD ORDER COMPLETE
	// ========================================================================================
	public function DPD_AddSettings($data)
	{
		$tableName = $this->DB_GetTable('dpdro_order_settings');
		$apiSettings = $this->Settings();
		$orderID          = (string) $data['orderID'];
		$shippingTax      = (string) $data['shippingTax'];
		$shippingTaxRate  = (string) $data['shippingTaxRate'];
		$courierService   = (string) $apiSettings['payerCourier'];
		$declaredValue    = (string) $apiSettings['senderPayerInsurance'];
		$includeShipping  = (string) $apiSettings['senderPayerIncludeShipping'];
		$query = "
			INSERT INTO 
				`{$tableName}` 
			SET 
				`orderID`         = '{$orderID}', 
				`shippingTax`     = '{$shippingTax}', 
				`shippingTaxRate` = '{$shippingTaxRate}', 
				`courierService`  = '{$courierService}', 
				`declaredValue`   = '{$declaredValue}', 
				`includeShipping` = '{$includeShipping}',
				`created`         = NOW()
		";
		$this->DB_Query($query);
	}
	public function DPD_AddAddress($data)
	{
		$tableName = $this->DB_GetTable('dpdro_order_address');
		$orderID          = (string) $data['orderID'];
		$address          = (string) $data['address'];
		$addressCityID    = (string) $data['addressCityID'];
		$addressCityName  = (string) $data['addressCityName'];
		$method           = (string) $data['method'];
		$officeID         = (string) $data['officeID'];
		$query = "
			INSERT INTO 
				`{$tableName}` 
			SET 
				`orderID`           = '{$orderID}', 
				`address`           = '{$address}', 
				`addressCityID`     = '{$addressCityID}', 
				`addressCityName`   = '{$addressCityName}', 
				`addressStreetID`   = '', 
				`addressStreetType` = '',
				`addressStreetName` = '',
				`addressNumber`     = '',
				`addressBlock`      = '',
				`addressApartment`  = '',
				`officeID`          = '{$officeID}',
				`officeName`        = '',
				`method`            = '{$method}',
				`status`            = '',
				`created`           = NOW()
		";
		$this->DB_Query($query);
	}
	// ========================================================================================
	// DPD DATA
	// ========================================================================================
	public function DPD_OrderAddressValidation($orderID)
	{
		$response = [
			'error' => true
		];
		$orderAddress = $this->Magento_GetOrderAdress($orderID);
		if ($orderAddress['country'] == 'RO' || $orderAddress['country'] == 'BG') {
			$normalizedAddress = $this->DPD_GetAddressNormalizedByOrderID($orderID);
			if (isset($normalizedAddress) && !empty($normalizedAddress)) {
				if (isset($normalizedAddress['method']) && !empty($normalizedAddress['method']) && $normalizedAddress['method'] !== 'pickup') {
					$parameters = [
						'api' => 'validation/address',
						'method' => 'POST',
						'data' => [
							'address' => []
						]
					];
					$countryData = $this->GetCountryByID($orderAddress['country']);
					if ($countryData) {
						$parameters['data']['address']['countryId'] = $countryData['id'];
						if (array_key_exists('postCodeFormats', $countryData) && !empty($countryData['postCodeFormats']) && is_array($countryData['postCodeFormats'])) {
							if ($orderAddress['postcode'] && !empty($orderAddress['postcode'])) {
								$parameters['data']['address']['postCode'] = trim($orderAddress['postcode']);
							}
						}
					}
					$parameters['data']['address']['siteName'] = $this->ReplaceDiacritics($normalizedAddress['addressCityName']);
					$parameters['data']['address']['streetId'] = $normalizedAddress['addressStreetID'];
					$parameters['data']['address']['streetNo'] = 0;
					if (isset($normalizedAddress['addressNumber']) &&  !empty($normalizedAddress['addressNumber'])) {
						$parameters['data']['address']['streetNo'] = (int) $normalizedAddress['addressNumber'];
					}
					$response['error'] = false;
					$fullAddress = array(
						$countryData['name'],
						$orderAddress['state'],
						$orderAddress['city'],
						$orderAddress['street']
					);
					if ($normalizedAddress['address'] && !empty($normalizedAddress['address'])) {
						$fullAddress = array(
							$countryData['name'],
							$orderAddress['state'],
							$orderAddress['city'],
							$normalizedAddress['address']
						);
					}
					$response['address'] = [
						'country'     => $countryData['name'],
						'countryID'   => $countryData['id'],
						'state'       => $orderAddress['state'],
						'city'        => $this->ReplaceDiacritics($normalizedAddress['addressCityName']),
						'cityID'      => $normalizedAddress['addressCityID'],
						'streetType'  => $normalizedAddress['addressStreetType'],
						'streetName'  => $this->ReplaceDiacritics($normalizedAddress['addressStreetName']),
						'number'      => $normalizedAddress['addressNumber'],
						'block'       => $normalizedAddress['addressBlock'],
						'apartment'   => $normalizedAddress['addressApartment'],
						'scale'       => $normalizedAddress['addressScale'],
						'floor'       => $normalizedAddress['addressFloor'],
						'postcode'    => $orderAddress['postcode'],
						'fullAddress' => implode(', ', $fullAddress)
					];
					$response['validation'] = $this->ApiRequest($parameters, true);
					if (isset($response['validation']['error'])) {
						$parametersStreets = [
							'api'    => 'location/street',
							'method' => 'POST',
							'data'   => [
								'countryId' => $countryData['name'],
								'siteId'    => $normalizedAddress['addressCityID'],
								'name'      => '',
							]
						];
						$validationStreets = $this->ApiRequest($parametersStreets);
						if (isset($validationStreets) && !empty($validationStreets)) {
							if (isset($validationStreets['streets']) && !empty($validationStreets['streets'])) {
								$response['streets'] = $validationStreets['streets'];
							}
						}
					}
				}
			}
		}
		return $response;
	}
	public function DPD_OrderAddressStatus($orderID)
	{
		$address = $this->DPD_GetAddressNormalizedByOrderID($orderID);
		if (isset($address['status'])) {
			return $address['status'];
		}
		return false;
	}
	public function DPD_PrepareParcels($products)
	{
		$apiSettings = $this->Settings();
		$productsWeight = [];
		if ($products) {
			foreach ($products as $product) {
				for ($i = 0; $i < (int) $product['quantity']; $i++) {
					array_push($productsWeight, $product['weight']);
				}
			}
		}
		$groupWeights = [];
		sort($productsWeight);
		if ($productsWeight && is_array($productsWeight)  && !empty($productsWeight)) {
			$countGroupsWeight = 0;
			$maxParcelsWeight = (float) $apiSettings['maxParcelWeight'];
			foreach ($productsWeight as $weight) {
				if (!isset($groupWeights[$countGroupsWeight])) {
					$groupWeights[$countGroupsWeight] = 0;
				}
				if ((float) $weight + $groupWeights[$countGroupsWeight] > $maxParcelsWeight) {
					$countGroupsWeight++;
					$groupWeights[$countGroupsWeight] = $weight;
				} else {
					$groupWeights[$countGroupsWeight] += $weight;
				}
			}
		}
		$data = [];
		if ($groupWeights && is_array($groupWeights)  && !empty($groupWeights)) {
			$groupIndex = 0;
			$groupSeqNo = 1;
			foreach ($groupWeights as $groupWeight) {
				if ($groupWeight > 0) {
					$data[$groupIndex] = [
						'seqNo'  => (string) $groupSeqNo,
						'weight' => (float) $groupWeight
					];
					$groupIndex++;
					$groupSeqNo++;
				}
			}
		}
		return $data;
	}
	// ========================================================================================
	// MAGENTO DATA
	// ========================================================================================
	public function Magento_GetOrders($page = 1)
	{
		$tableName = $this->DB_GetTable('sales_order');
		$limitPage = 0;
		$limitRows = 50;
		if ($page > 1) {
			$limitPage = (int) $page * $limitRows;
		}
		$query = "
			SELECT *
			FROM 
				`{$tableName}`
			WHERE 
				`shipping_method` LIKE 'dpdro_shipping_dpd_%' AND
				`status` != 'complete' AND
				`status` != 'canceled' AND
				`status` != 'closed'
			ORDER BY `created_at` DESC
			LIMIT 
				{$limitPage}, {$limitRows}
		";
		$request = $this->DB_Fetch($query);
		if ($request && !empty($request)) {
			$response = [];
			foreach ($request as $order) {
				$response[] = $order['entity_id'];
			}
			return $response;
		}
		return false;
	}
	public function Magento_GetOrdersPagination()
	{
		$tableName = $this->DB_GetTable('sales_order');
		$query = "
			SELECT COUNT(*) 
			FROM 
				`{$tableName}`
			WHERE 
				`shipping_method` LIKE 'dpdro_shipping_dpd_%' AND
				`status` != 'complete' AND
				`status` != 'canceled' AND
				`status` != 'closed'
		";
		$response = $this->DB_Fetch($query);
		if ($response && !empty($response)) {
			if (isset($response[0]['COUNT(*)'])) {
				return (int) $response[0]['COUNT(*)'];
			}
		}
		return false;
	}
	public function Magento_GetOrdersData($page = 1)
	{
		$obj = ObjectManager::getInstance();
		$store = $obj->create('\Magento\Store\Model\StoreManagerInterface');
		$orders = $this->Magento_GetOrders($page);
		$data = [];
		// ====================================================
		// Courier
		$courierRequests = $this->DPD_GetAllCourierRequests();
		$courierRequestsIDS = array();
		if ($courierRequests) {
			foreach ($courierRequests as $courierRequest) {
				if ($courierRequest && !empty($courierRequest) && array_key_exists('ordersIDS', $courierRequest) && !empty($courierRequest['ordersIDS'])) {
					$list = json_decode(str_replace('"', '', $courierRequest['ordersIDS']));
					if ($list) {
						foreach ($list as $ID) {
							if (!in_array($ID, $courierRequestsIDS)) {
								array_push($courierRequestsIDS, (string) $ID);
							}
						}
					}
				}
			}
		}
		$data['orders'] = [];
		$data['ordersLink'] =  $store->getStore()->getUrl('sales/order/index');
		$data['ordersTotal'] = 0;
		$data['requestCourier'] = false;
		$data['ordersPagination'] = false;
		if ($orders && !empty($orders)) {
			$data['ordersTotal'] = count($orders);
			if (count($orders) > 10) {
				$data['ordersPagination'] = true;
			}
		}
		if ($orders && !empty($orders)) {
			foreach ($orders as $orderID) {
				$order = $obj->create('Magento\Sales\Model\Order')->load($orderID);
				$orderIncrementId = $order->getIncrementId();
				$orderDate = date('Y-m-d', strtotime($order->getCreatedAt()));
				$orderState = $order->getState();
				$orderStatus = $order->getStatus();
				$orderTotal = (float) $order->getGrandTotal();
				$orderCurrency = $order->getOrderCurrencyCode();
				$orderPreview =  $store->getStore()->getUrl('sales/order/view', array('order_id' => $orderID));
				// ====================================================
				// Products
				$orderProducts = $this->Magento_GetOrderData($orderID);
				// ====================================================
				// Shipment
				$shipmentData = $this->DPD_GetShipmentByOrderID($orderID);
				$shipmentParcels = [];
				$shipmentDataParcels = false;
				$shipmentDataReturns = false;
				if ($shipmentData && !empty($shipmentData)) {
					$shipmentDataParcels = json_decode($shipmentData['parcels']);
					$shipmentDataReturns = json_decode($shipmentData['shipmentData']);
				}
				if ($shipmentDataParcels) {
					foreach ($shipmentDataParcels as $parcel) {
						$parcelData = [
							'parcel' => [
								'id' => $parcel->id
							]
						];
						array_push($shipmentParcels, $parcelData);
					}
				}
				$shipmentPrintLabels = $store->getStore()->getUrl('dpd/printing/index') . '?print=labels&orderID=' . $orderID;
				$shipmentPrintVoucher = false;
				if ($shipmentDataReturns && $shipmentDataReturns->shipment_has_voucher === 'true') {
					$shipmentPrintVoucher = $store->getStore()->getUrl('dpd/printing/index') . '?print=voucher&orderID=' . $orderID;
				}
				// ====================================================
				// Address
				$orderAddress = $this->Magento_GetOrderAdress($orderID);
				$orderAddressValidation = $this->DPD_OrderAddressValidation($orderID);
				$orderAddressStatus = $this->DPD_OrderAddressStatus($orderID);
				// ====================================================
				// Shipping Method
				$shippingMethodName = str_replace('DPD RO Shipping - ', '', $order->getShippingDescription());
				$shippingMethodCode = str_replace('dpdro_shipping_dpd_', '', $order->getShippingMethod());
				// ====================================================
				// Payment Method
				$paymentMethodName = $order->getPayment()->getMethodInstance()->getTitle();
				$paymentMethodCode = $order->getPayment()->getMethodInstance()->getCode();
				// ====================================================
				// Settings
				$orderSettings = $this->DPD_GetSettingsByOrderID($orderID);
				// ====================================================
				// Company
				$orderCompany = $order->getShippingAddress()->getCompany();
				// ====================================================
				$data['orders'][] = array(
					'id'                   => $orderID,
					'orderIncrementId'     => $orderIncrementId,
					'orderCompany'         => $orderCompany,
					'orderDate'            => $orderDate,
					'orderTotal'           => $orderTotal,
					'orderState'           => $orderState,
					'orderStatus'          => $orderStatus,
					'orderCurrency'        => $orderCurrency,
					'orderProducts'        => $orderProducts,
					'orderPreview'		   => $orderPreview,
					'orderSettings'        => $orderSettings,
					'orderCourier'         => ($shipmentData && in_array($shipmentData['shipmentID'], $courierRequestsIDS)) ? true : false,
					'address'              => $orderAddress,
					'addressStatus'        => $orderAddressStatus,
					'addressValidation'    => $orderAddressValidation,
					'shipmentData'         => $shipmentData,
					'shipmentParcels'      => json_encode($shipmentParcels),
					'shipmentPrintLabels'  => $shipmentPrintLabels,
					'shipmentPrintVoucher' => $shipmentPrintVoucher,
					'shippingName'         => $shippingMethodName,
					'shippingCode'         => $shippingMethodCode,
					'paymentName'          => $paymentMethodName,
					'paymentCode'          => $paymentMethodCode,
				);
				if (!$data['requestCourier']) {
					$data['requestCourier'] = !empty($shipmentData);
					if ($data['requestCourier']) {
						if (!in_array($shipmentData['shipmentID'], $courierRequestsIDS)) {
							$data['requestCourier'] = true;
						} else {
							$data['requestCourier'] = false;
						}
					}
				}
			}
		}
		return $data;
	}
	public function Magento_GetCartAdress($salesAddress = false)
	{
		if ($salesAddress) {
			if (isset($salesAddress['shipping_address'])) {
				$salesAddressData = $salesAddress['shipping_address'];
			} else {
				$salesAddressData = $salesAddress['billing_address'];
			}
			$region = $this->Magento_GetRegionById($salesAddressData['region_id']);
			$regionName = $this->ReplaceDiacritics($salesAddressData['region']);
			if ($region) {
				$regionName = $this->ReplaceDiacritics($region['default_name']);
			}
			$data = [
				'country'  => $this->ReplaceDiacritics($salesAddressData['country_id']),
				'state'    => $this->ReplaceDiacritics($regionName),
				'city'     => $this->ReplaceDiacritics($salesAddressData['city']),
				'street'   => $this->ReplaceDiacritics(implode(', ', $salesAddressData['street'])),
				'postcode' => $salesAddressData['postcode'],
				'email'    => '',
				'phone'    => $salesAddressData['telephone'],
			];
		} else {
			$obj = ObjectManager::getInstance();
			$customer = $obj->get('Magento\Customer\Model\Session');
			if ($customer->isLoggedIn()) {
			}
			$session = $obj->create('\Magento\Checkout\Model\Session');
			$sessionAddress = $session->getQuote()->getShippingAddress();
			$data = [
				'country'  => $this->ReplaceDiacritics($sessionAddress->getCountry()),
				'state'    => $this->ReplaceDiacritics($sessionAddress->getRegion()),
				'city'     => $this->ReplaceDiacritics($sessionAddress->getCity()),
				'street'   => $this->ReplaceDiacritics(implode(', ', $sessionAddress->getStreet())),
				'postcode' => $sessionAddress->getPostcode(),
				'email'    => $sessionAddress->getEmail(),
				'phone'    => $sessionAddress->getTelephone(),
			];
		}
		return $data;
	}
	public function Magento_GetCartData($adminOrder = false)
	{
		$obj = ObjectManager::getInstance();
		if ($adminOrder) {
			$cart = $obj->create('\Magento\Sales\Model\AdminOrder\Create');
		} else {
			$cart = $obj->create('\Magento\Checkout\Model\Cart');
		}
		$weight = $obj->create('\Magento\Framework\App\Config\ScopeConfigInterface');
		$weightUnit = $weight->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE);
		$weightUnits = [
			'kgs' => 1,
			'kg'  => 1,
			'lbs' => 2.2046,
			'lb'  => 2.2046,
			'gms' => 1000,
			'g'   => 1000,
		];
		$data = [
			'products'      => [],
			'productsTotal' => 0,
			'total'         => number_format($cart->getQuote()->getGrandTotal(), 2),
		];
		$cartItems = $cart->getQuote()->getAllVisibleItems();
		foreach ($cartItems as $cartItem) {
			$data['products'][] = [
				'id'          => $cartItem->getProductId(),
				'name'        => $cartItem->getName(),
				'price'       => (float) $this->Magento_CurrencyConvert($cartItem->getPrice()),
				'weight'      => (float) $cartItem->getWeight() * (float) $weightUnits[$weightUnit],
				'weightTotal' => ((float) $cartItem->getWeight() * (float) $weightUnits[$weightUnit]) * (int) $cartItem->getQty(),
				'quantity'    => $cartItem->getQty(),
			];
			$data['productsTotal'] = (float) $data['productsTotal'] + ((float) $this->Magento_CurrencyConvert($cartItem->getPrice()) * (int) $cartItem->getQty());
		}
		$data['productsTotal'] = number_format($data['productsTotal'], 2);
		return $data;
	}
	public function Magento_GetOrderAdress($orderID)
	{
		$obj = ObjectManager::getInstance();
		$order = $obj->create('Magento\Sales\Model\Order')->load($orderID);
		$orderAddress = $order->getShippingAddress();
		$data = [
			'country'  => $this->ReplaceDiacritics($orderAddress->getCountryId()),
			'state'    => $this->ReplaceDiacritics($orderAddress->getRegion()),
			'city'     => $this->ReplaceDiacritics($orderAddress->getCity()),
			'street'   => $this->ReplaceDiacritics(implode(', ', $orderAddress->getStreet())),
			'postcode' => $orderAddress->getPostcode(),
			'email'    => $orderAddress->getEmail(),
			'phone'    => $orderAddress->getTelephone(),
		];
		return $data;
	}
	public function Magento_GetOrderData($orderID)
	{
		$obj = ObjectManager::getInstance();
		$order = $obj->create('Magento\Sales\Model\Order')->load($orderID);
		$weight = $obj->create('\Magento\Framework\App\Config\ScopeConfigInterface');
		$weightUnit = $weight->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE);
		$weightUnits = [
			'kgs' => 1,
			'kg'  => 1,
			'lbs' => 2.2046,
			'lb'  => 2.2046,
			'gms' => 1000,
			'g'   => 1000,
		];
		$data = [
			'products'      => [],
			'productsTotal' => 0,
			'total'         => number_format($order->getGrandTotal(), 2),
		];
		$orderItems = $order->getAllVisibleItems();
		foreach ($orderItems as $orderItem) {
			$data['products'][] = [
				'id'          => $orderItem->getProductId(),
				'name'        => $orderItem->getName(),
				'price'       => (float) $this->Magento_CurrencyConvert($orderItem->getPrice()),
				'weight'      => (float) $orderItem->getWeight() * (float) $weightUnits[$weightUnit],
				'weightTotal' => ((float) $orderItem->getWeight() * (float) $weightUnits[$weightUnit]) * (int) $orderItem->getQtyOrdered(),
				'quantity'    => $orderItem->getQtyOrdered(),
			];
			$data['productsTotal'] = (float) $data['productsTotal'] + ((float) $this->Magento_CurrencyConvert($orderItem->getPrice()) * (int) $orderItem->getQtyOrdered());
		}
		$data['productsTotal'] = number_format($data['productsTotal'], 2);
		return $data;
	}
	public function Magento_CurrencyData()
	{
		$obj = ObjectManager::getInstance();
		$store = $obj->create('\Magento\Store\Model\StoreManagerInterface');
		$data = [
			'code' => $store->getStore()->getCurrentCurrencyCode(),
			'rate' => (float) $store->getStore()->getCurrentCurrencyRate(),
		];
		return $data;
	}
	public function Magento_CurrencyConvert($price, $serviceCurrency = false)
	{
		if (!$price) {
			return false;
		}
		$obj = ObjectManager::getInstance();
		$store = $obj->get('Magento\Store\Model\StoreManagerInterface');
		$currency = $obj->get('Magento\Directory\Model\CurrencyFactory');
		$currencyCodeTo = $store->getStore()->getCurrentCurrency()->getCode();
		$currencyCodeFrom = $store->getStore()->getBaseCurrency()->getCode();
		if ($serviceCurrency) {
			$checkServiceCurrency = $this->Magento_AvailableCurrencyCodes();
			if (isset($checkServiceCurrency[$serviceCurrency])) {
				$currencyCodeTo = $serviceCurrency;
			}
		}
		$currencyRate = $currency->create()->load($currencyCodeTo)->getAnyRate($currencyCodeFrom);
		$price = (float) $price * (float) $currencyRate;
		return (float) $price;
	}
	public function Magento_AvailableCurrencyCodes()
	{
		$obj = ObjectManager::getInstance();
		$store = $obj->create('Magento\Store\Model\StoreManager')->getStore();
		$codes = $store->getAvailableCurrencyCodes(true);
		$data = [];
		if (is_array($codes) && count($codes) >= 1) {
			foreach ($codes as $code) {
				$allCurrencies = $obj->create('Magento\Framework\Locale\Bundle\CurrencyBundle')->get(
					$obj->create('Magento\Framework\Locale\ResolverInterface')->getLocale()
				)['Currencies'];
				$data[$code]['title'] = $allCurrencies[$code][1] ?: $code;
				$data[$code]['symbol'] = $obj->create('Magento\Framework\Locale\CurrencyInterface')->getCurrency($code)->getSymbol();
			}
		}
		return $data;
	}
	public function Magento_GetRegionsRO()
	{
		$tableName = $this->DB_GetTable('directory_country_region');
		$query = "
			SELECT *
			FROM 
				`{$tableName}`
			WHERE
				`country_id` = 'RO'
		";
		$response = $this->DB_Fetch($query);
		if ($response && !empty($response)) {
			return $response;
		}
		return false;
	}
	public function Magento_GetRegionsBG()
	{
		$tableName = $this->DB_GetTable('directory_country_region');
		$query = "
			SELECT *
			FROM 
				`{$tableName}`
			WHERE
				`country_id` = 'BG'
		";
		$response = $this->DB_Fetch($query);
		if ($response && !empty($response)) {
			return $response;
		}
		return false;
	}
	public function Magento_GetRegionByName($country, $name)
	{
		$tableName = $this->DB_GetTable('directory_country_region');
		$countryID = (int) $country;
		$query = "
			SELECT *
			FROM 
				`{$tableName}`
			WHERE
				`country_id` = {$countryID} AND
				`default_name` = '{$name}'
		";
		$response = $this->DB_Fetch($query);
		if ($response && !empty($response)) {
			return $response[0];
		}
		return false;
	}
	public function Magento_GetRegionById($id)
	{
		$tableName = $this->DB_GetTable('directory_country_region');
		$regionID = (int) $id;
		$query = "
			SELECT *
			FROM 
				`{$tableName}`
			WHERE
				`region_id` = {$regionID}
		";
		$response = $this->DB_Fetch($query);
		if ($response && !empty($response)) {
			return $response[0];
		}
		return false;
	}
	public function Magento_GetCustomerAddress($id)
	{
		$tableName = $this->DB_GetTable('customer_address_entity');
		$customerID = (int) $id;
		$query = "
			SELECT *
			FROM 
				`{$tableName}`
			WHERE
				`entity_id` = {$customerID}
		";
		$response = $this->DB_Fetch($query);
		if ($response && !empty($response)) {
			return $response[0];
		}
		return false;
	}
}
