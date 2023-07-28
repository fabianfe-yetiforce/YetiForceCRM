<?php
/**
 * Api Government of French Republic file.
 *
 * @see https://api.gouv.fr/les-api/api-entreprise
 * @see https://api.gouv.fr/les-api/api-recherche-entreprises
 * @see https://api.gouv.fr/documentation/api-recherche-entreprises
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
 * Api Government of French Republic class.
 */
class FrEnterpriseGouv extends Base
{
	/** @var int Number of items returned */
	public const LIMIT = 4;

	/** {@inheritdoc} */
	public $allowedModules = [];

	/** {@inheritdoc} */
	public string $icon = 'yfi-entreprise-gouv-fr';

	/** {@inheritdoc} */
	public string $label = 'LBL_FR_ENTERPRISE_GOUV';

	/** {@inheritdoc} */
	public string $displayType = 'FillFields';

	/** {@inheritdoc} */
	public string $description = 'LBL_FR_ENTERPRISE_GOUV_DESC';

	/** {@inheritdoc} */
	public string $docUrl = 'https://api.gouv.fr/les-api/api-entreprise/';

	/** @var string Server address */
	private string $url = 'https://recherche-entreprises.api.gouv.fr/';

	/** {@inheritdoc} */
	protected array $fields = [
		'companyName' => [
			'labelModule' => '_Base',
			'label' => 'Account name',
		],
		'sicCode' => [
			'labelModule' => '_Base',
			'label' => 'SIC code',
		],
		'vatNumber' => [
			'labelModule' => '_Base',
			'label' => 'VAT',
		],
	];

	/** {@inheritdoc} */
	protected array $modulesFieldsMap = [
		'Accounts' => [
			'companyName' => 'accountname',
			'sicCode' => 'siccode',
			'vatNumber' => 'vat_id'
		],
		'Leads' => [
			'companyName' => 'company',
			'vatNumber' => 'vat_id'
		],
		'Vendors' => [
			'companyName' => 'vendorname',
			'vatNumber' => 'vat_id'
		],
		'Partners' => [
			'companyName' => 'subject',
			'vatNumber' => 'vat_id'
		],
		'Competition' => [
			'companyName' => 'subject',
			'vatNumber' => 'vat_id'
		],
	];

	/** {@inheritdoc} */
	public array $formFieldsToRecordMap = [
		'Accounts' => [
			'nom_complet' => 'accountname',
			'siren' => 'vat_id',
			'siegeActivite_principale' => 'siccode',
			'activite_principale' => 'siccode',
			'siegeSiret' => 'registration_number_1',
			'siegeAdresse_complete_secondaire' => 'buildingnumbera',
			'siegeAdresse_complete' => 'addresslevel8a',
			'siegeLibelle_commune' => 'addresslevel4a'
		],
		'Leads' => [
			'nom_complet' => 'company',
			'siren' => 'vat_id',
			'siegeSiret' => 'registration_number_1',
			'siegeAdresse_complete_secondaire' => 'buildingnumbera',
			'siegeAdresse_complete' => 'addresslevel8a',
			'siegeLibelle_commune' => 'addresslevel4a'
		],
		'Partners' => [
			'nom_complet' => 'subject',
			'siren' => 'vat_id',
			'siegeAdresse_complete_secondaire' => 'buildingnumbera',
			'siegeAdresse_complete' => 'addresslevel8a',
			'siegeLibelle_commune' => 'addresslevel4a'
		],
		'Vendors' => [
			'nom_complet' => 'vendorname',
			'siren' => 'vat_id',
			'siegeSiret' => 'registration_number_1',
			'siegeAdresse_complete_secondaire' => 'buildingnumbera',
			'siegeAdresse_complete' => 'addresslevel8a',
			'siegeLibelle_commune' => 'addresslevel4a'
		],
		'Competition' => [
			'nom_complet' => 'subject',
			'siren' => 'vat_id',
			'siegeAdresse_complete_secondaire' => 'buildingnumbera',
			'siegeAdresse_complete' => 'addresslevel8a',
			'siegeLibelle_commune' => 'addresslevel4a'
		],
	];

	/** {@inheritdoc} */
	public function search(): array
	{
		if (!$this->isActive()) {
			return [];
		}
		$vatNumber = str_replace([' ', ',', '.', '-'], '', $this->request->getByType('vatNumber', 'Text'));
		$sicCode = str_replace([' ', ',', '.', '-'], '', $this->request->getByType('sicCode', 'Text'));
		$companyName = $this->request->getByType('companyName', 'Text');
		$query = [];

		if (empty($vatNumber) && empty($sicCode) && empty($companyName)) {
			return [];
		}
		if (!empty($vatNumber)) {
			$query['q'] = $vatNumber;
		} elseif (!empty($companyName) && empty($vatNumber)) {
			$query['q'] = $companyName;
		}
		$query['per_page'] = self::LIMIT;
		if (!empty($sicCode)) {
			$query['activite_principale'] = $sicCode;
		}

		$this->getDataFromApi($query);
		$this->loadData();
		return $this->response;
	}

	/**
	 * Function fetching company data by params.
	 *
	 * @param array $query
	 *
	 * @return void
	 */
	private function getDataFromApi(array $query): void
	{
		$response = [];
		try {
			$response = \App\RequestHttp::getClient()->get($this->url . 'search?' . http_build_query($query));
			$data = isset($response) ? \App\Json::decode($response->getBody()->getContents()) : [];
		} catch (\GuzzleHttp\Exception\GuzzleException $e) {
			\App\Log::warning($e->getMessage(), 'RecordCollectors');
			$this->response['error'] = $this->getTranslationResponseMessage($e->getResponse()->getReasonPhrase());
		}
		if (empty($data)) {
			return;
		}

		foreach ($data['results'] as $key => $result) {
			$this->data[$key] = $this->parseData($result);
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

	/**
	 * @param string $message
	 * @return string
	 */
	protected function getTranslationResponseMessage(string $message): string
	{
		switch ($message) {
			case 'Not Found':
				$translatedMessage = \App\Language::translate('LBL_NO_FOUND_RECORD', 'Other.RecordCollector');
				break;
			case 'Bad Request':
				$translatedMessage = \App\Language::translate('LBL_FR_GOUV_BAD_REQUEST', 'Other.RecordCollector');
				break;
			default :
				$translatedMessage = $message;
				break;
		}

		return $translatedMessage;
	}
}
