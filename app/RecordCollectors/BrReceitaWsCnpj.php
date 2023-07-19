<?php
/**
 * The Brazilian National Registry of Legal Entities by Receita WS API file.
 *
 * @see https://developers.receitaws.com.br/#/operations/queryCNPJFree
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
 * The Brazilian National Registry of Legal Entities by Receita WS API class.
 */
class BrReceitaWsCnpj extends Base
{
	/** {@inheritdoc} */
	public $allowedModules = ['Accounts', 'Leads', 'Vendors', 'Partners', 'Competition'];

	/** {@inheritdoc} */
	public string $icon = 'yfi-receita-cnpj-br';

	/** {@inheritdoc} */
	public string $label = 'LBL_BR_RECITA_WS_CNPJ';

	/** {@inheritdoc} */
	public string $displayType = 'FillFields';

	/** {@inheritdoc} */
	public string $description = 'LBL_BR_RECITA_WS_CNPJ_DESC';

	/** {@inheritdoc} */
	public string $docUrl = 'https://developers.receitaws.com.br/#/operations/queryCNPJFree';

	/** @var string CNJP sever address */
	private string $url = 'https://receitaws.com.br/v1/cnpj/';

	/** @var string Api key */
	private $apiKey;

	/** {@inheritdoc} */
	public array $settingsFields = [
		'api_key' => ['required' => 1, 'purifyType' => 'Text', 'label' => 'LBL_API_KEY_OPTIONAL'],
		//zrobic arraymerge z class base
		'modules' => ['required' => 0, 'purifyType' => 'Text', 'label' => 'LBL_MODULES'],
	];

	/** {@inheritdoc} */
	protected array $fields = [
		'cnpj' => [
			'labelModule' => 'Other.RecordCollector',
			'label' => 'LBL_BR_RECITA_WS_CNPJ_NUMBER',
			'typeofdata' => 'V~O',
		]
	];

	/** {@inheritdoc} */
	protected array $modulesFieldsMap = [
		'Accounts' => [
			'cnpj' => 'registration_number_1',
		],
		'Leads' => [
			'cnpj' => 'registration_number_1',
		],
		'Vendors' => [
			'cnpj' => 'registration_number_1',
		]
	];

	protected array $validationMessages = [
		// to fill messages
	];

	/** {@inheritdoc} */
	public array $formFieldsToRecordMap = [
		'Accounts' => [
			'nome' => 'accountname',
			'fantasia' => 'accountname',
			'email' => 'email1',
			'telefone' => 'phone',
			'capital_social' => 'annual_revenue',
			'atividade_principal0Code' => 'siccode',
			'cnpj' => 'registration_number_1',
			'numero' => 'buildingnumbera',
			'logradouro' => 'addresslevel8a',
			'cep' => 'addresslevel7a',
			'municipio' => 'addresslevel5a',
			'bairro' => 'addresslevel4a',
		],
		'Leads' => [
			'nome' => 'company',
			'fantasia' => 'company',
			'email' => 'email',
			'telefone' => 'phone',
			'cnpj' => 'registration_number_1',
			'numero' => 'buildingnumbera',
			'logradouro' => 'addresslevel8a',
			'cep' => 'addresslevel7a',
			'municipio' => 'addresslevel5a',
			'bairro' => 'addresslevel4a',
		],
		'Partners' => [
			'nome' => 'subject',
			'fantasia' => 'subject',
			'email' => 'email',
			'numero' => 'buildingnumbera',
			'logradouro' => 'addresslevel8a',
			'cep' => 'addresslevel7a',
			'municipio' => 'addresslevel5a',
			'bairro' => 'addresslevel4a',
		],
		'Vendors' => [
			'nome' => 'vendorname',
			'fantasia' => 'vendorname',
			'email' => 'email',
			'telefone' => 'phone',
			'cnpj' => 'registration_number_1',
			'numero' => 'buildingnumbera',
			'logradouro' => 'addresslevel8a',
			'cep' => 'addresslevel7a',
			'municipio' => 'addresslevel5a',
			'bairro' => 'addresslevel4a',
		],
		'Competition' => [
			'nome' => 'subject',
			'fantasia' => 'subject',
			'email' => 'email',
			'numero' => 'buildingnumbera',
			'logradouro' => 'addresslevel8a',
			'cep' => 'addresslevel7a',
			'municipio' => 'addresslevel5a',
			'bairro' => 'addresslevel4a',
		],
	];

	/** {@inheritdoc} */
	public function search(): array
	{
		$cnpjNumber = str_replace([' ', '/', '.', '-'], '', $this->request->getByType('cnpj', 'Text'));
		if (empty($cnpjNumber) || !$this->isActive()) {
			return [];
		}
		$this->getDataFromApi($cnpjNumber);
		$this->loadData();
		return $this->response;
	}

	/**
	 * Function fetching company data by CNPJ number.
	 *
	 * @param string $cnpj
	 *
	 * @return void
	 */
	private function getDataFromApi(string $cnpjNumber): void
	{
		try {
			$this->setApiKey();
			if ($this->apiKey) {
				$options = [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->apiKey
					]
				];
			}
			$response = \App\RequestHttp::getClient()->get($this->url . $cnpjNumber, $options ?? []);
			$data = $this->parseData(\App\Json::decode($response->getBody()->getContents()));
// na froncie wrzucić require na pole z numerem
			if (isset($data['status']) && 'ERROR' === $data['status']) {
				$this->response['error'] = $this->getTranslationResponseMessage($data['message']);
				unset($this->data['fields']);
			} else {
				$this->data = $data;
			}

		} catch (\GuzzleHttp\Exception\GuzzleException $e) {
			\App\Log::warning($e->getMessage(), 'RecordCollectors');
			$this->response['error'] = $this->getTranslationResponseMessage($this->response['error'] ?? $e->getResponse()->getReasonPhrase());
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
	 * Function setup Api Key.
	 *
	 * @return void
	 */
	private function setApiKey(): void
	{
		if (($params = $this->getParams()) && !empty($params['api_key'])) {
			$this->apiKey = $params['api_key'];
		}
	}

	protected function getTranslationResponseMessage(string $message): string
	{
		switch ($message) {
			case 'CNPJ inválido':
				$translatedMessage = \App\Language::translate('LBL_BR_RECITA_WS_CNPJ_INVALIDATE', 'Other.RecordCollector');
				break;
			case 'Too Many Requests':
				$translatedMessage = \App\Language::translate('LBL_TOO_MANY_REQUESTS', 'Other.RecordCollector');
				break;
			default :
				$translatedMessage = $message;
				break;
		}

		return $translatedMessage;
	}
}
