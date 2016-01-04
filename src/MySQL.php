<?php

/**
 * This file is part of the Obo framework for application domain logic.
 * Obo framework is based on voluntary contributions from different developers.
 *
 * @link https://github.com/obophp/data-storage-dibi
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

namespace obo\DataStorage;

class MySQL extends \obo\Object implements \obo\Interfaces\IDataStorage {

    const PROCESS_SELECT = "select";
    const PROCESS_WHERE = "where";
    const PROCESS_ORDER_BY = "orderBy";
    const PROCESS_JOIN = "join";

    /**
     * @var \DibiConnection
     */
    protected $dibiConnection = null;

    /**
     * @var \DibiTranslator
     */
    protected $dibiTranslator = null;

    /**
     * @var \obo\DataStorage\Interfaces\IDataConverter
     */
    protected $dataConverter = null;

    /**
     *  @var \obo\Interfaces\ICache
     */
    protected $cache = null;

    /**
     * @var \obo\Carriers\EntityInformationCarrier[]
     */
    protected $informations = null;

    /**
     * @var string
     */
    protected $parameterPlaceholder = \obo\Interfaces\IQuerySpecification::PARAMETER_PLACEHOLDER;

    /**
     * @param \DibiConnection $dibiConnection
     * @param \obo\DataStorage\Interfaces\IDataConverter $dataConverter
     * @param \obo\Interfaces\ICache $cache
     * @throws \obo\Exceptions\Exception
     */
    public function __construct(\DibiConnection $dibiConnection, \obo\DataStorage\Interfaces\IDataConverter $dataConverter, \obo\Interfaces\ICache $cache = null) {
        if ($dibiConnection->getConfig("driver") !== "mysqli" AND $dibiConnection->getConfig("driver") !== "mysql") throw new \obo\Exceptions\Exception("Wrong driver has been set for dibi connection. Mysql or mysqli driver was expected.");
        $this->dibiConnection = $dibiConnection;
        $this->dibiTranslator = new \DibiTranslator($this->dibiConnection);
        $this->dataConverter = $dataConverter;
        $this->cache = $cache;
    }

    /**
     * @return \DibiConnection
     */
    public function getDibiConnection() {
        return $this->dibiConnection;
    }

    /**
     * @param \obo\Carriers\QueryCarrier $queryCarrier
     * @param boolean $asArray
     * @return string
     * @throws \DibiException
     * @throws \DibiPcreException
     * @throws \obo\Exceptions\AutoJoinException
     * @throws \obo\Exceptions\Exception
     * @throws \obo\Exceptions\PropertyNotFoundException
     */
    public function constructQuery(\obo\Carriers\QueryCarrier $queryCarrier, $asArray = false) {
        if ($queryCarrier->getDefaultEntityClassName() === null) throw new \obo\Exceptions\Exception("Default entity hasn't been set for QueryCarrier");
        $query = "";
        $data = array();
        $queryCarrier = clone $queryCarrier;
        $joins = array();
        $needDistinct = false;
        $entityInformation = $queryCarrier->getDefaultEntityEntityInformation();
        $repositoryName = $entityInformation->repositoryName;
        $primaryPropertyColumn = $entityInformation->informationForPropertyWithName($entityInformation->primaryPropertyName)->columnName;
        $select = $queryCarrier->getSelect();
        $where = $queryCarrier->getWhere();
        $orderBy = $queryCarrier->getOrderBy();
        $join = $queryCarrier->getJoin();

        $needDistinct = $this->process($queryCarrier->getDefaultEntityClassName(), $select, $joins, self::PROCESS_SELECT) || $needDistinct;
        $needDistinct = $this->process($queryCarrier->getDefaultEntityClassName(), $where, $joins, self::PROCESS_WHERE) || $needDistinct;
        $needDistinct = $this->process($queryCarrier->getDefaultEntityClassName(), $orderBy, $joins, self::PROCESS_ORDER_BY) || $needDistinct;
        $needDistinct = $this->process($queryCarrier->getDefaultEntityClassName(), $join, $joins, self::PROCESS_JOIN) || $needDistinct;

        if ("COUNT([{$repositoryName}].[{$primaryPropertyColumn}])" === $select["query"]) {
            $query .= "SELECT COUNT(" . ($needDistinct ? "DISTINCT " : "") ."[{$repositoryName}].[{$primaryPropertyColumn}])";
        } else {
            $query .= "SELECT " . ($needDistinct ? "DISTINCT " : "") . rtrim($select["query"], ",");
        }

        $data = \array_merge($data, $select["data"]);

        if ($queryCarrier->getFrom()["query"] === "") {
            $query .= " FROM [{$repositoryName}]";
        } else {
            $query .= " FROM " . rtrim($queryCarrier->getFrom()["query"],",");
            $data = \array_merge($data, $queryCarrier->getFrom()["data"]);
        }

        $query .= implode($joins, " ");

        if ($where["query"] !== "") {
            $query .= " WHERE " . \preg_replace("#^ *(AND|OR) *#i", "", $where["query"]);
            $data = \array_merge($data, $where["data"]);
        }

        if ($orderBy["query"] !== "") {
            $query .= " ORDER BY " . rtrim($orderBy["query"], ",");
            $data = \array_merge($data, $orderBy["data"]);
        }

        if ($queryCarrier->getLimit()["query"] !== "") {
            $query .= " LIMIT " . $queryCarrier->getLimit()["query"];
            $data = \array_merge($data, $queryCarrier->getLimit()["data"]);
        }

        if ($queryCarrier->getOffset()["query"] !== "") {
            $query .= " OFFSET " . $queryCarrier->getOffset()["query"];
            $data = \array_merge($data, $queryCarrier->getOffset()["data"]);
        }

        if ($asArray) return \array_merge([$query], $data);

        return $this->dibiTranslator->translate(\array_merge([$query], $data));
    }

    /**
     * @param \obo\Carriers\QueryCarrier $queryCarrier
     * @return array
     */
    public function dataForQuery(\obo\Carriers\QueryCarrier $queryCarrier) {
        return $this->convertDataForExport($this->dibiConnection->fetchAll($this->constructQuery($queryCarrier, true)), $queryCarrier->getDefaultEntityEntityInformation());
    }

    /**
     * @param \obo\Carriers\QueryCarrier $queryCarrier
     * @return int
     */
    public function countRecordsForQuery(\obo\Carriers\QueryCarrier $queryCarrier) {
        $queryCarrier = clone $queryCarrier;
        $entityInformation = $queryCarrier->getDefaultEntityEntityInformation();
        $repositoryName = $entityInformation->repositoryName;
        $primaryPropertyColumn = $entityInformation->informationForPropertyWithName($entityInformation->primaryPropertyName)->columnName;
        $queryCarrier->rewriteSelect("COUNT([{$repositoryName}].[{$primaryPropertyColumn}])");
        return (int) $this->dibiConnection->fetchSingle($this->constructQuery($queryCarrier, true));
    }

    /**
     * @param \obo\Entity $entity
     * @throws \obo\Exceptions\Exception
     */
    public function insertEntity(\obo\Entity $entity) {
        if ($entity->isBasedInRepository()) throw new \obo\Exceptions\Exception("Can't insert entity into storage. Entity is already persisted.");
        $convertedData = $this->convertDataForImport($entity->changedProperties($entity->entityInformation()->persistablePropertiesNames, true, true), $entity->entityInformation());
        $informationForEntity = $this->informationForEntity($entity->entityInformation());
        $primaryPropertyColumnName = $entity->entityInformation()->informationForPropertyWithName($entity->entityInformation()->primaryPropertyName)->columnName;

        if (count($convertedData) > 1 AND $informationForEntity["transactionEnabled"]) $this->dibiConnection->begin();
        $lastInsertId = null;
        foreach ($convertedData as $repositoryName => $data) {
            if ($lastInsertId) $data[$primaryPropertyColumnName] = $lastInsertId;
            $this->dibiConnection->query("INSERT INTO [{$repositoryName}] ", $data);
            if (!$lastInsertId) $lastInsertId = $this->dibiConnection->getInsertId();
        }
        if (count($convertedData) > 1 AND $informationForEntity["transactionEnabled"]) $this->dibiConnection->commit();

        if ($entity->entityInformation()->informationForPropertyWithName($entity->entityInformation()->primaryPropertyName)->autoIncrement) $entity->setValueForPropertyWithName($lastInsertId, $entity->entityInformation()->primaryPropertyName);
    }

    /**
     * @param \obo\Entity $entity
     * @return void
     */
    public function updateEntity(\obo\Entity $entity) {
        $primaryPropertyName = $entity->entityInformation()->primaryPropertyName;
        $primaryPropertyColumnName = $entity->informationForPropertyWithName($primaryPropertyName)->columnName;
        $informationForEntity = $this->informationForEntity($entity->entityInformation());
        $primaryPropertyPlaceholder = $informationForEntity["repositories"][$entity->entityInformation()->repositoryName]["columns"][$primaryPropertyColumnName]["placeholder"];
        $changedProperties = $entity->changedProperties($entity->entityInformation()->persistablePropertiesNames, true, true);
        $convertedData = $this->convertDataForImport($changedProperties, $entity->entityInformation());
        if (count($convertedData) > 1 AND $informationForEntity["transactionEnabled"]) $this->dibiConnection->begin();
        foreach ($convertedData as $repositoryName => $data) {
            $this->dibiConnection->query("UPDATE [{$repositoryName}] SET %a", $data, "WHERE [{$repositoryName}].[{$primaryPropertyColumnName}] = {$primaryPropertyPlaceholder}", $entity->primaryPropertyValue());
        }
        if (count($convertedData) > 1 AND $informationForEntity["transactionEnabled"]) $this->dibiConnection->commit();
    }

    /**
     * @param \obo\Entity $entity
     * @return void
     */
    public function removeEntity(\obo\Entity $entity) {
        $primaryPropertyColumnName = $entity->informationForPropertyWithName($entity->entityInformation()->primaryPropertyName)->columnName;
        $informationForEntity = $this->informationForEntity($entity->entityInformation());
        $primaryPropertyPlaceholder = $informationForEntity["repositories"][$entity->entityInformation()->repositoryName]["columns"][$primaryPropertyColumnName]["placeholder"];
        if (count($informationForEntity["repositories"]) > 1 AND $informationForEntity["transactionEnabled"]) $this->dibiConnection->begin();
        foreach ($informationForEntity["repositories"] as $repositoryName => $foo) {
            $this->dibiConnection->query("DELETE FROM [{$repositoryName}] WHERE [{$repositoryName}].[{$primaryPropertyColumnName}] = {$primaryPropertyPlaceholder} LIMIT 1", $entity->primaryPropertyValue());
        }
        if (count($informationForEntity["repositories"]) > 1 AND $informationForEntity["transactionEnabled"]) $this->dibiConnection->commit();
    }

    /**
     * @param \obo\Carriers\QueryCarrier $specification
     * @param string $repositoryName
     * @param \obo\Entity $owner
     * @param string $targetEntity
     * @return int
     */
    public function countEntitiesInRelationship(\obo\Carriers\QueryCarrier $specification, $repositoryName, \obo\Entity $owner, $targetEntity) {
        return $this->countRecordsForQuery($this->constructJoinQueryForRelationship($specification, $repositoryName, $owner, $targetEntity), $targetEntity::entityInformation()->primaryPropertyName);
    }

    /**
     * @param \obo\Carriers\QueryCarrier $specification
     * @param string $repositoryName
     * @param \obo\Entity $owner
     * @param string $targetEntity
     * @return array
     */
    public function dataForEntitiesInRelationship(\obo\Carriers\QueryCarrier $specification, $repositoryName, \obo\Entity $owner, $targetEntity) {
        return $this->dataForQuery($this->constructJoinQueryForRelationship($specification, $repositoryName, $owner, $targetEntity));
    }

    /**
     * @param \obo\Carriers\QueryCarrier $specification
     * @param string $repositoryName
     * @param \obo\Entity $owner
     * @param string $targetEntity
     */
    protected function constructJoinQueryForRelationship(\obo\Carriers\QueryCarrier $specification, $repositoryName, \obo\Entity $owner, $targetEntity) {
        $targetEntityPropertyNameForSoftDelete = $targetEntity::entityInformation()->propertyNameForSoftDelete;

        if ($targetEntityPropertyNameForSoftDelete === "") {
            $specification->join("JOIN [{$repositoryName}] ON [{$owner->entityInformation()->repositoryName}] = " . $this->informationForEntity($owner->entityInformation())["columns"][$owner->entityInformation()->informationForPropertyWithName($owner->entityInformation()->primaryPropertyName)->columnName]["placeholder"] . " AND [{$targetEntity::entityInformation()->repositoryName}] = [{$targetEntity::informationForPropertyWithName($targetEntity::entityInformation()->primaryPropertyName)->columnName}]", $owner->primaryPropertyValue());
        } else {
            $softDeleteJoinQuery = "AND [{$targetEntity::entityInformation()->repositoryName}].[{$targetEntity::informationForPropertyWithName($targetEntityPropertyNameForSoftDelete)->columnName}] = %b";
            $specification->join("JOIN [{$repositoryName}] ON [{$owner->entityInformation()->repositoryName}] = " . $this->informationForEntity($owner->entityInformation())["columns"][$owner->entityInformation()->informationForPropertyWithName($owner->entityInformation()->primaryPropertyName)->columnName]["placeholder"] . " AND [{$targetEntity::entityInformation()->repositoryName}] = [{$targetEntity::informationForPropertyWithName($targetEntity::entityInformation()->primaryPropertyName)->columnName}]" . $softDeleteJoinQuery, $owner->primaryPropertyValue(), FALSE);
        }
    }

    /**
     * @param string $repositoryName
     * @param \obo\Entity[]
     * @return void
     * @throws \obo\Exceptions\Exception
     */
    public function createRelationshipBetweenEntities($repositoryName, array $entities) {

        if (\obo\obo::$developerMode) {
            if (!$this->existsRepositoryWithName($repositoryName)) throw new \obo\Exceptions\Exception("Relationship can't be created. Repository with the name '{$repositoryName}' does not exist.");
            if (\count($entities) !== 2) throw new \obo\Exceptions\Exception("Relationship can't be created. Two entities were expected but " . \count($entities) . " given.");

            foreach ($entities as $entity) {
                if (!$entity instanceof \obo\Entity) throw new \obo\Exceptions\Exception("Relationship can't be created. Entities must be of \obo\Entity instance");
            }
        }

        $this->dibiConnection->query("INSERT INTO [{$repositoryName}] ", [$entities[0]->entityInformation()->repositoryName => $entities[0]->primaryPropertyValue(), $entities[1]->entityInformation()->repositoryName => $entities[1]->primaryPropertyValue()]);
    }

    /**
     * @param string $repositoryName
     * @param array $entities
     * @return void
     * @throws \obo\Exceptions\Exception
     */
    public function removeRelationshipBetweenEntities($repositoryName, array $entities) {

        if (\obo\obo::$developerMode) {
            if (!$this->existsRepositoryWithName($repositoryName)) throw new \obo\Exceptions\Exception("Relationship can't deleted repository with the name '{$repositoryName}' does not exist");
            if (\count($entities) !== 2) throw new \obo\Exceptions\Exception("Relationship can't be deleted. Two entities were expected but " . \count($entities) . " given. ");

            foreach ($entities as $entity) {
                if (!$entity instanceof \obo\Entity) throw new \obo\Exceptions\Exception("Relationship can't be deleted. Entities must be of \obo\Entity instance.");
            }
        }

        $this->dibiConnection->query("DELETE FROM [{$repositoryName}] WHERE [{$entities[0]->entityInformation()->repositoryName}] = {$entities[0]->primaryPropertyValue()} AND [{$entities[1]->entityInformation()->repositoryName}] = " . $this->informationForEntity($entities[1]->entityInformation())["repositories"][$entities[1]->entityInformation()->repositoryName]["columns"][$entities[1]->entityInformation()->informationForPropertyWithName($entities[1]->entityInformation()->primaryPropertyName)->columnName]["placeholder"], $entities[1]->primaryPropertyValue());
    }

    /**
     * @param \obo\Carriers\EntityInformationCarrier $entityInformation
     * @return array
     */
    public function informationForEntity(\obo\Carriers\EntityInformationCarrier $entityInformation) {
        return isset($this->informations[$entityInformation->className]) ? $this->informations[$entityInformation->className] : $this->loadInformationForEntity($entityInformation);
    }

    /**
     * @param \obo\Carriers\EntityInformationCarrier $entityInformation
     * @return array
     */
    protected function loadInformationForEntity(\obo\Carriers\EntityInformationCarrier $entityInformation) {
        if (\obo\obo::$developerMode OR $this->cache === null) {
            $information = $this->createInformationForEntity($entityInformation);
        } else {
            if (null === ($information = $this->cache->load($entityInformation->className))) {
                $this->cache->store($entityInformation->className, $information = $this->createInformationForEntity($entityInformation));
            }
        }

        return $this->informations[$entityInformation->className] = $information;
    }

    /**
     * @param \obo\Carriers\EntityInformationCarrier $entityInformation
     * @return array
     * @throws \obo\Exceptions\Exception
     * @throws \obo\Exceptions\PropertyNotFoundException
     */
    protected function createInformationForEntity(\obo\Carriers\EntityInformationCarrier $entityInformation) {
        $information = [
            "repositories" => [
                $entityInformation->repositoryName => [
                    "columns" => [],
                    "status" => [],
                ],
            ],
            "transactionEnabled" => true,
        ];

        $this->loadColumnsForRepository($entityInformation->repositoryName, $information);
        $this->loadStatusForRepository($entityInformation->repositoryName, $information);

        foreach ($entityInformation->persistablePropertiesNames as $persistablePropertyName) {
            $propertyInformation = $entityInformation->informationForPropertyWithName($persistablePropertyName);
            $propertyRepositoryName = $propertyInformation->repositoryName ?: $entityInformation->repositoryName;
            if (!isset($information["repositories"][$propertyRepositoryName])) {
                $this->loadColumnsForRepository($propertyRepositoryName, $information);
                $this->loadStatusForRepository($propertyRepositoryName, $information);
            }
            if (!isset($information["repositories"][$propertyRepositoryName]["columns"][$propertyInformation->columnName])) throw new \obo\Exceptions\Exception("Column '{$propertyInformation->columnName}' does not exist for persistable property '{$persistablePropertyName}' in table '{$propertyRepositoryName}'");
            $information["repositories"][$propertyRepositoryName]["columns"][$propertyInformation->columnName]["propertyName"] = $propertyInformation->name;
            $information["repositories"][$propertyRepositoryName]["columns"][$propertyInformation->columnName]["nullable"] = $propertyInformation->nullable;
            $information["repositories"][$propertyRepositoryName]["columns"][$propertyInformation->columnName]["autoIncrement"] = $propertyInformation->autoIncrement;
            $information["repositories"][$propertyRepositoryName]["columns"][$propertyInformation->columnName]["exportFilter"] = $this->dataConverter->convertFilterForCombinationCode("D" . $information["repositories"][$propertyRepositoryName]["columns"][$propertyInformation->columnName]["type"] . "->O" . $propertyInformation->dataType->dataTypeClass());
            $information["repositories"][$propertyRepositoryName]["columns"][$propertyInformation->columnName]["importFilter"] = $this->dataConverter->convertFilterForCombinationCode("O" . $propertyInformation->dataType->dataTypeClass() . "->D" . $information["repositories"][$propertyRepositoryName]["columns"][$propertyInformation->columnName]["type"]);
            $information["repositories"][$propertyRepositoryName]["toColumnName"][$propertyInformation->name] = $propertyInformation->columnName;
            if (!$information["transactionEnabled"] OR $information["repositories"][$propertyRepositoryName]["status"]["Engine"] !== "InnoDB") $information["transactionEnabled"] = false;
        }

        return $this->informations[$entityInformation->className] = $information;
    }

    /**
     * @param string $repositoryName
     * @param array $information
     */
    protected function loadColumnsForRepository($repositoryName, array &$information) {
        foreach ($this->dibiConnection->fetchAll("SHOW COLUMNS FROM [{$repositoryName}]") as $row) {
            $information["repositories"][$repositoryName]["columns"][$row->Field] = [
                "field" => $row->Field,
                "type" => $type = preg_replace("#[^a-z]+.*$#", "", $row->Type),
                "placeholder" => $this->placeholderForColumnType($type),
                "null" => $row->Null,
                "key" => $row->Key,
                "default" => $row->Default,
                "extra" => $row->Extra,
            ];
        }
    }

    /**
     * @param string $repositoryName
     * @param array $information
     */
    protected function loadStatusForRepository($repositoryName, array &$information) {
        $information["repositories"][$repositoryName]["status"] = $this->dibiConnection->fetch("SHOW TABLE STATUS WHERE [name] = %s", $repositoryName)->toArray();
    }

    /**
     * @param array $data
     * @param \obo\Carriers\EntityInformationCarrier $entityInformation
     * @return array
     */
    protected function convertDataForExport(array $data, \obo\Carriers\EntityInformationCarrier $entityInformation) {
        $convertedData = [];
        $defaultEntityInformation = $entityInformation;

        foreach ($data as $row) {
            $convertedRow = [];
            $nullEntities = [];
            foreach ($row as $columnName => $columnValue) {
                if ($defaultEntityInformation->existInformationForPropertyWithName($columnName)) {
                    $parts = [$columnName];
                } else {
                    $parts = \explode("_", $columnName);
                }

                foreach ($parts as $position => $property) {
                    if ($position !== 0 AND $defaultEntityInformation->informationForPropertyWithName($parts[$position-1])->relationship !== null) {
                        $connectedEntity = $defaultEntityInformation->informationForPropertyWithName($parts[$position-1])->relationship->entityClassNameToBeConnected;
                        $defaultEntityInformation = $connectedEntity::entityInformation();
                    }

                    if ($defaultEntityInformation->primaryPropertyName === $property AND $columnValue === null) $nullEntities[$parts[$position-1]] = $parts[$position-1];
                }

                $information = $this->informationForEntity($defaultEntityInformation);
                $propertyInformation = $defaultEntityInformation->informationForPropertyWithName($property);
                $propertyRepositoryName = $propertyInformation->repositoryName ?: $defaultEntityInformation->repositoryName;
                $propertyInformationArray = $information["repositories"][$propertyRepositoryName]["columns"][$information["repositories"][$propertyRepositoryName]["toColumnName"][$property]];
                $convertedRow[$columnName] = ($propertyInformationArray["exportFilter"] === null OR ($columnValue === null AND $propertyInformationArray["nullable"])) ? $columnValue : $this->dataConverter->{$propertyInformationArray["exportFilter"]}($columnValue);
                $defaultEntityInformation = $entityInformation;
            }

            foreach ($nullEntities as $nullEntity) {
                $convertedRow = \array_intersect_key($convertedRow, \array_flip(\array_filter(\array_keys($convertedRow), function($key) use ($nullEntity) {return \strpos($key, "_" . $nullEntity. "_") === false;})));
            }

            $convertedData[] = $convertedRow;
        }

        return $convertedData;
    }

    /**
     * @param array $data
     * @param \obo\Carriers\EntityInformationCarrier $entityInformation
     * @return array
     */
    protected function convertDataForImport(array $data, \obo\Carriers\EntityInformationCarrier $entityInformation) {
        $convertedData = [];

        $information = $this->informationForEntity($entityInformation);

        foreach ($data as $propertyName => $propertyValue) {
            $propertyInformation = $entityInformation->informationForPropertyWithName($propertyName);
            $propertyRepositoryName = $propertyInformation->repositoryName ?: $entityInformation->repositoryName;

            if ($information["repositories"][$propertyRepositoryName]["columns"][$information["repositories"][$propertyRepositoryName]["toColumnName"][$propertyName]]["autoIncrement"]) continue;
            $convertedData[$propertyRepositoryName][$information["repositories"][$propertyRepositoryName]["toColumnName"][$propertyName]] = ($information["repositories"][$propertyRepositoryName]["columns"][$information["repositories"][$propertyRepositoryName]["toColumnName"][$propertyName]]["importFilter"] === null OR $propertyValue === null) ? $propertyValue : $this->dataConverter->{$information["repositories"][$propertyRepositoryName]["columns"][$information["repositories"][$propertyRepositoryName]["toColumnName"][$propertyName]]["importFilter"]}($propertyValue);
        }

        return $convertedData;
    }

    /**
     * @param string $repositoryName
     * @return boolean
     */
    protected function existsRepositoryWithName($repositoryName) {
        return (boolean) $this->dibiConnection->fetchSingle("SHOW TABLES LIKE %s;", $repositoryName);
    }

    protected function placeholderForColumnType($columnType) {
        switch ($columnType) {
            case "int":
            case "tinyint":
            case "smallint":
            case "mediumint":
            case "bigint":
                return "%i";
            case "float":
            case "double":
            case "decimal":
                return "%f";
            case "date":
            case "datetime":
            case "timestamp":
            case "time":
            case "year":
                return "%t";
            case "char":
            case "varchar":
            case "text":
            case "tinytext":
            case "mediumtext":
            case "longtext":
            case "enum":
                return "%s";
            case "blob":
            case "tinyblob":
            case "mediumblob":
            case "longblob":
                return "%bin";

            default:
                throw new \obo\Exceptions\Exception("There is no placeholder for column type '{$columnType}'");
        }
    }

    /**
     * @param string $defaultEntityClassName
     * @param array $part
     * @param array $joins
     * @param int $type
     * @return bool
     * @throws \obo\Exceptions\AutoJoinException
     */
    protected function process($defaultEntityClassName, array &$part, array &$joins, $type) {
        $needDistinct = false;
        $originalDefaultEntityClassName = $defaultEntityClassName;
        self::processJunctions($part["query"], $joins, $defaultEntityClassName, $type);
        \preg_match_all("#(\{(.*?)\}\.?)+#", $part["query"], $blocks);

        foreach ($blocks[0] as $block) {
            $defaultEntityClassName = $originalDefaultEntityClassName;
            $joinKey = null;
            $selectItemAlias = null;
            $ownerRepositoryName = $defaultEntityClassName::entityInformation()->repositoryName;

            $items = \explode("}.{", trim($block, "{}"));
            if (($countItems = count($items)) > 1) {
                $selectItemAlias = null;

                foreach ($items as $key => $item) {
                    $defaultPropertyInformation = $defaultEntityClassName::informationForPropertyWithName($item);
                    $ownerRepositoryName = $defaultPropertyInformation->repositoryName ?: $ownerRepositoryName;
                    if (($defaultPropertyInformation->relationship) === null OR $key + 1 === count($items)) break;
                    if (isset($defaultPropertyInformation->relationship->entityClassNameToBeConnectedInPropertyWithName) AND $defaultPropertyInformation->relationship->entityClassNameToBeConnectedInPropertyWithName)
                        throw new \obo\Exceptions\AutoJoinException("Functionality autojoin can't be used in non-static relationship ONE for property with name '{$defaultPropertyInformation->name}'");

                    $defaultEntityInformation = $defaultEntityClassName::entityInformation();

                    if ($defaultPropertyInformation->relationship instanceof \obo\Relationships\One AND ($countItems - 1) !== $key) {
                        $entityClassNameToBeConnected = $defaultPropertyInformation->relationship->entityClassNameToBeConnected;
                        $entityInformationToBeConnected = $entityClassNameToBeConnected::entityInformation();
                        $joinKey = "{$defaultEntityClassName}->{$entityClassNameToBeConnected}";
                        $selectItemAlias .= "{$item}_";


                        if ($defaultPropertyInformation->relationship->connectViaProperty AND $defaultPropertyInformation->relationship->ownerNameInProperty) {
                            $foreignKey[0] = $defaultPropertyInformation->relationship->connectViaProperty;
                            $foreignKey[1] = $defaultPropertyInformation->relationship->ownerNameInProperty;

                            $join = self::oneInverseDynamicRelationshipJoinQuery(
                                $entityInformationToBeConnected->repositoryName, //$ownedRepositoryName
                                $joinKey, //$joinKey
                                $ownerRepositoryName, //$ownerRepositoryName
                                $defaultEntityInformation->name, //$ownerName
                                $foreignKey, //$foreignKey
                                $entityClassNameToBeConnected::informationForPropertyWithName($entityInformationToBeConnected->primaryPropertyName)->columnName, //$ownedEntityPrimaryColumnName
                                $entityInformationToBeConnected->propertyNameForSoftDelete ? $entityInformationToBeConnected->informationForPropertyWithName($entityInformationToBeConnected->propertyNameForSoftDelete)->columnName : null //$propertyNameForSoftDelete
                            );
                        } elseif ($defaultPropertyInformation->relationship->connectViaProperty) {
                            $join = self::oneInverseRelationshipJoinQuery(
                                $entityInformationToBeConnected->repositoryName, //$ownedRepositoryName
                                $joinKey, //$joinKey
                                $ownerRepositoryName, //$ownerRepositoryName
                                $defaultPropertyInformation->relationship->connectViaProperty, //$foreignKeyColumnName
                                $entityClassNameToBeConnected::informationForPropertyWithName($entityInformationToBeConnected->primaryPropertyName)->columnName, //$ownedEntityPrimaryColumnName
                                $entityInformationToBeConnected->propertyNameForSoftDelete ? $entityInformationToBeConnected->informationForPropertyWithName($entityInformationToBeConnected->propertyNameForSoftDelete)->columnName : null //$propertyNameForSoftDelete
                            );
                        } else {
                            $join = self::oneRelationshipJoinQuery(
                                $entityInformationToBeConnected->repositoryName, //$ownedRepositoryName
                                $joinKey, //$joinKey
                                $ownerRepositoryName, //$ownerRepositoryName
                                $defaultEntityInformation->propertiesInformation[$defaultPropertyInformation->relationship->ownerPropertyName]->columnName, //$foreignKeyColumnName
                                $entityClassNameToBeConnected::informationForPropertyWithName($entityInformationToBeConnected->primaryPropertyName)->columnName, //$ownedEntityPrimaryColumnName
                                $entityInformationToBeConnected->propertyNameForSoftDelete ? $entityInformationToBeConnected->informationForPropertyWithName($entityInformationToBeConnected->propertyNameForSoftDelete)->columnName : null //$propertyNameForSoftDelete
                            );
                        }
                    }

                    if ($defaultPropertyInformation->relationship instanceof \obo\Relationships\Many) {
                        $needDistinct = true;
                        $entityClassNameToBeConnected = $defaultPropertyInformation->relationship->entityClassNameToBeConnected;
                        $entityInformationToBeConnected = $entityClassNameToBeConnected::entityInformation();
                        $joinKey = "{$defaultEntityClassName}->{$entityClassNameToBeConnected}";

                        if ($defaultPropertyInformation->relationship->connectViaRepositoryWithName === "") {
                            $join = self::manyViaPropertyRelationshipJoinQuery(
                                $entityInformationToBeConnected->repositoryName, //$ownedRepositoryName
                                $joinKey, //$joinKey
                                $ownerRepositoryName, //$ownerRepositoryName
                                $entityInformationToBeConnected->propertiesInformation[$defaultPropertyInformation->relationship->connectViaPropertyWithName]->columnName, //$foreignKeyColumnName
                                $defaultEntityClassName::informationForPropertyWithName($defaultEntityInformation->primaryPropertyName)->columnName, //$ownedEntityPrimaryColumnName
                                $entityInformationToBeConnected->propertyNameForSoftDelete ? $entityInformationToBeConnected->informationForPropertyWithName($entityInformationToBeConnected->propertyNameForSoftDelete)->columnName : null //$propertyNameForSoftDelete
                            );

                            if ($defaultPropertyInformation->relationship->ownerNameInProperty !== "") {
                                $join .= self::manyViaPropertyRelationshipExtendsJoinQuery(
                                    $joinKey, //$joinKey
                                    $defaultPropertyInformation->relationship->ownerNameInProperty, //$ownerNameInPropertyWithName
                                    $defaultPropertyInformation->entityInformation->name //$ownerName
                                );
                            }

                        } elseif ($defaultPropertyInformation->relationship->connectViaPropertyWithName === "") {
                            $join = self::manyViaRepositoryRelationshipJoinQuery(
                                $joinKey, //$joinKey
                                $defaultPropertyInformation->relationship->connectViaRepositoryWithName, //$connectViaRepositoryWithName
                                $defaultEntityInformation->repositoryName, //$ownerRepositoryName
                                $entityInformationToBeConnected->repositoryName, //$ownedRepositoryName
                                $defaultEntityClassName::informationForPropertyWithName($defaultEntityInformation->primaryPropertyName)->columnName, //$ownerPrimaryPropertyColumnName
                                $entityClassNameToBeConnected::informationForPropertyWithName($entityInformationToBeConnected->primaryPropertyName)->columnName, //$ownedPrimaryPropertyColumnName
                                $entityInformationToBeConnected->propertyNameForSoftDelete ? $entityInformationToBeConnected->informationForPropertyWithName($entityInformationToBeConnected->propertyNameForSoftDelete)->columnName : null //$propertyNameForSoftDelete
                            );
                        }
                    }

                    $defaultEntityClassName = $entityClassNameToBeConnected;
                    $ownerRepositoryName = $joinKey;
                    $joins[$joinKey] = $join;

                }
            } else {
                $defaultPropertyInformation = $defaultEntityClassName::informationForPropertyWithName($items[0]);
            }

            if ($defaultPropertyInformation->repositoryName) $ownerRepositoryName = $defaultPropertyInformation->repositoryName;

            $matches = [];
            \preg_match("#\{([^\ ]*?\}\.\{[^\ ]*?)*[^\{\}]*?\}[^\{]*?([^\{]*)#", $part["query"], $matches);
            if (isset($matches[2]) AND \strpos($matches[2], $this->parameterPlaceholder) !== false) {
                $segment = \preg_replace("#(\{(.*?)\}\.?)+#", "[{$ownerRepositoryName}].[{$defaultPropertyInformation->columnName}]", $matches[0], 1);
                if (isset($this->informationForEntity($defaultPropertyInformation->entityInformation)["repositories"][$ownerRepositoryName])) {
                    $segment = \str_replace($this->parameterPlaceholder, $this->informationForEntity($defaultPropertyInformation->entityInformation)["repositories"][$ownerRepositoryName]["columns"][$defaultPropertyInformation->columnName]["placeholder"], $segment);
                }
                $part["query"] = \str_replace($matches[0], $segment, $part["query"]);
            } else {
                $part["query"] = \preg_replace("#(\{(.*?)\}\.?)+#", "[{$ownerRepositoryName}].[{$defaultPropertyInformation->columnName}]". ($type === self::PROCESS_SELECT ? " AS [{$selectItemAlias}{$defaultPropertyInformation->name}]" : ""), $part["query"], 1);
            }
        }

        return $needDistinct;
    }

    /**
     * @param string $query
     * @param array $joins
     * @param string $defaultEntityClassName
     * @param int $type
     * @return void
     */
    protected function processJunctions(&$query, array &$joins, $defaultEntityClassName, $type) {
        if (\preg_match_all("#(\{\*([A-Za-z0-9_]+?\,[A-Za-z0-9\\\_]+?)\*\})\ *?=\ *?(" . \preg_quote(\obo\Interfaces\IQuerySpecification::PARAMETER_PLACEHOLDER) . ")#", $query, $blocks)) {
            foreach ($blocks[0] as $key => $block) {
                $parts = \explode(",", $blocks[2][$key]);
                $joinKey = "{$defaultEntityClassName}->{$parts[0]}_{$parts[1]::entityInformation()->repositoryName}";
                $joins[$joinKey] = "INNER JOIN [{$parts[0]}] AS [{$joinKey}] ON [{$joinKey}].[{$defaultEntityClassName::entityInformation()->repositoryName}] = [{$defaultEntityClassName::entityInformation()->repositoryName}].[{$defaultEntityClassName::informationForPropertyWithName($defaultEntityClassName::entityInformation()->primaryPropertyName)->columnName}]";
                $newBlock = \str_replace($blocks[1][$key], "[{$joinKey}].[{$parts[1]::entityInformation()->repositoryName}]", $block);
                $newBlock = \str_replace($blocks[3][$key], $this->informationForEntity($parts[1]::entityInformation())["repositories"][$parts[1]::entityInformation()->repositoryName]["columns"][$parts[1]::informationForPropertyWithName($parts[1]::entityInformation()->primaryPropertyName)->columnName]["placeholder"], $newBlock);
                $query = \str_replace($block, $newBlock, $query);
            }
        }

        if ($type === self::PROCESS_WHERE) {
            $entityInformation = $defaultEntityClassName::entityInformation();
            $primaryPropertyColumn = $entityInformation->informationForPropertyWithName($entityInformation->primaryPropertyName)->columnName;
            $entityRepositoryName = $entityInformation->repositoryName;
            foreach ($entityInformation->propertiesInformation as $propertyInformation) {
                if (!$propertyInformation->repositoryName) continue;
                $joinKey = "{$entityRepositoryName}->{$propertyInformation->repositoryName}";
                $joins = [$joinKey => " LEFT JOIN [{$propertyInformation->repositoryName}] ON [{$propertyInformation->repositoryName}].[{$primaryPropertyColumn}] = [{$entityRepositoryName}].[{$primaryPropertyColumn}]"] + $joins;
            }
        }
    }

    /**
     * @param string $ownedRepositoryName
     * @param string $joinKey
     * @param string $ownerRepositoryName
     * @param string $foreignKeyColumnName
     * @param string $ownedEntityPrimaryColumnName
     * @param string $columnNameForSoftDelete
     * @return string
     */
    protected static function oneRelationshipJoinQuery($ownedRepositoryName, $joinKey, $ownerRepositoryName, $foreignKeyColumnName, $ownedEntityPrimaryColumnName, $columnNameForSoftDelete) {
        $softDeleteClause = $columnNameForSoftDelete ? " AND [{$joinKey}].[{$columnNameForSoftDelete}] = 0" : "";
        return "LEFT JOIN [{$ownedRepositoryName}] as [{$joinKey}] ON [{$ownerRepositoryName}].[{$foreignKeyColumnName}] = [{$joinKey}].[{$ownedEntityPrimaryColumnName}]{$softDeleteClause}";
    }

    /**
     * @param string $ownedRepositoryName
     * @param string $joinKey
     * @param string $ownerRepositoryName
     * @param string $foreignKeyColumnName
     * @param string $ownedEntityPrimaryColumnName
     * @param string $columnNameForSoftDelete
     * @return string
     */
    protected static function oneInverseRelationshipJoinQuery($ownedRepositoryName, $joinKey, $ownerRepositoryName, $foreignKeyColumnName, $ownedEntityPrimaryColumnName, $columnNameForSoftDelete) {
        $softDeleteClause = $columnNameForSoftDelete ? " AND [{$joinKey}].[{$columnNameForSoftDelete}] = 0" : "";
        return "LEFT JOIN [{$ownedRepositoryName}] as [{$joinKey}] ON" . "[{$joinKey}] .[{$foreignKeyColumnName}] = [{$ownerRepositoryName}].[{$ownedEntityPrimaryColumnName}] {$softDeleteClause}";
    }

    /**
     * @param string $ownedRepositoryName
     * @param string $joinKey
     * @param string $ownerRepositoryName
     * @param string $ownerName
     * @param string $foreignKey
     * @param string $ownedEntityPrimaryColumnName
     * @param string $columnNameForSoftDelete
     * @return string
     */
    protected static function oneInverseDynamicRelationshipJoinQuery($ownedRepositoryName, $joinKey, $ownerRepositoryName, $ownerName, $foreignKey, $ownedEntityPrimaryColumnName, $columnNameForSoftDelete) {
        $softDeleteClause = $columnNameForSoftDelete ? " AND [{$joinKey}].[{$columnNameForSoftDelete}] = 0" : "";
        return "LEFT JOIN [{$ownedRepositoryName}] as [{$joinKey}] ON" . "[{$joinKey}].[{$foreignKey[0]}] = [{$ownerRepositoryName}].[{$ownedEntityPrimaryColumnName}] AND [{$joinKey}].[{$foreignKey[1]}] = '{$ownerName}'  {$softDeleteClause}";
    }

    /**
     * @param string $ownedRepositoryName
     * @param string $joinKey
     * @param string $ownerRepositoryName
     * @param string $foreignKeyColumnName
     * @param string $ownedEntityPrimaryColumnName
     * @param string $columnNameForSoftDelete
     * @return string
     */
    protected static function manyViaPropertyRelationshipJoinQuery($ownedRepositoryName, $joinKey, $ownerRepositoryName, $foreignKeyColumnName, $ownedEntityPrimaryColumnName, $columnNameForSoftDelete) {
        $softDeleteClause = $columnNameForSoftDelete ? " AND [{$joinKey}].[{$columnNameForSoftDelete}] = 0" : "";
        return "LEFT JOIN [{$ownedRepositoryName}] as [{$joinKey}] ON [{$joinKey}].[{$foreignKeyColumnName}] = [{$ownerRepositoryName}].[{$ownedEntityPrimaryColumnName}]{$softDeleteClause}";
    }

    /**
     * @param string $joinKey
     * @param string $ownerNameInPropertyWithName
     * @param string $ownerClassName
     * @return string
     */
    protected static function manyViaPropertyRelationshipExtendsJoinQuery($joinKey, $ownerNameInPropertyWithName, $ownerClassName) {
        return " AND [{$joinKey}].[{$ownerNameInPropertyWithName}] = '{$ownerClassName}'";
    }

    /**
     * @param string $joinKey
     * @param string $connectViaRepositoryWithName
     * @param string $ownerRepositoryName
     * @param string $ownedRepositoryName
     * @param string $ownerPrimaryPropertyColumnName
     * @param string $ownedPrimaryPropertyColumnName
     * @param string $columnNameForSoftDelete
     * @return string
     */
    protected static function manyViaRepositoryRelationshipJoinQuery($joinKey, $connectViaRepositoryWithName, $ownerRepositoryName, $ownedRepositoryName, $ownerPrimaryPropertyColumnName, $ownedPrimaryPropertyColumnName, $columnNameForSoftDelete) {
        $softDeleteClause = $columnNameForSoftDelete ? " AND [{$joinKey}].[{$columnNameForSoftDelete}] = 0" : "";
        return "LEFT JOIN [{$connectViaRepositoryWithName}]
                ON [{$connectViaRepositoryWithName}].[{$ownerRepositoryName}]
                = [{$ownerRepositoryName}].[{$ownerPrimaryPropertyColumnName}]
                LEFT JOIN [{$ownedRepositoryName}] as [{$joinKey}]
                ON [{$connectViaRepositoryWithName}].[{$ownedRepositoryName}]
                = [{$joinKey}].[{$ownedPrimaryPropertyColumnName}]{$softDeleteClause}";
    }
}
