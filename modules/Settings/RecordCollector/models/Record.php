<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce S.A.
 * *********************************************************************************** */

class Settings_RecordCollector_Record_Model extends Settings_Vtiger_Record_Model
{
	public $module;

	/** @var array Record changes */
	protected array $changes = [];

	// get edit fields per collector class
	/**
	 * Edit fields.
	 *
	 * @var string[]
	 */
	private array $editFields = ['api_key', 'tabid'];

	/**
	 * @return int|mixed|null
	 */
	public function getId()
	{
		return $this->get('id');
	}

	/**
	 * Function to get Name of this record instance.
	 *
	 * @return string Name
	 */
	public function getName()
	{
		return '';
	}

	public function getModule()
	{
		return $this->module;
	}

	public function setModule($moduleModel)
	{
		$this->module = $moduleModel;
		return $this;
	}

	/**
	 * Function to save.
	 */
	public function save()
	{
		$result = false;
		$db = App\Db::getInstance('admin');
		$transaction = $db->beginTransaction();
		try {
			$this->saveToDb();
			$transaction->commit();
			$result = true;
		} catch (\Throwable $ex) {
			$transaction->rollBack();
			\App\Log::error($ex->__toString());
			throw $ex;
		}
		$this->clearCache($this->getId());
		return $result;
	}

	/**
	 * Save data to the database.
	 */
	public function saveToDb()
	{
		$db = \App\Db::getInstance('admin');
		$fields = array_flip(['isactive', 'api_key', 'parameters', 'name', 'default', 'tabid']);
		$tablesData = $this->getId() ? array_intersect_key($this->getData(), $this->changes, $fields) : array_intersect_key($this->getData(), $fields);
		if ($tablesData) {
			$baseTable = $this->getModule()->baseTable;
			$baseTableIndex = $this->getModule()->baseIndex;
			if ($this->getId()) {
				$db->createCommand()->update($baseTable, $tablesData, [$baseTableIndex => (int) $this->getId()])->execute();
			} else {
				$db->createCommand()->insert($baseTable, $tablesData)->execute();
				$this->set('id', $db->getLastInsertID("{$baseTable}_{$baseTableIndex}_seq"));
			}
			if (!empty($tablesData['default'])) {
				$db->createCommand()->update($baseTable, ['default' => 0], ['<>', $baseTableIndex, (int) $this->getId()])->execute();
			}
		}
	}

	/**
	 * Get pervious value by field.
	 *
	 * @param string $fieldName
	 *
	 * @return mixed
	 */
	public function getPreviousValue(string $fieldName = '')
	{
		return $fieldName ? ($this->changes[$fieldName] ?? null) : $this->changes;
	}

	/**
	 * Sets data from request.
	 *
	 * @param App\Request $request
	 */
	public function setDataFromRequest(App\Request $request)
	{
		foreach ($this->getEditFields() as $fieldName => $fieldModel) {
			$configFields = $request->getArray('config');
			if (isset($configFields[$fieldName])) {
				$value = $configFields[$fieldName] && !$fieldModel->isMandatory() ? '' : $request->getByType($fieldName, $fieldModel->get('purifyType'));
				if ('api_key' === $fieldName) {
					$value = App\Encryption::getInstance()->encrypt($value);
				}
				$fieldModel->getUITypeModel()->validate($value, true);
				$value = $fieldModel->getUITypeModel()->getDBValue($value);

				if (\in_array($fieldName, ['api_key', 'tabid'])) {
					$this->set($fieldName, $value);
				} else {
					$parameters = $this->getParameters();
					$parameters[$fieldName] = $value;
					$this->set('params', \App\Json::encode($parameters));
				}
			}
		}
	}

	/**
	 * Function to set the value for a given key.
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
	public function set($key, $value)
	{
		if ($this->getId() && !\in_array($key, ['id']) && (\array_key_exists($key, $this->value) && $this->value[$key] != $value) && !\array_key_exists($key, $this->changes)) {
			$this->changes[$key] = $this->get($key);
		}
		return parent::set($key, $value);
	}

	/**
	 * Data anonymization.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function anonymize(array $data): array
	{
		foreach ($data as $key => &$value) {
			if ('api_key' === $key || 'pwd' === $key) {
				$value = '****';
			}
		}
		return $data;
	}

	/**
	 * @param string $name
	 * @return self
	 */
	public static function getInstanceByName(string $name)
	{
		$base = $tabIds =[];
		$query = (new App\Db\Query())->from('vtiger_links')->where(['linklabel' => $name]);
		$rows = $query->createCommand()->query()->readAll();

		foreach ($rows as $index => $row) {
			if ($index === 0) {
				$base = $row;
			}
			if (isset($row['tabid'])) {
				$tabIds[] = $row['tabid'];
			}
		}
		$base['tabid'] = $tabIds;
		$instance = new self();
		$instance->setData($base);
		$moduleModel = Settings_Vtiger_Module_Model::getInstance('Settings:RecordCollector');
		$instance->setModule($moduleModel);

		return $instance;
	}

	/**
	 * Function determines fields available in edition view.
	 *
	 * @param string $name
	 *
	 * @return \Vtiger_Field_Model
	 */
	public function getFieldInstanceByName(string $name): Vtiger_Field_Model
	{
		$moduleName = 'Settings:RecordCollector';
		$params = ['uitype' => 1, 'column' => $name, 'name' => $name, 'displaytype' => 2, 'typeofdata' => 'V~M', 'presence' => 0, 'isEditableReadOnly' => false];
		switch ($name) {
			case 'api_key':
				$params['uitype'] = 99;
				$params['label'] = 'LBL_API_KEY';
				$params['purifyType'] = \App\Purifier::ALNUM;
				$params['fieldvalue'] = $this->getValueByField($name);
				$params['maximumlength'] = '100';
				break;
			case 'tabid':
				$params['uitype'] = 33;
				$params['label'] = 'LBL_MODULES';
				$params['typeofdata'] = 'V~M';
				$params['maximumlength'] = '65535';
				$params['fieldvalue'] = $this->getValueByField($name);
				$params['picklistValues'] = [];

				foreach (\vtlib\Functions::getAllModules(true, false, 0) as $module) {
					if (\Api\WebservicePremium\Privilege::isPermitted($module['name'])) {
						$params['picklistValues'][$module['tabid']] = \App\Language::translate($module['name'], $module['name']);
					}
				}
				break;
			default:
				break;
		}

		return Settings_Vtiger_Field_Model::init($moduleName, $params);
	}

	/**
	 * Function determines fields available in edition view.
	 *
	 * @return Vtiger_Field_Model[]
	 */
	public function getEditFields()
	{
		$fields = [];
		//do wyniesienia editFields[] per recordCollection
		foreach ($this->editFields as $fieldName) {
			$fields[$fieldName] = $this->getFieldInstanceByName($fieldName);
		}
		// provider ?
		return $fields;
	}

	/**
	 * Get parameters.
	 *
	 * @return array
	 */
	public function getParameters(): array
	{
		return $this->get('params') ? \App\Json::decode($this->get('params')) : [];
	}

	/**
	 * Get parameter value by name.
	 *
	 * @param string $fieldName
	 *
	 * @return string
	 */
	public function getParameter(string $fieldName): string
	{
		return $this->getParameters()[$fieldName] ?? '';
	}

	/**
	 * Get value by name.
	 *
	 * @param string $fieldName
	 *
	 * @return mixed
	 */
	public function getValueByField(string $fieldName)
	{
		return \array_key_exists($fieldName, $this->value) ? $this->value[$fieldName] : $this->getParameter($fieldName);
	}

	/**
	 * Function removes record.
	 *
	 * @return bool
	 */
	public function delete()
	{
		$db = App\Db::getInstance('admin');
		$recordId = $this->getId();
		if ($recordId) {
			$table = $this->getModule()->getBaseTable();
			$index = $this->getModule()->getBaseIndex();
			$result = $db->createCommand()->delete($table, [$index => $recordId])->execute();
		}
		return !empty($result);
	}

	/**
	 * @param string $name
	 * @return array
	 */
	public static function getAllInstancesByName(string $name): array
	{
		$allRecords = [];
		$query = (new App\Db\Query())->from('vtiger_links')->where(['linklabel' => $name]);
		$rows = $query->createCommand()->query()->readAll();
		foreach ($rows as $row) {
			$instance = new self();
			$instance->setData($row);
			$moduleModel = Settings_Vtiger_Module_Model::getInstance('Settings:RecordCollector');
			$instance->setModule($moduleModel);
			$allRecords[] = $instance;
		}

		return $allRecords;
	}
}
