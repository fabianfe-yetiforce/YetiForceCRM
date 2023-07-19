<?php
/**
 * VAT Payer Status Verification in Poland record collector file.
 *
 * @see https://ppuslugi.mf.gov.pl/
 * @see https://www.podatki.gov.pl/e-deklaracje/dokumentacja-it/
 * @see https://www.podatki.gov.pl/media/3275/specyfikacja-we-wy.pdf
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
 * VAT Payer Status Verification in Poland record collector class.
 */
class PlVatPayerStatusVerification extends Base
{
	/** {@inheritdoc} */
	public $allowedModules = ['Accounts', 'Leads', 'Vendors', 'Competition', 'Partners'];

	/** {@inheritdoc} */
	public string $icon = 'yfi-vat-pl';

	/** {@inheritdoc} */
	public string $label = 'LBL_PL_VAT_PAYER';

	/** {@inheritdoc} */
	public string $displayType = 'Summary';

	/** {@inheritdoc} */
	public string $description = 'LBL_PL_VAT_PAYER_DESC';

	/** {@inheritdoc} */
	public string $docUrl = 'https://www.podatki.gov.pl/e-deklaracje/dokumentacja-it/';

	/** @var string MF sever address */
	protected string $url = 'https://sprawdz-status-vat.mf.gov.pl/?wsdl';

	/** {@inheritdoc} */
	protected array $fields = [
		'vatNumber' => [
			'labelModule' => '_Base',
			'label' => 'Vat ID',
			'typeofdata' => 'V~M',
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
		'Competition' => [
			'vatNumber' => 'vat_id',
		],
		'Partners' => [
			'vatNumber' => 'vat_id',
		],
	];

	/** {@inheritdoc} */
	public function search(): array
	{
		if (!$this->isActive()) {
			return [];
		}
		$vatNumber = str_replace([' ', ',', '.', '-'], '', $this->request->getByType('vatNumber', 'Text'));
		if (!$vatNumber) {
			return [];
		}
		try {
			if ($client = new \SoapClient($this->url, \App\RequestHttp::getSoapOptions())) {
				$responseData = $client->sprawdzNIP($vatNumber);
				if ($responseData->Kod === "I") {
					$response['error'] = \App\Language::translate('LBL_INCORRECT_VAT_NUMBER', 'Other.RecordCollector');
				} else {
					$response['fields'] = [
						'' => $responseData->Komunikat
					];
				}
			}
		} catch (\SoapFault $e) {
			\App\Log::warning($e->faultstring, 'RecordCollectors');
			$response['error'] = $e->faultstring;
		}

		return $response;
	}
}
