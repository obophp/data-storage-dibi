<?php

/**
 * This file is part of the Obo framework for application domain logic.
 * Obo framework is based on voluntary contributions from different developers.
 *
 * @link https://github.com/obophp/obo
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

namespace obo\DataStorage\DataConverters;

class DefaultMysqlDataConverter extends \obo\BaseObject implements \obo\DataStorage\Interfaces\IDataConverter {

    /**
     * @param string $combinationCode
     * @return string
     * @throws \obo\Exceptions\Exception
     */
    public function convertFilterForCombinationCode($combinationCode) {
        switch ($combinationCode) {
            case "Dtinyint->Oentity":
            case "Dtinyint->Ointeger":
            case "Dsmallint->Oentity":
            case "Dsmallint->Ointeger":
            case "Dmediumint->Oentity":
            case "Dmediumint->Ointeger":
            case "Dint->Oentity":
            case "Dint->Ointeger":
            case "Dbigint->Oentity":
            case "Dbigint->Ointeger":
            case "Dfloat->Ofloat":
            case "Ddouble->Ofloat":
            case "Ddate->OdateTime":
            case "Ddatetime->OdateTime":
            case "Dtime->OdateTime":
            case "Dyear->OdateTime":
            case "Dchar->Oentity":
            case "Dchar->Ostring":
            case "Dvarchar->Oentity":
            case "Dvarchar->Ostring":
            case "Dtinytext->Ostring":
            case "Dtext->Ostring":
            case "Dmediumtext->Ostring":
            case "Dlongtext->Ostring":
            case "Denum->Oentity":
            case "Dtinyint->Omixed":
            case "Dsmallint->Omixed":
            case "Dmediumint->Omixed":
            case "Dint->Omixed":
            case "Dbigint->Omixed":
            case "Ddecimal->Omixed":
            case "Dfloat->Omixed":
            case "Ddouble->Omixed":
            case "Ddate->Omixed":
            case "Ddatetime->Omixed":
            case "Dtimestamp->Omixed":
            case "Dtime->Omixed":
            case "Dyear->Omixed":
            case "Dchar->Omixed":
            case "Dvarchar->Omixed":
            case "Dtinytext->Omixed":
            case "Dtext->Omixed":
            case "Dmediumtext->Omixed":
            case "Dlongtext->Omixed":
            case "Denum->Omixed":
            case "Oarray->Dset":
            case "OdateTime->Ddate":
            case "OdateTime->Ddatetime":
            case "OdateTime->Dtime":
            case "OdateTime->Dyear":
            case "Oentity->Denum":
            case "Ofloat->Dfloat":
            case "Ofloat->Ddouble":
            case "Ofloat->Denum":
            case "Ointeger->Dtinyint":
            case "Ointeger->Dsmallint":
            case "Ointeger->Dmediumint":
            case "Ointeger->Dint":
            case "Ointeger->Dbigint":
            case "Ointeger->Ddecimal":
            case "Ointeger->Dtimestamp":
            case "Ointeger->Dtime":
            case "Ointeger->Denum":
            case "Ostring->Dchar":
            case "Ostring->Dvarchar":
            case "Ostring->Dtinytext":
            case "Ostring->Dtext":
            case "Ostring->Dmediumtext":
            case "Ostring->Dlongtext":
            case "Ostring->Denum":
            case "Omixed->Dtinyint":
            case "Omixed->Dsmallint":
            case "Omixed->Dmediumint":
            case "Omixed->Dint":
            case "Omixed->Dbigint":
            case "Omixed->Ddecimal":
            case "Omixed->Dfloat":
            case "Omixed->Ddouble":
            case "Omixed->Ddate":
            case "Omixed->Ddatetime":
            case "Omixed->Dtimestamp":
            case "Omixed->Dtime":
            case "Omixed->Dyear":
            case "Omixed->Dchar":
            case "Omixed->Dvarchar":
            case "Omixed->Dtinytext":
            case "Omixed->Dtext":
            case "Omixed->Dmediumtext":
            case "Omixed->Dlongtext":
            case "Denum->Ostring":
            case "Ofloat->Ddecimal":
            case "Dtime->OdateInterval":
            case "OdateInterval->Dtime":
            case "Omixed->Denum":
            case "Ostring->Dlongblob":
            case "Ostring->Dmediumblob":
            case "Ostring->Dblob":
            case "Ostring->Dtinyblob":
            case "Dlongblob->Ostring":
            case "Dmediumblob->Ostring":
            case "Dblob->Ostring":
            case "Dtinyblob->Ostring":
                return null;
            case "Dtinyint->Ofloat":
            case "Dsmallint->Ofloat":
            case "Dmediumint->Ofloat":
            case "Dint->Ofloat":
            case "Dbigint->Ofloat":
            case "Ddecimal->Ofloat":
            case "Dchar->Ofloat":
            case "Dvarchar->Ofloat":
            case "Denum->Ofloat":
            case "Ointeger->Dfloat":
            case "Ointeger->Ddouble":
            case "Ostring->Ddecimal":
            case "Ostring->Dfloat":
            case "Ostring->Ddouble":
                return "toFloat";
            case "Dtinyint->Ostring":
            case "Dsmallint->Ostring":
            case "Dmediumint->Ostring":
            case "Dint->Ostring":
            case "Dbigint->Ostring":
            case "Ddecimal->Ostring":
            case "Dfloat->Ostring":
            case "Ddouble->Ostring":
            case "Ddatetime->Ostring":
            case "Dtimestamp->Ostring":
            case "Oentity->Dchar":
            case "Oentity->Dvarchar":
            case "Oentity->Dtinytext":
            case "Oentity->Dtext":
            case "Oentity->Dmediumtext":
            case "Oentity->Dlongtext":
            case "Ofloat->Dchar":
            case "Ofloat->Dvarchar":
            case "Ointeger->Dchar":
            case "Ointeger->Dvarchar":
                return "toString";
            case "Dtinyint->Oboolean":
            case "Dsmallint->Oboolean":
            case "Dmediumint->Oboolean":
            case "Dint->Oboolean":
            case "Dbigint->Oboolean":
                return "toBoolean";
            case "Ddecimal->Ointeger":
            case "Dfloat->Ointeger":
            case "Ddouble->Ointeger":
            case "Dtimestamp->Ointeger":
            case "Dchar->Ointeger":
            case "Dvarchar->Ointeger":
            case "Denum->Ointeger":
            case "Oboolean->Dtinyint":
            case "Oboolean->Dsmallint":
            case "Oboolean->Dmediumint":
            case "Oboolean->Dint":
            case "Oboolean->Dbigint":
            case "Oentity->Dtinyint":
            case "Oentity->Dsmallint":
            case "Oentity->Dmediumint":
            case "Oentity->Dint":
            case "Oentity->Dbigint":
            case "Ofloat->Dtinyint":
            case "Ofloat->Dsmallint":
            case "Ofloat->Dmediumint":
            case "Ofloat->Dint":
            case "Ofloat->Dbigint":
            case "Ostring->Dtimestamp":
            case "Ddate->Ostring":
            case "Dtime->Ostring":
            case "Dyear->Ostring":
            case "Ostring->Dtinyint":
            case "Ostring->Dsmallint":
            case "Ostring->Dmediumint":
            case "Ostring->Dint":
            case "Ostring->Dbigint":
                return "toInteger";
            case "OdateTime->Denum":
            case "OdateTime->Dchar":
            case "OdateTime->Dvarchar":
                return "dateTimeToString";
            case "Dtimestamp->OdateTime":
                return "timeStampToDateTime";
            case "Dchar->Oobject":
            case "Dvarchar->Oobject":
            case "Dtinytext->Oobject":
            case "Dtext->Oobject":
            case "Dmediumtext->Oobject":
            case "Dlongtext->Oobject":
                return "deserializeToObject";
            case "Dchar->Oarray":
            case "Dvarchar->Oarray":
            case "Dtinytext->Oarray":
            case "Dtext->Oarray":
            case "Dmediumtext->Oarray":
            case "Dlongtext->Oarray":
                return "deserializeToArray";
            case "Dchar->OdateTime":
            case "Dvarchar->OdateTime":
            case "Ostring->Ddate":
            case "Ostring->Ddatetime":
            case "Ostring->Dtime":
            case "Denum->OdateTime":
            case "Ostring->Dyear":
                return "stringToDateTime";
            case "Dset->Oarray":
                return "toArray";
            case "Oobject->Dchar":
            case "Oobject->Dvarchar":
            case "Oobject->Dtinytext":
            case "Oobject->Dtext":
            case "Oobject->Dmediumtext":
            case "Oobject->Dlongtext":
                return "serializeObject";
            case "Oarray->Dchar":
            case "Oarray->Dvarchar":
            case "Oarray->Dtinytext":
            case "Oarray->Dtext":
            case "Oarray->Dmediumtext":
            case "Oarray->Dlongtext":
                return "serializeArray";
            case "OdateTime->Dtimestamp":
                return "dateTimeToTimestamp";

            default:
                throw new \obo\Exceptions\Exception("For combination {$combinationCode} does not exist filter");
        }
    }

    /**
     * @param mixed $value
     * @return float
     */
    public static function toFloat($value) {
        return (float) $value;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public static function toString($value) {
        return (string) $value;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function toBoolean($value) {
        return (boolean) $value;
    }

    /**
     * @param mixed $value
     * @return int
     */
    public static function toInteger($value) {
        return (integer) $value;
    }

    /**
     * @param mixed $value
     * @return array
     */
    public static function toArray($value) {
        return (array) $value;
    }

    /**
     * @param object $value
     * @return string
     */
    public static function serializeObject($value) {
        return \serialize($value);
    }

    /**
     * @param string $value
     * @param \obo\Carriers\PropertyInformationCarrier $propertyInformation
     * @return null|object
     * @throws \obo\Exceptions\Exception
     */
    public static function deserializeToObject($value, \obo\Carriers\PropertyInformationCarrier $propertyInformation) {
        $unserializedValue = ($value === null OR $value === "") ? null : \unserialize($value);
        if ($value && $unserializedValue === false) throw new \obo\Exceptions\Exception("Wrong data source for deserialization");
        return $unserializedValue;
    }

    /**
     * @param array $value
     * @return string
     */
    public static function serializeArray(array $value) {
        return \serialize($value);
    }

    /**
     * @param string $value
     * @param \obo\Carriers\PropertyInformationCarrier $propertyInformation
     * @return array
     * @throws \obo\Exceptions\Exception
     */
    public static function deserializeToArray($value, \obo\Carriers\PropertyInformationCarrier $propertyInformation) {
        $unserializedValue = ($value === null OR $value === "") ? ($propertyInformation->nullable ? null : []) : \unserialize($value);
        if ($value && $unserializedValue === false) throw new \obo\Exceptions\Exception("Wrong data source for deserialization");
        return $unserializedValue;
    }

    /**
     * @param \DateTime $value
     * @return string
     */
    public static function dateTimeToString(\DateTime $value) {
        return $value->format("Y-m-d H:i:s");
    }

    /**
     * @param integer $value
     * @return \DateTime
     */
    public static function timeStampToDateTime($value) {
        $dateTime = new \DateTime;
        $dateTime->setTimestamp($value);
        return $dateTime;
    }

    /**
     * @param string $value
     * @return \DateTime
     */
    public static function stringToDateTime($value) {
        return new \DateTime($value);
    }

    /**
     * @param \DateTime $value
     * @return int
     */
    public static function dateTimeToTimestamp(\DateTime $value) {
        return $value->getTimestamp();
    }

}
