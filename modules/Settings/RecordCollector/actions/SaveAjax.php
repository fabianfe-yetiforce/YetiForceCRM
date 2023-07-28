<?php
/**
 * RecordCollector active action file.
 *
 * @package Settings.Action
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Sławomir Rembiesa <s.rembiesa@yetiforce.com>
 * @author    Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

/**
 * RecordCollector active action class.
 */
class Settings_RecordCollector_SaveAjax_Action extends Settings_Vtiger_Save_Action
{
	/** {@inheritdoc} */
	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('changeStatus');
		$this->exposeMethod('changeFeatured');
		$this->exposeMethod('saveConfig');
	}

	/**
	 * Function changing status of activity for collectors.
	 *
	 * @param App\Request $request
	 *
	 * @return void
	 */
	public function changeStatus(App\Request $request): void
	{
		$collectorName = $request->getByType('collector');

		if ($request->getBoolean('status')) {
			\vtlib\Link::addLink(0, 'EDIT_VIEW_RECORD_COLLECTOR', $collectorName, 'App\RecordCollectors\\' . $collectorName);
		} else {
			\vtlib\Link::deleteLink(0, 'EDIT_VIEW_RECORD_COLLECTOR', $collectorName);
		}
		$response = new Vtiger_Response();
		$response->setResult(['success' => true, 'message' => App\Language::translate('LBL_SUCCESSFULLY_UPDATED', 'Settings:RecordCollector')]);
		$response->emit();
	}

	/**
	 * Change collector feature.
	 *
	 * @param App\Request $request
	 *
	 * @return void
	 */
	public function changeFeatured(App\Request $request): void
	{
		//to refactor
		$collectorName = $request->getByType('collector');
		\App\Db::getInstance()->createCommand()
			->update('vtiger_links',
				['linkicon' => $request->getBoolean('status') ? 1 : ''],
				['linktype' => 'EDIT_VIEW_RECORD_COLLECTOR', 'linklabel' => $collectorName])
			->execute();

		$response = new Vtiger_Response();
		$response->setResult(['success' => true, 'message' => App\Language::translate('LBL_SUCCESSFULLY_UPDATED', 'Settings:RecordCollector')]);
		$response->emit();
	}

	/**
	 * Save the collector configuration.
	 *
	 * @param App\Request $request
	 *
	 * @return void
	 */
	public function saveConfig(App\Request $request): void
	{
		$config = $request->getArray('config');
		$recordCollectorName = $request->getByType('collector');

		if (empty($config) || !$recordCollectorName) {
			throw new \App\Exceptions\IllegalValue('ERR_NOT_ALLOWED_VALUE||', 406);
		}

		$recordsModel = Settings_RecordCollector_Record_Model::getAllInstancesByName($recordCollectorName);
		//to refactor and check
		foreach ($recordsModel as $record) {
			$record->setDataFromRequest($request);
			$response = new Vtiger_Response();
			try {
				$result = $record->save();
				$response->setResult([$result]);
			} catch (Exception $e) {
				$response->setError($e->getMessage());
			}
			$response->emit();
		}

		$response = new Vtiger_Response();
		$response->setResult(['success' => true, 'message' => \App\Language::translate('LBL_SAVE_NOTIFY_OK', $request->getModule(false))]);
		$response->emit();
	}
}