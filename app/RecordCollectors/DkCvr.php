<?php
/**
 * The Danish Central Business Register (CVR) file.
 *
 * @see https://cvrtjek.dk/
 * @see https://cvrapi.dk/
 *
 * @package App
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Sławomir Rembiesa <s.rembiesa@yetiforce.com>
 * @author    Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

namespace App\RecordCollectors;

/**
 * The Danish Central Business Register (CVR) class.
 */
class DkCvr extends Base
{
	/** {@inheritdoc} */
	public $allowedModules = [];

	/** {@inheritdoc} */
	public string $icon = 'yfi-cvr-dk';

	/** {@inheritdoc} */
	public string $label = 'LBL_DK_CVR';

	/** {@inheritdoc} */
	public string $displayType = 'FillFields';

	/** {@inheritdoc} */
	public string $description = 'LBL_DK_CVR_DESC';

	/** {@inheritdoc} */
	public string $docUrl = 'https://cvrapi.dk/documentation';

	/** @var string CH sever address */
	public const EXTERNAL_URL = 'https://cvrapi.dk/virksomhed/';

	/** @var string CH sever address */
	private string $url = 'http://cvrapi.dk/api?';

	/** @var string Token key */
	private string $token;

	/** {@inheritdoc} */
	public array $settingsFields = [
		'token' => ['required' => 0, 'purifyType' => 'Text', 'label' => 'LBL_API_KEY_OPTIONAL'],
		'tabid' => ['required' => 0, 'purifyType' => 'Text', 'label' => 'LBL_MODULES'],
	];

	/** {@inheritdoc} */
	protected array $fields = [
		'country' => [
			'labelModule' => '_Base',
			'label' => 'Country',
			'picklistModule' => 'Other.Country',
			'typeofdata' => 'V~M',
			'uitype' => 16,
			'picklistValues' => [
				'no' => 'Norway',
				'dk' => 'Denmark'
			]
		],
		'vatNumber' => [
			'labelModule' => '_Base',
			'label' => 'Vat ID',
		],
		'name' => [
			'labelModule' => '_Base',
			'label' => 'FL_COMPANY_NAME',
		],
		'phone' => [
			'labelModule' => '_Base',
			'label' => 'FL_PHONE',
		],
	];

	/** {@inheritdoc} */
	protected array $modulesFieldsMap = [
		'Accounts' => [
			'vatNumber' => 'vat_id',
		],
		'Leads' => [
			'vatNumber' => 'vat_id',
		],
		'Vendors' => [
			'vatNumber' => 'vat_id',
		],
		'Partners' => [
			'vatNumber' => 'vat_id',
		],
		'Competition' => [
			'vatNumber' => 'vat_id',
		],
	];

	/** {@inheritdoc} */
	public array $formFieldsToRecordMap = [
		'Accounts' => [
			'name' => 'accountname',
			'vat' => 'vat_id',
			'phone' => 'phone',
			'fax' => 'fax',
			'email' => 'email1',
			'industrycode' => 'siccode',
			'companydesc' => 'description',
			'industrydesc' => 'description',
			'productionunits0Address' => 'addresslevel8a',
			'address' => 'addresslevel8a',
			'productionunits0Zipcode' => 'addresslevel7a',
			'zipcode' => 'addresslevel7a',
			'productionunits0City' => 'addresslevel5a',
			'city' => 'addresslevel5a',
			'country' => 'addresslevel1a',
		],
		'Leads' => [
			'name' => 'company',
			'vat' => 'vat_id',
			'phone' => 'phone',
			'fax' => 'fax',
			'email' => 'email',
			'companydesc' => 'description',
			'industrydesc' => 'description',
			'productionunits0Address' => 'addresslevel8a',
			'address' => 'addresslevel8a',
			'productionunits0Zipcode' => 'addresslevel7a',
			'zipcode' => 'addresslevel7a',
			'productionunits0City' => 'addresslevel5a',
			'city' => 'addresslevel5a',
			'country' => 'addresslevel1a',
		],
		'Vendors' => [
			'name' => 'vendorname',
			'vat' => 'vat_id',
			'phone' => 'phone',
			'fax' => 'fax',
			'email' => 'email',
			'companydesc' => 'description',
			'industrydesc' => 'description',
			'productionunits0Address' => 'addresslevel8a',
			'address' => 'addresslevel8a',
			'productionunits0Zipcode' => 'addresslevel7a',
			'zipcode' => 'addresslevel7a',
			'productionunits0City' => 'addresslevel5a',
			'city' => 'addresslevel5a',
			'country' => 'addresslevel1a',
		],
		'Partners' => [
			'name' => 'subject',
			'vat' => 'vat_id',
			'phone' => 'phone',
			'fax' => 'fax',
			'email' => 'email',
			'companydesc' => 'description',
			'industrydesc' => 'description',
			'productionunits0Address' => 'addresslevel8a',
			'address' => 'addresslevel8a',
			'productionunits0Zipcode' => 'addresslevel7a',
			'zipcode' => 'addresslevel7a',
			'productionunits0City' => 'addresslevel5a',
			'city' => 'addresslevel5a',
			'country' => 'addresslevel1a',
		],
		'Competition' => [
			'name' => 'subject',
			'vat' => 'vat_id',
			'phone' => 'phone',
			'fax' => 'fax',
			'email' => 'email',
			'companydesc' => 'description',
			'industrydesc' => 'description',
			'productionunits0Address' => 'addresslevel8a',
			'address' => 'addresslevel8a',
			'productionunits0Zipcode' => 'addresslevel7a',
			'zipcode' => 'addresslevel7a',
			'productionunits0City' => 'addresslevel5a',
			'city' => 'addresslevel5a',
			'country' => 'addresslevel1a',
		]
	];

	protected array $validationMessages = [
		// to fill messages
	];

	/** {@inheritdoc} */
	public function search(): array
	{
		$params = [];
		if ($vatNumber = str_replace([' ', ',', '.', '-'], '', $this->request->getByType('vatNumber', 'Text'))) {
			$params['vat'] = $vatNumber;
		}
		if ($name = $this->request->getByType('name', 'Text')) {
			$params['name'] = $name;
		}
		if ($phone = $this->request->getByType('phone', 'Text')) {
			$params['phone'] = $phone;
		}
		if (empty($params) && !$this->isActive()) {
			return [];
		}
		$params['country'] = $this->request->getByType('country', 'Text');
		$this->setToken();
		$this->getDataFromApi($params);
		$this->loadData();
		return $this->response;
	}

	/**
	 * Function setup token key.
	 *
	 * @return void
	 */
	private function setToken(): void
	{
		if (($params = $this->getParams()) && !empty($params['token'])) {
			$this->token = $params['token'];
		}
	}

	/**
	 * Function fetching company data by Company Number (CVR).
	 *
	 * @param array $params
	 *
	 * @return void
	 */
	private function getDataFromApi(array $params): void
	{
		$params['format'] = 'json';
		if (!empty($this->token)) {
			$params['token'] = $this->token;
		}
		try {
			$response = \App\RequestHttp::getClient()->get($this->url . http_build_query($params));
			$this->data = $this->parseData(\App\Json::decode($response->getBody()->getContents()));
			if (isset($this->data['error'])) {
				$this->data['error'] = $this->getTranslationResponseMessage($this->data['error']);
			}
			if (isset($this->data['name'])) {
				$this->response['links'][0] = self::EXTERNAL_URL . $params['country'] . '/' . urlencode(str_replace(' ', '-', $this->data['name'])) . '/' . urlencode($this->data['vat']);
			}
		} catch (\GuzzleHttp\Exception\GuzzleException $e) {
			\App\Log::warning($e->getMessage(), 'RecordCollectors');
			$this->response['error'] = $this->getTranslationResponseMessage($this->response['error'] ?? $e->getResponse()->getReasonPhrase());
		}

		if ($this->data && empty($this->data['error'])) {
			switch ($params['country']) {
				case 'no':
					$this->data['country'] = 'Norway';
					break;
				case 'dk':
					$this->data['country'] = 'Denmark';
					break;
				default:
					break;
			}
		}
	}

	/**
	 * Function parsing data to fields from API.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	private function parseData(array $data): array
	{
		return \App\Utils::flattenKeys($data, 'ucfirst');
	}
}
