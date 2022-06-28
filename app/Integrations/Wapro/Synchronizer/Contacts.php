<?php

/**
 * WAPRO ERP contacts synchronizer file.
 *
 * @package Integration
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

namespace App\Integrations\Wapro\Synchronizer;

/**
 * WAPRO ERP contacts synchronizer class.
 */
class Contacts extends \App\Integrations\Wapro\Synchronizer
{
	/** {@inheritdoc} */
	const NAME = 'LBL_CONTACTS';

	/** {@inheritdoc} */
	protected $fieldMap = [
		'ID_KONTRAHENTA' => ['fieldName' => 'parent_id', 'fn' => 'findRelationship', 'tableName' => 'KONTRAHENT'],
		'IMIE' => 'firstname',
		'NAZWISKO' => 'lastname',
		'TYTUL' => 'jobtitle',
		'TEL' => ['fieldName' => 'phone', 'fn' => 'convertPhone'],
		'TEL_KOM' => ['fieldName' => 'mobile', 'fn' => 'convertPhone'],
		'E_MAIL' => 'email',
		'E_MAIL_DW' => 'secondary_email',
		'UWAGI' => 'description',
	];

	/** {@inheritdoc} */
	public function process(): void
	{
		$query = (new \App\Db\Query())->from('dbo.KONTAKT');
		$pauser = \App\Pauser::getInstance('WaproContactsLastId');
		if ($val = $pauser->getValue()) {
			$query->where(['>', 'ID_KONTAKTU', $val]);
		}
		$lastId = $s = $e = $i = $u = 0;
		foreach ($query->batch(50, $this->controller->getDb()) as $rows) {
			$lastId = 0;
			foreach ($rows as $row) {
				$this->row = $row;
				$this->skip = false;
				try {
					switch ($this->importRecord()) {
						default:
						case 0:
							++$s;
							break;
						case 1:
							++$u;
							break;
						case 2:
							++$i;
							break;
					}
					$lastId = $row['ID_KONTAKTU'];
				} catch (\Throwable $th) {
					$this->logError($th);
					++$e;
				}
			}
			$pauser->setValue($lastId);
		}
		if (0 == $lastId) {
			$pauser->destroy();
		}
		$this->log("Create {$i} | Update {$u} | Skipped {$s} | Error {$e}");
	}

	/** {@inheritdoc} */
	public function importRecord(): int
	{
		if ($id = $this->findInMapTable($this->row['ID_KONTAKTU'], 'KONTAKT')) {
			$this->recordModel = \Vtiger_Record_Model::getInstanceById($id, 'Contacts');
		} else {
			$this->recordModel = \Vtiger_Record_Model::getCleanInstance('Contacts');
			$this->recordModel->setDataForSave([\App\Integrations\Wapro::RECORDS_MAP_TABLE_NAME => [
				'wtable' => 'KONTAKT',
			]]);
		}
		$this->recordModel->set('wapro_id', $this->row['ID_KONTAKTU']);
		$this->loadFromFieldMap();
		if ($this->skip) {
			return 0;
		}
		$this->recordModel->save();
		return $id ? 1 : 2;
	}
}