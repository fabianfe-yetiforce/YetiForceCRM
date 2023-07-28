<?php
/**
 * Zefix Swiss Central Business Name Index API file.
 *
 * @see https://www.zefix.admin.ch/
 * @see https://www.zefix.admin.ch/ZefixPublicREST/
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
 * Zefix Swiss Central Business Name Index API class.
 */
class ChZefix extends Base
{
	/** {@inheritdoc} */
	public $allowedModules = [];

	/** {@inheritdoc} */
	public string $icon = 'yfi-zefix-ch';

	/** {@inheritdoc} */
	public string $label = 'LBL_CH_ZEFIX';

	/** {@inheritdoc} */
	public string $displayType = 'FillFields';

	/** {@inheritdoc} */
	public string $description = 'LBL_CH_ZEFIX_DESC';

	/** {@inheritdoc} */
	public string $docUrl = 'https://www.zefix.admin.ch/';

	/** {@inheritdoc} */
	private $url = 'https://www.zefix.admin.ch/ZefixPublicREST/api/v1/company/';

	/** {@inheritdoc} */
	public array $settingsFields = [
		'username' => ['required' => 1, 'purifyType' => 'Text', 'label' => 'Username'],
		'password' => ['required' => 1, 'purifyType' => 'Text', 'label' => 'Password'],
		'tabid' => ['required' => 0, 'purifyType' => 'Text', 'label' => 'LBL_MODULES'],
	];

	/** @var string Username. */
	private string $username;

	/** @var string Password. */
	private string $password;

	/** {@inheritdoc} */
	protected array $fields = [
		'companyName' => [
			'labelModule' => '_Base',
			'label' => 'Account name',
		],
		'companyId' => [
			'labelModule' => '_Base',
			'label' => 'Registration number 1',
		],
	];

	/** {@inheritdoc} */
	protected array $modulesFieldsMap = [
		'Accounts' => [
			'companyId' => 'registration_number_1',
			'companyName' => 'accountname'
		],
		'Leads' => [
			'companyId' => 'registration_number_1',
			'companyName' => 'company'
		],
		'Vendors' => [
			'companyId' => 'registration_number_1',
			'companyName' => 'vendorname'
		],
		'Competition' => [
			'companyId' => 'registration_number_1',
			'companyName' => 'subject'
		]
	];

	/** {@inheritdoc} */
	public array $formFieldsToRecordMap = [
		'Accounts' => [
			'name' => 'accountname',
			'translation1' => 'accountname',
			'uid' => 'registration_number_1',
			'capitalNominal' => 'annual_revenue',
			'addressHouseNumber' => 'buildingnumbera',
			'addressAddon' => 'localnumbera',
			'addressStreet' => 'addresslevel8a',
			'addressPoBox' => 'poboxa',
			'addressSwissZipCode' => 'addresslevel7a',
			'addressCity' => 'addresslevel5a',
			'canton' => 'addresslevel2a',
			'country' => 'addresslevel1a',
			'purpose' => 'description'
		],
		'Leads' => [
			'name' => 'company',
			'translation1' => 'company',
			'uid' => 'registration_number_1',
			'addressHouseNumber' => 'buildingnumbera',
			'addressAddon' => 'localnumbera',
			'addressStreet' => 'addresslevel8a',
			'addressPoBox' => 'poboxa',
			'addressSwissZipCode' => 'addresslevel7a',
			'addressCity' => 'addresslevel5a',
			'canton' => 'addresslevel2a',
			'country' => 'addresslevel1a',
			'purpose' => 'description'
		],
		'Partners' => [
			'name' => 'subject',
			'translation1' => 'subject',
			'addressHouseNumber' => 'buildingnumbera',
			'addressAddon' => 'localnumbera',
			'addressStreet' => 'addresslevel8a',
			'addressPoBox' => 'poboxa',
			'addressSwissZipCode' => 'addresslevel7a',
			'addressCity' => 'addresslevel5a',
			'canton' => 'addresslevel2a',
			'country' => 'addresslevel1a',
			'purpose' => 'description'
		],
		'Vendors' => [
			'name' => 'vendorname',
			'translation1' => 'vendorname',
			'uid' => 'registration_number_1',
			'addressHouseNumber' => 'buildingnumbera',
			'addressAddon' => 'localnumbera',
			'addressStreet' => 'addresslevel8a',
			'addressPoBox' => 'poboxa',
			'addressSwissZipCode' => 'addresslevel7a',
			'addressCity' => 'addresslevel5a',
			'canton' => 'addresslevel2a',
			'country' => 'addresslevel1a',
			'purpose' => 'description'
		],
		'Competition' => [
			'name' => 'subject',
			'translation1' => 'subject',
			'addressHouseNumber' => 'buildingnumbera',
			'addressAddon' => 'localnumbera',
			'addressStreet' => 'addresslevel8a',
			'addressPoBox' => 'poboxa',
			'addressSwissZipCode' => 'addresslevel7a',
			'addressCity' => 'addresslevel5a',
			'canton' => 'addresslevel2a',
			'country' => 'addresslevel1a',
			'purpose' => 'description'
		],
	];

	/** {@inheritdoc} */
	public function isActive(): bool
	{
		return parent::isActive() && ($params = $this->getParams()) && !empty($params['username'] && !empty($params['password']));
	}

	/** {@inheritdoc} */
	public function search(): array
	{
		$companyId = str_replace([' ', ',', '.', '-'], '', $this->request->getByType('companyId', 'Text'));
		$companyName = $this->request->getByType('companyName', 'Text');
		if (!$this->isActive() || (empty($companyId) && empty($companyName))) {
			return [];
		}
		$this->loadCredentials();
		if (!empty($companyId)) {
			$this->getCompanyById($companyId);
		} elseif (!empty($companyName) && (empty($companyId) || empty($this->data))) {
			$this->getCompaniesByName($companyName);
		} else {
			return [];
		}
		$this->loadData();
		return $this->response;
	}

	/**
	 * Function setup Credentials.
	 *
	 * @return void
	 */
	private function loadCredentials(): void
	{
		if (($params = $this->getParams()) && !empty($params['username'] && !empty($params['password']))) {
			$this->username = $params['username'];
			$this->password = $params['password'];
		}
	}

	/**
	 * Function fetching company data by Company ID.
	 *
	 * @param string $companyId
	 *
	 * @return void
	 */
	private function getCompanyById(string $companyId): void
	{
		try {
			$response = \App\RequestHttp::getClient(['auth' => [$this->username, $this->password]])
				->get($this->url . 'uid/' . $companyId);
			if (200 === $response->getStatusCode()) {
				$this->data = $this->parseData(\App\Json::decode($response->getBody()->getContents())[0] ?? []);
			}
		} catch (\GuzzleHttp\Exception\GuzzleException $e) {
			\App\Log::warning($e->getMessage(), 'RecordCollectors');
			$this->response['error'] = $this->getTranslationResponseMessage($e->getResponse()->getReasonPhrase());
		}
	}

	/**
	 * Function fetching companies data by company name.
	 *
	 * @param string $companyName
	 *
	 * @return void
	 */
	private function getCompaniesByName(string $companyName): void
	{
		try {
			$response = \App\RequestHttp::getClient(['auth' => [$this->username, $this->password]])
				->post($this->url . 'search', ['json' => ['name' => $companyName]]);
			if (200 === $response->getStatusCode()) {
				$response = \App\Json::decode($response->getBody()->getContents());
				$i = 0;
				$data = [];
				foreach ($response as $company) {
					if ($i < 5) {
						$this->data = [];
						$this->getCompanyById($company['uid']);
						$data[$i] = $this->data;
						$this->response['links'][$i] = $this->data['zefixDetailWeb'] ?? '';
						++$i;
					}
				}
				$this->data = $data;
			}
		} catch (\GuzzleHttp\Exception\GuzzleException $e) {
			\App\Log::warning($e->getMessage(), 'RecordCollectors');
			$this->response['error'] = $this->getTranslationResponseMessage($e->getResponse()->getReasonPhrase());
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
		if (empty($data)) {
			return [];
		}
		unset($data['sogcPub']);
		$data['zefixDetailWeb'] = $data['zefixDetailWeb']['en'] ?? $data['cantonalExcerptWeb'] ?? '';
		$data['country'] = 'Switzerland';
		return \App\Utils::flattenKeys($data, 'ucfirst');
	}

	/**
	 * @param string $message
	 * @return string
	 */
	protected function getTranslationResponseMessage(string $message): string
	{
		switch ($message) {
			default :
				$translatedMessage = $message;
				break;
		}

		return $translatedMessage;
	}
}
