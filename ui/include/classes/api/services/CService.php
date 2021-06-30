<?php
/*
** Zabbix
** Copyright (C) 2001-2021 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


/**
 * Services API implementation.
 */
class CService extends CApiService {

	public const ACCESS_RULES = [
		'get' => ['min_user_type' => USER_TYPE_ZABBIX_USER],
		'getsla' => ['min_user_type' => USER_TYPE_ZABBIX_USER],
		'create' => ['min_user_type' => USER_TYPE_ZABBIX_ADMIN],
		'update' => ['min_user_type' => USER_TYPE_ZABBIX_ADMIN],
		'delete' => ['min_user_type' => USER_TYPE_ZABBIX_ADMIN]
	];

	protected $tableName = 'services';
	protected $tableAlias = 's';
	protected $sortColumns = ['sortorder', 'name'];

	/**
	 * @param array $options

	 * @return array|int

	 * @throws APIException
	 */
	public function get(array $options = []) {
		$api_input_rules = ['type' => API_OBJECT, 'fields' => [
			// filter
			'serviceids' =>				['type' => API_IDS, 'flags' => API_ALLOW_NULL | API_NORMALIZE, 'default' => null],
			'parentids' =>				['type' => API_IDS, 'flags' => API_ALLOW_NULL | API_NORMALIZE, 'default' => null],
			'childids' =>				['type' => API_IDS, 'flags' => API_ALLOW_NULL | API_NORMALIZE, 'default' => null],
			'evaltype' =>				['type' => API_INT32, 'in' => implode(',', [TAG_EVAL_TYPE_AND_OR, TAG_EVAL_TYPE_OR]), 'default' => TAG_EVAL_TYPE_AND_OR],
			'tags' =>					['type' => API_OBJECTS, 'default' => [], 'fields' => [
				'tag' =>					['type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY],
				'value' =>					['type' => API_STRING_UTF8],
				'operator' =>				['type' => API_STRING_UTF8, 'in' => implode(',', [TAG_OPERATOR_LIKE, TAG_OPERATOR_EQUAL, TAG_OPERATOR_NOT_LIKE, TAG_OPERATOR_NOT_EQUAL, TAG_OPERATOR_EXISTS, TAG_OPERATOR_NOT_EXISTS])]
			]],
			'filter' =>					['type' => API_OBJECT, 'flags' => API_ALLOW_NULL, 'default' => null, 'fields' => [
				'serviceid' =>				['type' => API_IDS, 'flags' => API_ALLOW_NULL | API_NORMALIZE],
				'name' =>					['type' => API_STRINGS_UTF8, 'flags' => API_ALLOW_NULL | API_NORMALIZE],
				'status' =>					['type' => API_INTS32, 'flags' => API_ALLOW_NULL | API_NORMALIZE, 'in' => implode(',', range(TRIGGER_SEVERITY_NOT_CLASSIFIED, TRIGGER_SEVERITY_COUNT - 1))],
				'algorithm' =>				['type' => API_INTS32, 'flags' => API_ALLOW_NULL | API_NORMALIZE, 'in' => implode(',', array_keys(serviceAlgorithm()))],
				'triggerid' =>				['type' => API_IDS, 'flags' => API_ALLOW_NULL | API_NORMALIZE],
				'showsla' =>				['type' => API_INTS32, 'flags' => API_ALLOW_NULL | API_NORMALIZE, 'in' => '0,1']
			]],
			'search' =>					['type' => API_OBJECT, 'flags' => API_ALLOW_NULL, 'default' => null, 'fields' => [
				'name' =>					['type' => API_STRINGS_UTF8, 'flags' => API_ALLOW_NULL | API_NORMALIZE]
			]],
			'searchByAny' =>			['type' => API_BOOLEAN, 'default' => false],
			'startSearch' =>			['type' => API_FLAG, 'default' => false],
			'excludeSearch' =>			['type' => API_FLAG, 'default' => false],
			'searchWildcardsEnabled' =>	['type' => API_BOOLEAN, 'default' => false],
			// output
			'output' =>					['type' => API_OUTPUT, 'in' => implode(',', ['serviceid', 'name', 'status', 'algorithm', 'triggerid', 'showsla', 'goodsla', 'sortorder']), 'default' => API_OUTPUT_EXTEND],
			'countOutput' =>			['type' => API_FLAG, 'default' => false],
			'selectParents' =>			['type' => API_OUTPUT, 'flags' => API_ALLOW_NULL | API_ALLOW_COUNT, 'in' => implode(',', ['serviceid', 'name', 'status', 'algorithm', 'triggerid', 'showsla', 'goodsla', 'sortorder']), 'default' => null],
			'selectChildren' =>			['type' => API_OUTPUT, 'flags' => API_ALLOW_NULL | API_ALLOW_COUNT, 'in' => implode(',', ['serviceid', 'name', 'status', 'algorithm', 'triggerid', 'showsla', 'goodsla', 'sortorder']), 'default' => null],
			'selectTrigger' =>			['type' => API_OUTPUT, 'flags' => API_ALLOW_NULL, 'default' => null],
			'selectTags' =>				['type' => API_OUTPUT, 'flags' => API_ALLOW_NULL, 'in' => implode(',', ['tag', 'value']), 'default' => null],
			'selectTimes' =>			['type' => API_OUTPUT, 'flags' => API_ALLOW_NULL, 'in' => implode(',', ['ts_from', 'ts_to', 'type', 'note']), 'default' => null],
			'selectAlarms' =>			['type' => API_OUTPUT, 'flags' => API_ALLOW_NULL, 'in' => implode(',', ['clock', 'value']), 'default' => null],
			// sort and limit
			'sortfield' =>				['type' => API_STRINGS_UTF8, 'flags' => API_NORMALIZE, 'in' => implode(',', $this->sortColumns), 'uniq' => true, 'default' => []],
			'sortorder' =>				['type' => API_SORTORDER, 'default' => []],
			'limit' =>					['type' => API_INT32, 'flags' => API_ALLOW_NULL, 'in' => '1:'.ZBX_MAX_INT32, 'default' => null],
			// flags
			'editable' =>				['type' => API_BOOLEAN, 'default' => false],
			'preservekeys' =>			['type' => API_BOOLEAN, 'default' => false]
		]];

		if (!CApiInputValidator::validate($api_input_rules, $options, '/', $error)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, $error);
		}

		$db_services = [];

		$sql = $this->createSelectQuery($this->tableName(), $options);
		$resource = DBselect($sql, $options['limit']);

		while ($row = DBfetch($resource)) {
			if ($options['countOutput']) {
				return $row['rowscount'];
			}

			$db_services[$row['serviceid']] = $row;
		}

		if ($db_services) {
			$db_services = $this->addRelatedObjects($options, $db_services);
			$db_services = $this->unsetExtraFields($db_services, ['serviceid', 'triggerid'], $options['output']);

			if (!$options['preservekeys']) {
				$db_services = array_values($db_services);
			}
		}

		return $db_services;
	}

	/**
	 * @param array $services

	 * @return array

	 * @throws APIException
	 */
	public function create(array $services): array {
		$this->validateCreate($services);

		$ins_services = [];

		foreach ($services as $service) {
			unset($service['tags'], $service['parents'], $service['children'], $service['times']);
			$ins_services[] = $service;
		}

		$serviceids = DB::insert('services', $ins_services);
		$services = array_combine($serviceids, $services);

		$this->updateTags($services, __FUNCTION__);
		$this->updateParents($services, __FUNCTION__);
		$this->updateChildren($services, __FUNCTION__);
		$this->updateTimes($services,  __FUNCTION__);

		return ['serviceids' => $serviceids];
	}

	/**
	 * @param array $services

	 * @throws APIException
	 */
	private function validateCreate(array &$services): void {
		$api_input_rules = ['type' => API_OBJECTS, 'flags' => API_NOT_EMPTY | API_NORMALIZE, 'fields' => [
			'name' =>		['type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('services', 'name')],
			'algorithm' =>	['type' => API_INT32, 'flags' => API_REQUIRED, 'in' => implode(',', array_keys(serviceAlgorithm()))],
			'triggerid' =>	['type' => API_ID],
			'showsla' =>	['type' => API_INT32, 'flags' => API_REQUIRED, 'in' => implode(',', [SERVICE_SHOW_SLA_OFF, SERVICE_SHOW_SLA_ON])],
			'goodsla' =>	['type' => API_FLOAT, 'in' => '0:100'],
			'sortorder' =>	['type' => API_INT32, 'flags' => API_REQUIRED, 'in' => '0:999'],
			'tags' =>		['type' => API_OBJECTS, 'uniq' => [['tag', 'value']], 'fields' => [
				'tag' =>		['type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('service_tag', 'tag')],
				'value' =>		['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('service_tag', 'value'), 'default' => DB::getDefault('service_tag', 'value')]
			]],
			'parents' =>	['type' => API_OBJECTS, 'uniq' => [['serviceid']], 'fields' => [
				'serviceid' =>	['type' => API_ID]
			]],
			'children' =>	['type' => API_OBJECTS, 'uniq' => [['serviceid']], 'fields' => [
				'serviceid' =>	['type' => API_ID]
			]],
			'times' =>		['type' => API_OBJECTS, 'uniq' => [['type', 'ts_from', 'ts_to']], 'fields' => [
				'type' =>		['type' => API_INT32, 'flags' => API_REQUIRED, 'in' => implode(',', [SERVICE_TIME_TYPE_UPTIME, SERVICE_TIME_TYPE_DOWNTIME, SERVICE_TIME_TYPE_ONETIME_DOWNTIME])],
				'ts_from' =>	['type' => API_MULTIPLE, 'flags' => API_REQUIRED, 'rules' => [
					['if' => ['field' => 'type', 'in' => implode(',', [SERVICE_TIME_TYPE_DOWNTIME, SERVICE_TIME_TYPE_UPTIME])], 'type' => API_INT32, 'in' => '0:'.SEC_PER_WEEK],
					['if' => ['field' => 'type', 'in' => implode(',', [SERVICE_TIME_TYPE_ONETIME_DOWNTIME])], 'type' => API_INT32, 'in' => '0:'.ZBX_MAX_DATE]
				]],
				'ts_to' =>		['type' => API_MULTIPLE, 'flags' => API_REQUIRED, 'rules' => [
					['if' => ['field' => 'type', 'in' => implode(',', [SERVICE_TIME_TYPE_DOWNTIME, SERVICE_TIME_TYPE_UPTIME])], 'type' => API_INT32, 'in' => '0:'.SEC_PER_WEEK],
					['if' => ['field' => 'type', 'in' => implode(',', [SERVICE_TIME_TYPE_ONETIME_DOWNTIME])], 'type' => API_INT32, 'in' => '0:'.ZBX_MAX_DATE]
				]],
				'note' =>		['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('services_times', 'note'), 'default' => DB::getDefault('services_times', 'note')]
			]]
		]];

		if (!CApiInputValidator::validate($api_input_rules, $services, '/', $error)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, $error);
		}

		$this->checkTriggerAndChildrenExclusivity($services);
		$this->checkTriggerPermissions($services);
		$this->checkParents($services);
		$this->checkChildren($services);
		$this->checkCircularReferences($services);
	}

	/**
	 * @param array $services

	 * @return array

	 * @throws APIException
	 */
	public function update(array $services): array {
		$this->validateUpdate($services, $db_services);

		$upd_services = [];

		foreach ($services as $service) {
			$upd_service = DB::getUpdatedValues('services', $service, $db_services[$service['serviceid']]);

			if ($upd_service) {
				$upd_services[] = [
					'values' => $upd_service,
					'where' => ['serviceid' => $service['serviceid']]
				];
			}
		}

		if ($upd_services) {
			DB::update('services', $upd_services);
		}

		$services = array_column($services, null, 'serviceid');

		$this->updateTags($services, __FUNCTION__);
		$this->updateParents($services, __FUNCTION__);
		$this->updateChildren($services, __FUNCTION__);
		$this->updateTimes($services, __FUNCTION__);

		return ['serviceids' => array_column($services, 'serviceid')];
	}

	/**
	 * @param array      $services
	 * @param array|null $db_services

	 * @throws APIException
	 */
	private function validateUpdate(array &$services, array &$db_services = null): void {
		$api_input_rules = ['type' => API_OBJECTS, 'flags' => API_NOT_EMPTY | API_NORMALIZE, 'uniq' => [['serviceid']], 'fields' => [
			'serviceid' =>	['type' => API_ID, 'flags' => API_REQUIRED],
			'name' =>		['type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'length' => DB::getFieldLength('services', 'name')],
			'algorithm' =>	['type' => API_INT32, 'in' => implode(',', array_keys(serviceAlgorithm()))],
			'triggerid' =>	['type' => API_ID],
			'showsla' =>	['type' => API_INT32, 'in' => implode(',', [SERVICE_SHOW_SLA_OFF, SERVICE_SHOW_SLA_ON])],
			'goodsla' =>	['type' => API_FLOAT, 'in' => '0:100'],
			'sortorder' =>	['type' => API_INT32, 'in' => '0:999'],
			'tags' =>		['type' => API_OBJECTS, 'uniq' => [['tag', 'value']], 'fields' => [
				'tag' =>		['type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('service_tag', 'tag')],
				'value' =>		['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('service_tag', 'value'), 'default' => DB::getDefault('service_tag', 'value')]
			]],
			'parents' =>	['type' => API_OBJECTS, 'uniq' => [['serviceid']], 'fields' => [
				'serviceid' =>	['type' => API_ID]
			]],
			'children' =>	['type' => API_OBJECTS, 'uniq' => [['serviceid']], 'fields' => [
				'serviceid' =>	['type' => API_ID]
			]],
			'times' =>		['type' => API_OBJECTS, 'uniq' => [['type', 'ts_from', 'ts_to']], 'fields' => [
				'type' =>		['type' => API_INT32, 'flags' => API_REQUIRED, 'in' => implode(',', [SERVICE_TIME_TYPE_UPTIME, SERVICE_TIME_TYPE_DOWNTIME, SERVICE_TIME_TYPE_ONETIME_DOWNTIME])],
				'ts_from' =>	['type' => API_MULTIPLE, 'flags' => API_REQUIRED, 'rules' => [
					['if' => ['field' => 'type', 'in' => implode(',', [SERVICE_TIME_TYPE_DOWNTIME, SERVICE_TIME_TYPE_UPTIME])], 'type' => API_INT32, 'in' => '0:'.SEC_PER_WEEK],
					['if' => ['field' => 'type', 'in' => implode(',', [SERVICE_TIME_TYPE_ONETIME_DOWNTIME])], 'type' => API_INT32, 'in' => '0:'.ZBX_MAX_DATE]
				]],
				'ts_to' =>		['type' => API_MULTIPLE, 'flags' => API_REQUIRED, 'rules' => [
					['if' => ['field' => 'type', 'in' => implode(',', [SERVICE_TIME_TYPE_DOWNTIME, SERVICE_TIME_TYPE_UPTIME])], 'type' => API_INT32, 'in' => '0:'.SEC_PER_WEEK],
					['if' => ['field' => 'type', 'in' => implode(',', [SERVICE_TIME_TYPE_ONETIME_DOWNTIME])], 'type' => API_INT32, 'in' => '0:'.ZBX_MAX_DATE]
				]],
				'note' =>		['type' => API_STRING_UTF8, 'length' => DB::getFieldLength('services_times', 'note'), 'default' => DB::getDefault('services_times', 'note')]
			]]
		]];

		if (!CApiInputValidator::validate($api_input_rules, $services, '/', $error)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, $error);
		}

		$db_services = $this->get([
			'output' => ['serviceid', 'name', 'status', 'algorithm', 'triggerid', 'showsla', 'goodsla', 'sortorder'],
			'selectParents' => ['serviceid'],
			'selectChildren' => ['serviceid'],
			'serviceids' => array_column($services, 'serviceid'),
			'editable' => true,
			'preservekeys' => true
		]);

		if (count($db_services) != count($services)) {
			self::exception(ZBX_API_ERROR_PERMISSIONS, _('No permissions to referred object or it does not exist!'));
		}

		$this->checkTriggerAndChildrenExclusivity($services, $db_services);
		$this->checkTriggerPermissions($services, $db_services);
		$this->checkParents($services);
		$this->checkChildren($services);
		$this->checkCircularReferences($services, $db_services);
	}

	/**
	 * @param array $serviceids

	 * @return array

	 * @throws APIException
	 */
	public function delete(array $serviceids): array {
		$api_input_rules = ['type' => API_IDS, 'flags' => API_NOT_EMPTY, 'uniq' => true];

		if (!CApiInputValidator::validate($api_input_rules, $serviceids, '/', $error)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, $error);
		}

		$count = $this->get([
			'countOutput' => true,
			'serviceids' => $serviceids,
			'editable' => true
		]);

		if ($count != count($serviceids)) {
			self::exception(ZBX_API_ERROR_PERMISSIONS, _('No permissions to referred object or it does not exist!'));
		}

		DB::delete('services', ['serviceid' => $serviceids]);

		return ['serviceids' => $serviceids];
	}

	protected function applyQueryFilterOptions($tableName, $tableAlias, array $options, array $sqlParts) {
		$sqlParts = parent::applyQueryFilterOptions($tableName, $tableAlias, $options, $sqlParts);

		if ($options['parentids'] !== null) {
			$sqlParts['left_table'] = ['table' => $this->tableName, 'alias' => $this->tableAlias];
			$sqlParts['left_join'][] = [
				'table' => 'services_links',
				'alias' => 'slp',
				'using' => 'servicedownid',
			];
			$sqlParts['where'][] = dbConditionId('slp.serviceupid', $options['parentids']);
		}

		if ($options['childids'] !== null) {
			$sqlParts['left_table'] = ['table' => $this->tableName, 'alias' => $this->tableAlias];
			$sqlParts['left_join'][] = [
				'table' => 'services_links',
				'alias' => 'slc',
				'using' => 'serviceupid',
			];
			$sqlParts['where'][] = dbConditionId('slc.servicedownid', $options['childids']);
		}

		if ($options['tags']) {
			$sqlParts['where'][] = CApiTagHelper::addWhereCondition($options['tags'], $options['evaltype'], 's',
				'service_tag', 'serviceid'
			);
		}

		return $sqlParts;
	}

	protected function applyQueryOutputOptions($tableName, $tableAlias, array $options, array $sqlParts) {
		$sqlParts = parent::applyQueryOutputOptions($tableName, $tableAlias, $options, $sqlParts);

		if (!$options['countOutput']) {
			if ($options['selectTrigger'] !== null && $options['selectTrigger'] !== API_OUTPUT_COUNT) {
				$sqlParts = $this->addQuerySelect($this->fieldId('triggerid'), $sqlParts);
			}
		}

		return $sqlParts;
	}

	protected function addRelatedObjects(array $options, array $result) {
		$result = parent::addRelatedObjects($options, $result);

		$serviceids = array_keys($result);

		if ($options['selectParents'] !== null) {
			$relation_map = $this->createRelationMap($result, 'servicedownid', 'serviceupid', 'services_links');
			$parents = $this->get([
				'output' => ($options['selectParents'] === API_OUTPUT_COUNT) ? [] : $options['selectParents'],
				'serviceids' => $relation_map->getRelatedIds(),
				'sortfield' => $options['sortfield'],
				'sortorder' => $options['sortorder'],
				'preservekeys' => true
			]);
			$result = $relation_map->mapMany($result, $parents, 'parents');

			if ($options['selectParents'] === API_OUTPUT_COUNT) {
				foreach ($result as &$row) {
					$row['parents'] = (string) count($row['parents']);
				}
				unset($row);
			}
		}

		if ($options['selectChildren'] !== null) {
			$relation_map = $this->createRelationMap($result, 'serviceupid', 'servicedownid', 'services_links');
			$children = $this->get([
				'output' => ($options['selectChildren'] === API_OUTPUT_COUNT) ? [] : $options['selectChildren'],
				'serviceids' => $relation_map->getRelatedIds(),
				'sortfield' => $options['sortfield'],
				'sortorder' => $options['sortorder'],
				'preservekeys' => true
			]);
			$result = $relation_map->mapMany($result, $children, 'children');

			if ($options['selectChildren'] === API_OUTPUT_COUNT) {
				foreach ($result as &$row) {
					$row['children'] = (string) count($row['children']);
				}
				unset($row);
			}
		}

		if ($options['selectTrigger'] !== null) {
			$relation_map = $this->createRelationMap($result, 'serviceid', 'triggerid');
			$triggers = API::Trigger()->get([
				'output' => $options['selectTrigger'],
				'triggerids' => $relation_map->getRelatedIds(),
				'preservekeys' => true
			]);
			$result = $relation_map->mapOne($result, $triggers, 'trigger');
		}

		if ($options['selectTags'] !== null) {
			$tags = API::getApiService()->select('service_tag', [
				'output' => $this->outputExtend($options['selectTags'], ['servicetagid', 'serviceid']),
				'filter' => ['serviceid' => $serviceids],
				'preservekeys' => true
			]);
			$relation_map = $this->createRelationMap($tags, 'serviceid', 'servicetagid');
			$tags = $this->unsetExtraFields($tags, ['servicetagid', 'serviceid'], ['selectTags']);
			$result = $relation_map->mapMany($result, $tags, 'tags');
		}

		if ($options['selectTimes'] !== null) {
			$times = API::getApiService()->select('services_times', [
				'output' => $this->outputExtend($options['selectTimes'], ['timeid', 'serviceid']),
				'filter' => ['serviceid' => $serviceids],
				'preservekeys' => true
			]);
			$relation_map = $this->createRelationMap($times, 'serviceid', 'timeid');
			$times = $this->unsetExtraFields($times, ['timeid', 'serviceid'], $options['selectTimes']);
			$result = $relation_map->mapMany($result, $times, 'times');
		}

		if ($options['selectAlarms'] !== null) {
			$alarms = API::getApiService()->select('service_alarms', [
				'output' => $this->outputExtend($options['selectAlarms'], ['servicealarmid', 'serviceid']),
				'filter' => ['serviceid' => $serviceids],
				'preservekeys' => true
			]);
			$relation_map = $this->createRelationMap($alarms, 'serviceid', 'servicealarmid');
			$alarms = $this->unsetExtraFields($alarms, ['servicealarmid', 'serviceid'], ['selectAlarms']);
			$result = $relation_map->mapMany($result, $alarms, 'alarms');
		}

		return $result;
	}

	/**
	 * @param array      $services
	 * @param array|null $db_services

	 * @throws APIException
	 */
	private function checkTriggerAndChildrenExclusivity(array $services, array $db_services = null): void {
		foreach ($services as $service) {
			$has_trigger = array_key_exists('triggerid', $service) && $service['triggerid'] != 0;

			$has_children = (array_key_exists('children', $service) && $service['children'])
				|| ($db_services !== null && count($db_services[$service['serviceid']]['children']) > 0);

			if ($has_trigger && $has_children) {
				$name = array_key_exists('name', $service)
					? $service['name']
					: $db_services[$service['serviceid']]['name'];

				self::exception(ZBX_API_ERROR_PARAMETERS,
					_s('Service "%1$s" cannot be linked to a trigger and have children at the same time.', $name)
				);
			}
		}
	}

	/**
	 * @param array      $services
	 * @param array|null $db_services

	 * @throws APIException
	 */
	private function checkTriggerPermissions(array $services, array $db_services = null): void {
		$triggerids = [];

		foreach ($services as $service) {
			if (!array_key_exists('triggerid', $service) || $service['triggerid'] == 0) {
				continue;
			}

			if ($db_services !== null) {
				if ((string) $service['triggerid'] === (string) $db_services[$service['serviceid']]['triggerid']) {
					continue;
				}
			}

			$triggerids[$service['triggerid']] = true;
		}

		if (!$triggerids) {
			return;
		}

		$count = API::Trigger()->get([
			'countOutput' => true,
			'triggerids' => array_keys($triggerids)
		]);

		if ($count != count($triggerids)) {
			self::exception(ZBX_API_ERROR_PERMISSIONS, _('No permissions to referred object or it does not exist!'));
		}
	}

	/**
	 * @param array $services

	 * @throws APIException
	 */
	private function checkParents(array $services): void {
		$parent_serviceids = [];

		foreach ($services as $service) {
			if (array_key_exists('parents', $service)) {
				$parent_serviceids += array_column($service['parents'], 'serviceid', 'serviceid');
			}
		}

		if (!$parent_serviceids) {
			return;
		}

		$db_parent_services = $this->get([
			'output' => ['name', 'triggerid'],
			'serviceids' => $parent_serviceids,
			'editable' => true,
			'preservekeys' => true
		]);

		if (count($db_parent_services) != count($parent_serviceids)) {
			self::exception(ZBX_API_ERROR_PERMISSIONS, _('No permissions to referred object or it does not exist!'));
		}

		foreach ($db_parent_services as $db_parent_service) {
			if ($db_parent_service['triggerid'] != 0) {
				self::exception(ZBX_API_ERROR_PARAMETERS,
					_s('Service "%1$s" cannot be linked to a trigger and have children at the same time.',
						$db_parent_service['name']
					)
				);
			}
		}
	}

	/**
	 * @param array $services

	 * @throws APIException
	 */
	private function checkChildren(array $services): void {
		$child_serviceids = [];

		foreach ($services as $service) {
			if (array_key_exists('children', $service)) {
				$child_serviceids += array_column($service['children'], 'serviceid', 'serviceid');
			}
		}

		if (!$child_serviceids) {
			return;
		}

		$count = $this->get([
			'countOutput' => true,
			'serviceids' => $child_serviceids,
			'editable' => true
		]);

		if ($count != count($child_serviceids)) {
			self::exception(ZBX_API_ERROR_PERMISSIONS, _('No permissions to referred object or it does not exist!'));
		}
	}

	/**
	 * @param array      $services
	 * @param array|null $db_services

	 * @throws APIException
	 */
	private function checkCircularReferences(array $services, array $db_services = null): void {
		$add_references = [];
		$del_references = [];

		foreach ($services as $service) {
			if ($db_services !== null) {
				$db_service = $db_services[$service['serviceid']];

				if (array_key_exists('parents', $service)) {
					foreach ($db_service['parents'] as $parent) {
						$del_references[$parent['serviceid']][$service['serviceid']] = true;
					}
					foreach ($service['parents'] as $parent) {
						$add_references[$parent['serviceid']][$service['serviceid']] = true;
					}
				}

				if (array_key_exists('children', $service)) {
					foreach ($db_service['children'] as $child) {
						$del_references[$service['serviceid']][$child['serviceid']] = true;
					}
					foreach ($service['children'] as $child) {
						$add_references[$service['serviceid']][$child['serviceid']] = true;
					}
				}
			}
			else if (array_key_exists('parents', $service) && array_key_exists('children', $service)) {
				foreach ($service['children'] as $child) {
					foreach ($service['parents'] as $parent) {
						$add_references[$parent['serviceid']][$child['serviceid']] = true;
					}
				}
			}
		}

		if ($this->hasCircularReferences($add_references, $del_references)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, _('Services form a circular dependency.'));
		}
	}

	/**
	 * @param array $add_references
	 * @param array $del_references

	 * @return bool
	 */
	private function hasCircularReferences(array $add_references, array $del_references): bool {
		$reverse_references = [];

		foreach ($add_references as $child_serviceid => $parents) {
			foreach (array_keys($parents) as $parent_serviceid) {
				$reverse_references[$parent_serviceid][$child_serviceid] = true;
			}
		}

		while ($add_references) {
			$db_links = API::getApiService()->select('services_links', [
				'output' => ['serviceupid', 'servicedownid'],
				'filter' => ['servicedownid' => array_keys($add_references)]
			]);

			$db_parents = [];

			foreach ($db_links as $db_link) {
				if (!array_key_exists($db_link['servicedownid'], $del_references)
						|| !array_key_exists($db_link['serviceupid'], $del_references[$db_link['servicedownid']])) {
					$db_parents[$db_link['servicedownid']][$db_link['serviceupid']] = true;
				}
			}

			$next_references = [];

			foreach ($add_references as $child_serviceid => $parents) {
				foreach (array_keys($parents) as $parent_serviceid) {
					if ((string) $child_serviceid === (string) $parent_serviceid) {
						return true;
					}

					if (array_key_exists($child_serviceid, $reverse_references)) {
						foreach (array_keys($reverse_references[$child_serviceid]) as $serviceid) {
							$next_references[$serviceid][$parent_serviceid] = true;
						}
					}

					if (array_key_exists($child_serviceid, $db_parents)) {
						foreach (array_keys($db_parents[$child_serviceid]) as $serviceid) {
							$next_references[$serviceid][$parent_serviceid] = true;
						}
					}
				}
			}

			$add_references = $next_references;
		}

		return false;
	}

	/**
	 * @param array  $services
	 * @param string $method
	 */
	private function updateTags(array $services, string $method): void {
		$serviceids = [];

		foreach ($services as $serviceid => $service) {
			if (array_key_exists('tags', $service)) {
				$serviceids[$serviceid] = true;
			}
		}

		if (!$serviceids) {
			return;
		}

		$del_tags = [];
		$ins_tags = [];

		if ($method === 'update') {
			$db_tags = API::getApiService()->select('service_tag', [
				'output' => ['servicetagid', 'serviceid', 'tag', 'value'],
				'filter' => ['serviceid' => array_keys($serviceids)]
			]);

			foreach ($db_tags as $db_tag) {
				$del_tags[$db_tag['serviceid']][$db_tag['tag']][$db_tag['value']] = $db_tag['servicetagid'];
			}
		}

		foreach (array_keys($serviceids) as $serviceid) {
			foreach ($services[$serviceid]['tags'] as $tag) {
				if (array_key_exists($serviceid, $del_tags)
						&& array_key_exists($tag['tag'], $del_tags[$serviceid])
						&& array_key_exists($tag['value'], $del_tags[$serviceid][$tag['tag']])) {
					unset($del_tags[$serviceid][$tag['tag']][$tag['value']]);
				}
				else {
					$ins_tags[] = ['serviceid' => $serviceid] + $tag;
				}
			}
		}

		if ($del_tags) {
			$del_servicetagids = [];

			foreach ($del_tags as $del_tags) {
				foreach ($del_tags as $del_tags) {
					foreach ($del_tags as $servicetagid) {
						$del_servicetagids[$servicetagid] = true;
					}
				}
			}

			DB::delete('service_tag', ['servicetagid' => array_keys($del_servicetagids)]);
		}

		if ($ins_tags) {
			DB::insertBatch('service_tag', $ins_tags);
		}
	}

	/**
	 * @param array  $services
	 * @param string $method
	 */
	private function updateParents(array $services, string $method): void {
		$serviceids = [];

		foreach ($services as $serviceid => $service) {
			if (array_key_exists('parents', $service)) {
				$serviceids[$serviceid] = true;
			}
		}

		if (!$serviceids) {
			return;
		}

		$del_parents = [];
		$ins_parents = [];

		if ($method === 'update') {
			$db_parents = API::getApiService()->select('services_links', [
				'output' => ['linkid', 'serviceupid', 'servicedownid'],
				'filter' => ['servicedownid' => array_keys($serviceids)]
			]);

			foreach ($db_parents as $db_parent) {
				$del_parents[$db_parent['servicedownid']][$db_parent['serviceupid']] = $db_parent['linkid'];
			}
		}

		foreach (array_keys($serviceids) as $serviceid) {
			foreach ($services[$serviceid]['parents'] as $parent) {
				if (array_key_exists($serviceid, $del_parents)
						&& array_key_exists($parent['serviceid'], $del_parents[$serviceid])) {
					unset($del_parents[$serviceid][$parent['serviceid']]);
				}
				else {
					$ins_parents[] = ['serviceupid' => $parent['serviceid'], 'servicedownid' => $serviceid];
				}
			}
		}

		if ($del_parents) {
			$del_linkids = [];

			foreach ($del_parents as $del_parents) {
				foreach ($del_parents as $linkid) {
					$del_linkids[$linkid] = true;
				}
			}

			DB::delete('services_links', ['linkid' => array_keys($del_linkids)]);
		}

		if ($ins_parents) {
			DB::insertBatch('services_links', $ins_parents);
		}
	}

	/**
	 * @param array  $services
	 * @param string $method
	 */
	private function updateChildren(array $services, string $method): void {
		$serviceids = [];

		foreach ($services as $serviceid => $service) {
			if (array_key_exists('children', $service)) {
				$serviceids[$serviceid] = true;
			}
		}

		if (!$serviceids) {
			return;
		}

		$del_children = [];
		$ins_children = [];

		if ($method === 'update') {
			$db_children = API::getApiService()->select('services_links', [
				'output' => ['linkid', 'serviceupid', 'servicedownid'],
				'filter' => ['serviceupid' => array_keys($serviceids)]
			]);

			foreach ($db_children as $db_child) {
				$del_children[$db_child['serviceupid']][$db_child['servicedownid']] = $db_child['linkid'];
			}
		}

		foreach (array_keys($serviceids) as $serviceid) {
			foreach ($services[$serviceid]['children'] as $child) {
				if (array_key_exists($serviceid, $del_children)
					&& array_key_exists($child['serviceid'], $del_children[$serviceid])) {
					unset($del_children[$serviceid][$child['serviceid']]);
				}
				else {
					$ins_children[] = ['serviceupid' => $serviceid, 'servicedownid' => $child['serviceid']];
				}
			}
		}

		if ($del_children) {
			$del_linkids = [];

			foreach ($del_children as $del_children) {
				foreach ($del_children as $linkid) {
					$del_linkids[$linkid] = true;
				}
			}

			DB::delete('services_links', ['linkid' => array_keys($del_linkids)]);
		}

		if ($ins_children) {
			DB::insertBatch('services_links', $ins_children);
		}
	}

	/**
	 * @param array  $services
	 * @param string $method
	 */
	private function updateTimes(array $services, string $method): void {
		$serviceids = [];

		foreach ($services as $serviceid => $service) {
			if (array_key_exists('times', $service)) {
				$serviceids[$serviceid] = true;
			}
		}

		if (!$serviceids) {
			return;
		}

		$del_times = [];
		$ins_times = [];
		$upd_times = [];

		if ($method === 'update') {
			$db_times = API::getApiService()->select('services_times', [
				'output' => ['timeid', 'serviceid', 'type', 'ts_from', 'ts_to', 'note'],
				'filter' => ['serviceid' => array_keys($serviceids)]
			]);

			foreach ($db_times as $db_time) {
				$del_times[$db_time['serviceid']][$db_time['type']][$db_time['ts_from']][$db_time['ts_to']] = [
					'timeid' => $db_time['timeid'],
					'fields' => [
						'note' => $db_time['note']
					]
				];
			}
		}

		foreach (array_keys($serviceids) as $serviceid) {
			foreach ($services[$serviceid]['times'] as $time) {
				if (array_key_exists($serviceid, $del_times)
						&& array_key_exists($time['type'], $del_times[$serviceid])
						&& array_key_exists($time['ts_from'], $del_times[$serviceid][$time['type']])
						&& array_key_exists($time['ts_to'], $del_times[$serviceid][$time['type']][$time['ts_from']])) {
					$db_time = $del_times[$serviceid][$time['type']][$time['ts_from']][$time['ts_to']];

					$upd_time = DB::getUpdatedValues('services_times', $time, $db_time['fields']);

					if ($upd_time) {
						$upd_times[] = [
							'values' => $upd_time,
							'where' => ['timeid' => $db_time['timeid']]
						];
					}

					unset($del_times[$serviceid][$time['type']][$time['ts_from']][$time['ts_to']]);
				}
				else {
					$ins_times[] = ['serviceid' => $serviceid] + $time;
				}
			}
		}

		if ($del_times) {
			$del_timeids = [];

			foreach ($del_times as $del_times) {
				foreach ($del_times as $del_times) {
					foreach ($del_times as $del_times) {
						foreach ($del_times as $del_times) {
							$del_timeids[$del_times['timeid']] = true;
						}
					}
				}
			}

			DB::delete('services_times', ['timeid' => array_keys($del_timeids)]);
		}

		if ($ins_times) {
			DB::insertBatch('services_times', $ins_times);
		}

		if ($upd_times) {
			DB::update('services_times', $upd_times);
		}
	}

	// Methods related to an SLA calculation - to be reworked.

	/**
	 * Returns availability-related information about the given services during the given time intervals.
	 *
	 * Available options:
	 *  - serviceids    - a single service ID or an array of service IDs;
	 *  - intervals     - a single time interval or an array of time intervals, each containing:
	 *      - from          - the beginning of the interval, timestamp;
	 *      - to            - the end of the interval, timestamp.
	 *
	 * Returns the following availability information for each service:
	 *  - status            - the current status of the service;
	 *  - problems          - an array of triggers that are currently in problem state and belong to the given service
	 *                        or it's descendants;
	 *  - sla               - an array of requested intervals with SLA information:
	 *      - from              - the beginning of the interval;
	 *      - to                - the end of the interval;
	 *      - okTime            - the time the service was in OK state, in seconds;
	 *      - problemTime       - the time the service was in problem state, in seconds;
	 *      - downtimeTime      - the time the service was down, in seconds.
	 *
	 * If the service calculation algorithm is set to SERVICE_ALGORITHM_NONE, the method will return an empty 'problems'
	 * array and null for all of the calculated values.
	 *
	 * @param array $options
	 *
	 * @return array    as array(serviceId2 => data1, serviceId2 => data2, ...)
	 */
	public function getSla(array $options) {
		$serviceIds = (isset($options['serviceids'])) ? zbx_toArray($options['serviceids']) : null;
		$intervals = (isset($options['intervals'])) ? zbx_toArray($options['intervals']) : [];

		// fetch services
		$services = $this->get([
			'output' => ['serviceid', 'name', 'status', 'algorithm'],
			'selectTimes' => API_OUTPUT_EXTEND,
			'selectParents' => ['serviceid'],
			'serviceids' => $serviceIds,
			'preservekeys' => true
		]);

		$rs = [];
		if ($services) {
			$usedSeviceIds = [];

			$problemServiceIds = [];
			foreach ($services as &$service) {
				// don't calculate SLA for services with disabled status calculation
				if ($this->isStatusEnabled($service)) {
					$usedSeviceIds[$service['serviceid']] = $service['serviceid'];
					$service['alarms'] = [];

					if ($service['status'] > 0) {
						$problemServiceIds[] = $service['serviceid'];
					}
				}
			}
			unset($service);

			// initial data
			foreach ($services as $service) {
				$rs[$service['serviceid']] = [
					'status' => ($this->isStatusEnabled($service)) ? $service['status'] : null,
					'problems' => [],
					'sla' => []
				];
			}

			if ($usedSeviceIds) {
				// add service alarms
				if ($intervals) {
					$intervalConditions = [];
					foreach ($intervals as $interval) {
						$intervalConditions[] = 'sa.clock BETWEEN '.zbx_dbstr($interval['from']).' AND '.zbx_dbstr($interval['to']);
					}
					$query = DBselect(
						'SELECT *'.
						' FROM service_alarms sa'.
						' WHERE '.dbConditionInt('sa.serviceid', $usedSeviceIds).
						' AND ('.implode(' OR ', $intervalConditions).')'.
						' ORDER BY sa.servicealarmid'
					);
					while ($data = DBfetch($query)) {
						$services[$data['serviceid']]['alarms'][] = $data;
					}
				}

				// add problem triggers
				if ($problemServiceIds) {
					$problemTriggers = $this->fetchProblemTriggers($problemServiceIds);
					$rs = $this->escalateProblems($services, $problemTriggers, $rs);
				}

				$slaCalculator = new CServicesSlaCalculator();

				// calculate SLAs
				foreach ($intervals as $interval) {
					$latestValues = $this->fetchLatestValues($usedSeviceIds, $interval['from']);

					foreach ($services as $service) {
						$serviceId = $service['serviceid'];

						// only calculate the sla for services which require it
						if (isset($usedSeviceIds[$serviceId])) {
							$latestValue = (isset($latestValues[$serviceId])) ? $latestValues[$serviceId] : 0;
							$intervalSla = $slaCalculator->calculateSla($service['alarms'], $service['times'],
								$interval['from'], $interval['to'], $latestValue
							);
						}
						else {
							$intervalSla = [
								'ok' => null,
								'okTime' => null,
								'problemTime' => null,
								'downtimeTime' => null
							];
						}

						$rs[$service['serviceid']]['sla'][] = [
							'from' => $interval['from'],
							'to' => $interval['to'],
							'sla' => $intervalSla['ok'],
							'okTime' => $intervalSla['okTime'],
							'problemTime' => $intervalSla['problemTime'],
							'downtimeTime' => $intervalSla['downtimeTime']
						];
					}
				}
			}
		}

		return $rs;
	}

	/**
	 * Returns true if status calculation is enabled for the given service.
	 *
	 * @param array $service
	 *
	 * @return bool
	 */
	protected function isStatusEnabled(array $service) {
		return ($service['algorithm'] != SERVICE_ALGORITHM_NONE);
	}

	/**
	 * Returns an array of triggers which are in a problem state and are linked to the given services.
	 *
	 * @param array $serviceIds
	 *
	 * @return array    in the form of array(serviceId1 => array(triggerId => trigger), ...)
	 */
	protected function fetchProblemTriggers(array $serviceIds) {
		$sql = 'SELECT s.serviceid,t.triggerid'.
			' FROM services s,triggers t'.
			' WHERE s.status>0'.
			' AND t.triggerid=s.triggerid'.
			' AND '.dbConditionInt('s.serviceid', $serviceIds).
			' ORDER BY s.status DESC,t.description';

		// get service reason
		$triggers = DBfetchArray(DBSelect($sql));

		$rs = [];
		foreach ($triggers as $trigger) {
			$serviceId = $trigger['serviceid'];
			unset($trigger['serviceid']);

			$rs[$serviceId] = [$trigger['triggerid'] => $trigger];
		}

		return $rs;
	}

	/**
	 * Escalates the problem triggers from the child services to their parents and adds them to $slaData.
	 * The escalation will stop if a service has status calculation disabled or is in OK state.
	 *
	 * @param array $services
	 * @param array $serviceProblems    an array of service triggers defines as
	 *                                  array(serviceId1 => array(triggerId => trigger), ...)
	 * @param array $slaData
	 *
	 * @return array
	 */
	protected function escalateProblems(array $services, array $serviceProblems, array $slaData) {
		$parentProblems = [];
		foreach ($serviceProblems as $serviceId => $problemTriggers) {
			$service = $services[$serviceId];

			// add the problem trigger of the current service to the data
			$slaData[$serviceId]['problems'] = zbx_array_merge($slaData[$serviceId]['problems'], $problemTriggers);

			// add the same trigger to the parent services
			foreach ($service['parents'] as $parent) {
				$parentServiceId = $parent['serviceid'];

				if (isset($services[$parentServiceId])) {
					$parentService = $services[$parentServiceId];

					// escalate only if status calculation is enabled for the parent service and it's in problem state
					if ($this->isStatusEnabled($parentService) && $parentService['status']) {
						if (!isset($parentProblems[$parentServiceId])) {
							$parentProblems[$parentServiceId] = [];
						}
						$parentProblems[$parentServiceId] = zbx_array_merge($parentProblems[$parentServiceId], $problemTriggers);
					}
				}
			}
		}

		// propagate the problems to the parents
		if ($parentProblems) {
			$slaData = $this->escalateProblems($services, $parentProblems, $slaData);
		}

		return $slaData;
	}

	/**
	 * Returns the value of the latest service alarm before the given time.
	 *
	 * @param array $serviceIds
	 * @param int $beforeTime
	 *
	 * @return array
	 */
	protected function fetchLatestValues(array $serviceIds, $beforeTime) {
		// The query will return the alarms with the latest servicealarmid for each service, before $beforeTime.
		$query = DBSelect(
			'SELECT sa.serviceid,sa.value'.
			' FROM (SELECT sa2.serviceid,MAX(sa2.servicealarmid) AS servicealarmid'.
			' FROM service_alarms sa2'.
			' WHERE sa2.clock<'.zbx_dbstr($beforeTime).
			' AND '.dbConditionInt('sa2.serviceid', $serviceIds).
			' GROUP BY sa2.serviceid) ss2'.
			' JOIN service_alarms sa ON sa.servicealarmid = ss2.servicealarmid'
		);
		$rs = [];
		while ($alarm = DBfetch($query)) {
			$rs[$alarm['serviceid']] = $alarm['value'];
		}

		return $rs;
	}
}
