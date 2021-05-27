<?php
/**
 * Base action file.
 *
 * @package API
 *
 * @copyright YetiForce Sp. z o.o
 * @license   YetiForce Public License 3.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

namespace Api\Core;

/**
 * Base action class.
 */
class BaseAction
{
	/** @var string[] Allowed methods */
	public $allowedMethod;

	/** @var array Allowed headers */
	public $allowedHeaders = [];

	/** @var \Api\Controller */
	public $controller;

	/** @var string Response data type. */
	public $responseType = 'data';

	/** @var array User data */
	protected $userData = [];

	/**
	 * Check called action.
	 *
	 * @return void
	 */
	public function checkAction(): void
	{
		if ((isset($this->allowedMethod) && !\in_array($this->controller->method, $this->allowedMethod)) || !method_exists($this, $this->controller->method)) {
			throw new \Api\Core\Exception('Invalid method', 405);
		}
		$this->checkPermission();
		$this->checkPermissionToModule();
	}

	/**
	 * Check permission to module.
	 *
	 * @throws \Api\Core\Exception
	 *
	 * @return void
	 */
	public function checkPermissionToModule(): void
	{
		if (!$this->controller->request->isEmpty('module') && !Module::checkModuleAccess($this->controller->request->get('module'))) {
			throw new \Api\Core\Exception('No permissions for module', 403);
		}
	}

	/**
	 * Check permission to method.
	 *
	 * @throws \Api\Core\Exception
	 *
	 * @return void
	 */
	public function checkPermission(): void
	{
		if (empty($this->controller->headers['x-token'])) {
			throw new \Api\Core\Exception('No sent token', 401);
		}
		$sessionTable = Containers::$listTables[$this->controller->app['type']]['session'];
		$userTable = Containers::$listTables[$this->controller->app['type']]['user'];
		$db = \App\Db::getInstance('webservice');
		$this->userData = (new \App\Db\Query())->select(["$userTable.*", 'sid' => "$sessionTable.id", "$sessionTable.language", "$sessionTable.created", "$sessionTable.changed", "$sessionTable.params"])
			->from($userTable)
			->innerJoin($sessionTable, "$sessionTable.user_id = $userTable.id")
			->where(["$sessionTable.id" => $this->controller->headers['x-token'], "$userTable.status" => 1])
			->one($db);
		if (empty($this->userData)) {
			throw new \Api\Core\Exception('Invalid token', 401);
		}
		if ((strtotime('now') > strtotime($this->userData['created']) + (\Config\Security::$apiLifetimeSessionCreate * 60)) || (strtotime('now') > strtotime($this->userData['changed']) + (\Config\Security::$apiLifetimeSessionUpdate * 60))) {
			$db->createCommand()
				->delete($sessionTable, ['id' => $this->controller->headers['x-token']])
				->execute();
			throw new \Api\Core\Exception('Token has expired', 401);
		}

		$this->userData['type'] = (int) $this->userData['type'];
		$this->userData['custom_params'] = \App\Json::isEmpty($this->userData['custom_params']) ? [] : \App\Json::decode($this->userData['custom_params']);
		\App\User::setCurrentUserId($this->userData['user_id']);
		$userModel = \App\User::getCurrentUserModel();
		$userModel->set('permission_type', $this->userData['type']);
		$userModel->set('permission_crmid', $this->userData['crmid']);
		$userModel->set('permission_app', (int) $this->controller->app['id']);
		$namespace = $this->controller->app['type'];
		\App\Privilege::setPermissionInterpreter("\\Api\\{$namespace}\\Privilege");
		\App\PrivilegeQuery::setPermissionInterpreter("\\Api\\{$namespace}\\PrivilegeQuery");
		$this->updateSession();
	}

	/**
	 * Pre process function.
	 */
	public function preProcess()
	{
		$language = $this->getLanguage();
		if ($language) {
			\App\Language::setTemporaryLanguage($language);
		}
		if (\App\Config::performance('CHANGE_LOCALE')) {
			\App\Language::initLocale();
		}
	}

	/**
	 * Get current language.
	 *
	 * @return string
	 */
	public function getLanguage(): string
	{
		$language = '';
		if (!empty($this->userData['language'])) {
			$language = $this->userData['language'];
		} elseif (!empty($this->userData['custom_params']['language'])) {
			$language = $this->userData['custom_params']['language'];
		} elseif ($this->data && isset($this->data['custom_params']['language'])) {
			$language = $this->data['custom_params']['language'];
		} elseif (!empty($this->controller->headers['accept-language'])) {
			$language = str_replace('_', '-', \Locale::acceptFromHttp($this->controller->headers['accept-language']));
		} else {
			$language = \App\Config::main('default_language');
		}
		return $language;
	}

	/**
	 * Get permission type.
	 *
	 * @return int
	 */
	public function getPermissionType(): int
	{
		return $this->userData['type'];
	}

	/**
	 * Get crmid for portal user.
	 *
	 * @return int
	 */
	public function getUserCrmId(): int
	{
		return $this->userData['crmid'];
	}

	/**
	 * Get user storage ID.
	 *
	 * @return int
	 */
	public function getUserStorageId(): ?int
	{
		return $this->userData['istorage'] ?? null;
	}

	/**
	 * Get information, whether to check inventory levels.
	 *
	 * @return bool
	 */
	public function getCheckStockLevels(): bool
	{
		$parentId = \Api\Portal\Privilege::USER_PERMISSIONS !== $this->getPermissionType() ? $this->getParentCrmId() : 0;
		return empty($parentId) || (bool) \Vtiger_Record_Model::getInstanceById($parentId)->get('check_stock_levels');
	}

	/**
	 * Get parent record.
	 *
	 * @return int
	 */
	public function getParentCrmId(): int
	{
		if ($this->controller && ($parentId = $this->controller->request->getHeader('x-parent-id'))) {
			$hierarchy = new \Api\Portal\BaseModule\Hierarchy();
			$hierarchy->setUserData($this->userData);
			$hierarchy->findId = $parentId;
			$hierarchy->moduleName = \App\Record::getType(\App\Record::getParentRecord($this->getUserCrmId()));
			$records = $hierarchy->get();
			if (isset($records[$parentId])) {
				return $parentId;
			}
			throw new \Api\Core\Exception('No permission to X-PARENT-ID', 403);
		}
		return \App\Record::getParentRecord($this->getUserCrmId());
	}

	/**
	 * Set user data.
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function setUserData(array $data): void
	{
		$this->userData = array_merge_recursive($this->userData, $data);
	}

	/**
	 * Update user session.
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function updateSession(array $data = []): void
	{
		$data['changed'] = date('Y-m-d H:i:s');
		$data['ip'] = $this->controller->request->getServer('REMOTE_ADDR');
		$data['last_method'] = $this->controller->request->getServer('REQUEST_URI');
		\App\Db::getInstance('webservice')->createCommand()
			->update(\Api\Core\Containers::$listTables[$this->controller->app['type']]['session'], $data, ['id' => $this->userData['sid']])
			->execute();
	}

	/**
	 * Update user data.
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function updateUser(array $data = []): void
	{
		$data['custom_params'] = \App\Json::encode($this->userData['custom_params']);
		\App\Db::getInstance('webservice')->createCommand()
			->update(\Api\Core\Containers::$listTables[$this->controller->app['type']]['user'], $data, ['id' => $this->userData['id']])
			->execute();
	}
}
