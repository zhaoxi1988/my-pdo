<?php
/**
 * PDO 单表操作
 * 目标:
 * <ul>
 *      <li>100%防止SQL注入 , 确保database系统的100%安全.</li>
 *      <li>不使用prepared statement, 确保WebApp及中间层性能</li>
 *      <li>严格限制无WHERE LIMIT的条件, 确保交易安全</li>
 * <ul>
 * Note:
 * <pre>
 * 如果每个变量都有"前继变量",就在当前的变量值头部加上空格,请勿在尾部加空格.
 * </pre>
 * User: zhaoxi
 * Date: 13-11-18
 * Time: 18:00
 */
include __DIR__ . '/simpletable.php';
abstract class Zpdo_Zfunc {
    /**
     * @var $dbRef Zpdo_Simple
     */
    protected $dbRef = null;
    protected $_sql = null;

    public abstract function getName();

    public abstract function getSql();

    /**
     * 用于quote
     * @param $db Zpdo_Simple
     */
    public function setDbRef($db) {
        if (!is_null($db)) {
            $this->dbRef = $db;
        }
    }

    public function clear() {
        $this->_sql = null;
    }
}


class Zpdo_In extends Zpdo_Zfunc {
    private static $dataTypes = array('NUM', 'STR', 'NUMBER', 'STRING');

    private $name = 'IN';
    private $columnName = null;
    private $type = null;
    private $data = null;

    /**
     * @param $columnName
     * @param $type 'NUMBER', 'STRING'
     *          alias : 'NUM', 'STR'
     * @param array $data
     * @throws RuntimeException
     */
    public function __construct($columnName, $type, array $data) {
        if (empty($data)) {
            throw new RuntimeException('Illegal Arguments! $data is empty!');
        }
        if (empty($columnName)) {
            throw new RuntimeException('Illegal Arguments! $columnName is empty!');
        }
        if (!in_array($type, self::$dataTypes)) {
            throw new RuntimeException('Illegal Arguments! $type undefined!');
        }
        $this->columnName = $columnName;
        $this->type = $type;
        $this->data = $data;
    }

    public function getName() {
        return $this->name;
    }

    public function getSql() {
        if (!empty($this->_sql)) {
            return $this->_sql;
        }
        if (empty($this->dbRef)) {
            throw new RuntimeException('Please set dbRef!');
        }
        $v = '';
        if ($this->type === 'NUM' || $this->type === 'NUMBER') {
            $appendedCnt = 0;
            $count = count($this->data);
            foreach ($this->data as $one) {
                if (is_numeric($one)) {
                    $v .= $one;
                    if (($appendedCnt = $appendedCnt + 1) < $count) {
                        $v .= ', ';
                    }
                } else {
                    continue;
                }
            }
        } else {
            $appendedCnt = 0;
            $count = count($this->data);
            $hasNull = false;
            $hasEmpty = false;
            foreach ($this->data as $one) {
                // null , ''  有意义吗?
                if (null === $one) {
                    $count--;
                    $hasNull = true;
                    continue;
                } else if ('' === $one) {
                    $count--;
                    $hasEmpty = true;
                    continue;
                }
                $v .= $this->dbRef->quote($one);
                if (($appendedCnt = $appendedCnt + 1) < $count) {
                    $v .= ',';
                }
            }
            if ($hasNull) {
                $v .= ',' . $this->dbRef->quotedValue(null);
            }
            if ($hasEmpty) {
                $v .= ',' . $this->dbRef->quotedValue('');
            }
        }
        if ($v === '') {
            throw new RuntimeException('Illegal Arguments!Occur IN function: has no values!');
        }
        $this->_sql = $this->dbRef->quotedName($this->columnName) . ' IN (' . $v . ')';
        return $this->_sql;
    }
}

class Zpdo_Notin extends Zpdo_Zfunc {
    private static $dataTypes = array('NUM', 'STR', 'NUMBER', 'STRING');

    private $name = 'NOT IN';
    private $columnName = null;
    private $type = null;
    private $data = null;

    public function __construct($columnName, $type, array $data) {
        if (empty($data)) {
            throw new RuntimeException('Illegal Arguments! $data is empty!');
        }
        if (empty($columnName)) {
            throw new RuntimeException('Illegal Arguments! $columnName is empty!');
        }
        if (!in_array($type, self::$dataTypes)) {
            throw new RuntimeException('Illegal Arguments! $type undefined!');
        }
        $this->columnName = $columnName;
        $this->type = $type;
        $this->data = $data;
    }

    public function getName() {
        return $this->name;
    }

    public function getSql() {
        if (!empty($this->_sql)) {
            return $this->_sql;
        }
        if (empty($this->dbRef)) {
            throw new RuntimeException('Please set dbRef!');
        }
        $v = '';
        if ($this->type === 'NUM' || $this->type === 'NUMBER') {
            $appendedCnt = 0;
            $count = count($this->data);
            foreach ($this->data as $one) {
                if (is_numeric($one)) {
                    $v .= $one;
                    if (($appendedCnt = $appendedCnt + 1) < $count) {
                        $v .= ',';
                    }
                } else {
                    continue;
                }
            }
        } else {
            $appendedCnt = 0;
            $count = count($this->data);
            $hasNull = false;
            $hasEmpty = false;
            foreach ($this->data as $one) {
                // null , ''  有意义吗?
                if (null === $one) {
                    $count--;
                    $hasNull = true;
                    continue;
                } else if ('' === $one) {
                    $count--;
                    $hasEmpty = true;
                    continue;
                }
                $v .= $this->dbRef->quote($one);
                if (($appendedCnt = $appendedCnt + 1) < $count) {
                    $v .= ',';
                }
            }
            if ($hasNull) {
                $v .= ',' . $this->dbRef->quotedValue(null);
            }
            if ($hasEmpty) {
                $v .= ',' . $this->dbRef->quotedValue('');
            }
        }
        $this->_sql = $this->dbRef->quotedName($this->columnName) . ' NOT IN (' . $v . ')';
        return $this->_sql;
    }
}


/**
 * @deprecated see @Class Zpdo_IsNull
 * Class (Zpdo_Is)
 * @authors: zhaoxi
 */
class Zpdo_Is extends Zpdo_Zfunc {
    private $name = 'IS';
    private $columnName = null;

    /**
     * @param $columnName
     * @throws RuntimeException
     */
    public function __construct($columnName) {
        if (empty($columnName)) {
            throw new RuntimeException('Illegal Arguments! $columnName is empty!');
        }
        $this->columnName = $columnName;
    }

    public function getName() {
        return $this->name;
    }

    public function getSql() {
        if (!empty($this->_sql)) {
            return $this->_sql;
        }
        if (empty($this->dbRef)) {
            throw new RuntimeException('Please set dbRef!');
        }
        $this->_sql = $this->dbRef->quotedName($this->columnName) . ' IS NULL';
        return $this->_sql;
    }
}

/**
 * Class (Zpdo_IsNull)
 * @authors: zhaoxi
 */
class Zpdo_IsNull extends Zpdo_Zfunc {
    private $name = 'IS';
    private $columnName = null;

    /**
     * @param $columnName
     * @throws RuntimeException
     */
    public function __construct($columnName) {
        if (empty($columnName)) {
            throw new RuntimeException('Illegal Arguments! $columnName is empty!');
        }
        $this->columnName = $columnName;
    }

    public function getName() {
        return $this->name;
    }

    public function getSql() {
        if (!empty($this->_sql)) {
            return $this->_sql;
        }
        if (empty($this->dbRef)) {
            throw new RuntimeException('Please set dbRef!');
        }
        $this->_sql = $this->dbRef->quotedName($this->columnName) . ' IS NULL';
        return $this->_sql;
    }
}


/**
 * @deprecated  see @Class Zpdo_IsnotNull
 * Class (Zpdo_Isnot)
 * @authors: zhaoxi
 */
class Zpdo_Isnot extends Zpdo_Zfunc {
    private $name = 'IS NOT';
    private $columnName = null;

    public function __construct($columnName) {
        if (empty($columnName)) {
            throw new RuntimeException('Illegal Arguments! $columnName is empty!');
        }
        $this->columnName = $columnName;
    }

    public function getName() {
        return $this->name;
    }

    public function getSql() {
        if (!empty($this->_sql)) {
            return $this->_sql;
        }
        if (empty($this->dbRef)) {
            throw new RuntimeException('Please set dbRef!');
        }
        $this->_sql = $this->dbRef->quotedName($this->columnName) . ' IS NOT NULL';
        return $this->_sql;
    }
}

/**
 * Class (Zpdo_IsnotNull)
 * @authors: zhaoxi
 */
class Zpdo_IsNotNull extends Zpdo_Zfunc {
    private $name = 'IS NOT';
    private $columnName = null;

    public function __construct($columnName) {
        if (empty($columnName)) {
            throw new RuntimeException('Illegal Arguments! $columnName is empty!');
        }
        $this->columnName = $columnName;
    }

    public function getName() {
        return $this->name;
    }

    public function getSql() {
        if (!empty($this->_sql)) {
            return $this->_sql;
        }
        if (empty($this->dbRef)) {
            throw new RuntimeException('Please set dbRef!');
        }
        $this->_sql = $this->dbRef->quotedName($this->columnName) . ' IS NOT NULL';
        return $this->_sql;
    }
}


class Zpdo_Distinct extends Zpdo_Zfunc {
    private $name = 'DISTINCT';
    private $columnName = null;
    private $alias = null;

    public function __construct($columnName, $alias = null) {
        if (empty($columnName)) {
            throw new RuntimeException('Illegal Arguments! $columnName is empty!');
        }
        $this->columnName = $columnName;

        if (!empty($alias)) {
            $this->alias = $alias;
        }
    }

    public function getName() {
        return $this->name;
    }

    public function getSql() {
        if (!empty($this->_sql)) {
            return $this->_sql;
        }
        if (empty($this->dbRef)) {
            throw new RuntimeException('Please set dbRef!');
        }
        if (!empty($this->alias)) {
            $this->_sql = ' DISTINCT(' . $this->dbRef->quotedName($this->columnName) . ') AS ';
            $this->_sql .= $this->dbRef->quotedName($this->alias);
        } else {
            $this->_sql = ' DISTINCT ' . $this->dbRef->quotedName($this->columnName);
        }
        return $this->_sql;
    }
}


/**
 * Class (Zpdo_Count)
 * @authors: zhaoxi
 */
class Zpdo_Count extends Zpdo_Zfunc {
    private $name = 'COUNT';
    private $columnOrFunc = null;
    private $alias = null;

    public function __construct($columnOrFunc, $alias = null) {
        if ($columnOrFunc !== 1 && $columnOrFunc !== '1') {
            if (is_string($columnOrFunc)) {
                if (empty($columnOrFunc)) {
                    throw new RuntimeException('Illegal Arguments! $column is empty!');
                }
            } else if (!$columnOrFunc instanceof Zpdo_Distinct) {

                throw new RuntimeException('Illegal Arguments! $columnOrFunc is neither string nor distinct func!');
            }
        }
        $this->columnOrFunc = $columnOrFunc;
        if (!empty($alias)) {
            $this->alias = $alias;
        }
    }

    public function getName() {
        return $this->name;
    }

    public function getSql() {
        if (!empty($this->_sql)) {
            return $this->_sql;
        }
        if (empty($this->dbRef)) {
            throw new RuntimeException('Please set dbRef!');
        }
        if (is_string($this->columnOrFunc)) {
            if ($this->columnOrFunc === '*' || $this->columnOrFunc === '1') {
                $this->_sql = ' COUNT(1)';
            } else {
                $this->_sql = ' COUNT(' . $this->dbRef->quotedName($this->columnOrFunc) . ')';
            }
        } else if ($this->columnOrFunc === 1) {
            $this->_sql = ' COUNT(1)';
        } else if ($this->columnOrFunc instanceof Zpdo_Distinct) {
            $this->_sql = ' COUNT(' . $this->columnOrFunc->getSql() . ')';
            $this->columnOrFunc->clear();
        }
        if (!empty($this->alias)) {
            $this->_sql .= ' AS ' . $this->dbRef->quotedName($this->alias);
        }
        return $this->_sql;
    }
}

/**
 * Class (Zpdo_Max)
 * @authors: zhaoxi
 */
class Zpdo_Max extends Zpdo_Zfunc {
    private $name = 'MAX';
    private $columnOrFunc = null;
    private $alias = null;

    public function __construct($columnOrFunc, $alias = null) {
        if (is_string($columnOrFunc)) {
            if (empty($columnOrFunc)) {
                throw new RuntimeException('Illegal Arguments! $column is empty!');
            }
        } else if (!$columnOrFunc instanceof Zpdo_Distinct) {
            throw new RuntimeException('Illegal Arguments! $columnOrFunc is neither string nor distinct func!');
        }
        $this->columnOrFunc = $columnOrFunc;
        if (!empty($alias)) {
            $this->alias = $alias;
        }
    }

    public function getName() {
        return $this->name;
    }

    public function getSql() {
        if (!empty($this->_sql)) {
            return $this->_sql;
        }
        if (empty($this->dbRef)) {
            throw new RuntimeException('Please set dbRef!');
        }
        if (is_string($this->columnOrFunc)) {
            $this->_sql = ' MAX(' . $this->dbRef->quotedName($this->columnOrFunc) . ')';
        } else if ($this->columnOrFunc instanceof Zpdo_Distinct) {
            $this->_sql = ' MAX(' . $this->columnOrFunc->getSql() . ')';
            $this->columnOrFunc->clear();
        }
        if (!empty($this->alias)) {
            $this->_sql .= ' AS ' . $this->dbRef->quotedName($this->alias);
        }
        return $this->_sql;
    }
}

/**
 * Class (Zpdo_Min)
 * @authors: zhaoxi
 */
class Zpdo_Min extends Zpdo_Zfunc {
    private $name = 'MIN';
    private $columnOrFunc = null;
    private $alias = null;

    public function __construct($columnOrFunc, $alias = null) {
        if (is_string($columnOrFunc)) {
            if (empty($columnOrFunc)) {
                throw new RuntimeException('Illegal Arguments! $column is empty!');
            }
        } else if (!$columnOrFunc instanceof Zpdo_Distinct) {
            throw new RuntimeException('Illegal Arguments! $columnOrFunc is neither string nor distinct func!');
        }
        $this->columnOrFunc = $columnOrFunc;
        if (!empty($alias)) {
            $this->alias = $alias;
        }
    }

    public function getName() {
        return $this->name;
    }

    public function getSql() {
        if (!empty($this->_sql)) {
            return $this->_sql;
        }
        if (empty($this->dbRef)) {
            throw new RuntimeException('Please set dbRef!');
        }
        if (is_string($this->columnOrFunc)) {
            $this->_sql = ' MIN(' . $this->dbRef->quotedName($this->columnOrFunc) . ')';
        } else if ($this->columnOrFunc instanceof Zpdo_Distinct) {
            $this->_sql = ' MIN(' . $this->columnOrFunc->getSql() . ')';
            $this->columnOrFunc->clear();
        }
        if (!empty($this->alias)) {
            $this->_sql .= ' AS ' . $this->dbRef->quotedName($this->alias);
        }
        return $this->_sql;
    }
}

class Zpdo_Expr extends Zpdo_Zfunc {
    private static $calculate = array('+', '-', '*', '/', '%', '<<', '>>', '&', '|', '^', '=', '>', '<', '>=', '<=', '<>', '!=');
    private static $comparisonOperator = array();
    private $expr = null;

    public function getName() {
        return 'expr';
    }

    public function setExpresses(array $args) {
        $this->expr = $args;
        if (empty($args)) {
            throw new RuntimeException('Illegal Arguments!Empty contents!');
        }
        $count = count($args);
        if (Zpdo_Simple::isEven($count)) {
            // even 0,2,4,...
            throw new RuntimeException('Illegal Argument Count!');
        }
    }


    public function getSql() {
        if (!empty($this->_sql)) {
            return $this->_sql;
        }
        $i = 0;
        $count = count($this->expr);
        $this->_sql = '';
        foreach ($this->expr as $k => $v) {
            if (Zpdo_Simple::isEven($i)) {
                // columnName or numeric
                if ($v instanceof Zpdo_Zfunc) {
                    $this->_sql .= $v->getSql();
                } else {
                    // numeric , zstr , columnName ,
                    if (!is_numeric($v)) {
                        $type = gettype($v);
                        if ($type === 'string') {
                            // columnName
                            $this->_sql .= $this->dbRef->quotedName($v);
                        } else if ($type === 'object') {
                            // ztype: sql string literal
                            $this->_sql .= $this->dbRef->quotedValue($v);
                        } else {
                            $this->_sql .= $this->dbRef->quotedName($v);
                        }
                    } else {
                        // numeric
                        $this->_sql .= $this->dbRef->quotedValue($v);
                    }
                }
            } else {
                // + - * / >=
                if (in_array($v, self::$calculate)) {
                    $this->_sql .= ' ' . $v . ' ';
                } else {
                    throw new RuntimeException('Illegal Argument!Key: ' . $k . ', Value:' . $v);
                }
            }
            $i++;
        }
        return $this->_sql;
    }
}

class Zpdo_Sum extends Zpdo_Zfunc {
    private $name = 'SUM';
    private $columnOrFunc = null;
    private $alias = null;

    public function __construct($columnOrFunc, $alias = null) {
        if (is_string($columnOrFunc)) {
            if (empty($columnOrFunc)) {
                throw new RuntimeException('Illegal Arguments! $column is empty!');
            }
        } else if (!$columnOrFunc instanceof Zpdo_Expr) {
            throw new RuntimeException('Illegal Arguments! $columnOrFunc is neither string nor expr func!');
        }
        $this->columnOrFunc = $columnOrFunc;
        if (!empty($alias)) {
            $this->alias = $alias;
        }
    }

    public function getName() {
        return $this->name;
    }

    public function getSql() {
        if (!empty($this->_sql)) {
            return $this->_sql;
        }
        if (empty($this->dbRef)) {
            throw new RuntimeException('Please set dbRef!');
        }
        if (is_string($this->columnOrFunc)) {
            $this->_sql = ' SUM(' . $this->dbRef->quotedName($this->columnOrFunc) . ')';
        } else if ($this->columnOrFunc instanceof Zpdo_Expr) {
            $this->_sql = ' SUM(' . $this->columnOrFunc->getSql() . ')';
            $this->columnOrFunc->clear();
        }
        if (!empty($this->alias)) {
            $this->_sql .= ' AS ' . $this->dbRef->quotedName($this->alias);
        }
        return $this->_sql;
    }
}


class Zpdo_As extends Zpdo_Zfunc {
    private $name = 'AS';
    private $columnName = null;
    private $alias = null;

    public function __construct($columnOrFunc, $alias) {
        if (is_string($columnOrFunc)) {
            if (empty($columnOrFunc)) {
                throw new RuntimeException('Illegal Arguments! $column is empty!');
            }
        } else if (!$columnOrFunc instanceof Zpdo_Expr) {
            throw new RuntimeException('Illegal Arguments! $columnOrFunc is neither string nor expr func!');
        }
        $this->columnOrFunc = $columnOrFunc;
        if (!empty($alias)) {
            $this->alias = $alias;
        }
    }

    public function getName() {
        return $this->name;
    }

    public function getSql() {
        if (!empty($this->_sql)) {
            return $this->_sql;
        }
        if (empty($this->dbRef)) {
            throw new RuntimeException('Please set dbRef!');
        }
        if (is_string($this->columnOrFunc)) {
            $this->_sql = ' ' . $this->dbRef->quotedName($this->columnOrFunc);
        } else if ($this->columnOrFunc instanceof Zpdo_Expr) {
            $this->_sql = ' ' . $this->columnOrFunc->getSql();
            $this->columnOrFunc->clear();
        }
        if (!empty($this->alias)) {
            $this->_sql .= ' AS ' . $this->dbRef->quotedName($this->alias);
        }
        return $this->_sql;
    }
}


/**
 *
 * Class Zpdo_Between
 * @authors: zhaoxi
 */
class Zpdo_Between extends Zpdo_Zfunc {
    private $name = 'BETWEEN';
    private $columnName = null;
    private $low = null;
    private $high = null;

    public function __construct($columnName, $low, $high) {
        if (is_null($columnName) || is_null($low) || is_null($high)) {
            throw new RuntimeException('Illegal Arguments!');
        }
        if (!is_string($columnName)) {
            throw new RuntimeException('Illegal Arguments!$columnName is not string.');
        }
        $this->columnName = $columnName;
        $this->low = $low;
        $this->high = $high;
    }

    public function getName() {
        return $this->name;
    }

    public function getSql() {
        if (!empty($this->_sql)) {
            return $this->_sql;
        }
        $this->_sql = $this->dbRef->quotedName($this->columnName) . ' BETWEEN';
        $this->_sql .= ' ' . $this->dbRef->quotedValue($this->low);
        $this->_sql .= ' AND';
        $this->_sql .= ' ' . $this->dbRef->quotedValue($this->high);
        return $this->_sql;
    }
}

class Zpdo_SubWhere extends Zpdo_Zfunc {
    private $data = null;

    public function __construct(array $args) {
        if (empty($args)) {
            throw new RuntimeException('Illegal Arguments!Empty contents in where method!');
        }
        $this->data = $args;
    }

    public function getName() {
        return 'sub where';
    }

    public function getSql() {
        if (!empty($this->_sql)) {
            return $this->_sql;
        }
        $this->_sql = '(';
        $this->_sql .= $this->dbRef->whereCondition($this->data);
        $this->_sql .= ' )';
        return $this->_sql;
    }
}

/**
 * 为了不提供业务开发调用new Zpdo_If使用,参数用array来表示了.
 * Class (Zpdo_If)
 * @author: zhaoxi
 */
class Zpdo_If extends Zpdo_Zfunc {

    private $expr = null;
    private $ifval1 = null;
    private $ifval2 = null;

    private $comparisonOp = null;
    private $comparisonVal = null;

    public function __construct($expr, $ifval1, $ifval2, $comparisonOp = null, $comparisonVal = null) {
        if (empty($expr)) {
            throw new RuntimeException('Illegal Arguments!Empty $expr!');
        }
        $this->expr = $expr;
        $this->ifval1 = $ifval1;
        $this->ifval2 = $ifval2;

        $this->comparisonOp = $comparisonOp;
        $this->comparisonVal = $comparisonVal;

    }

    public function getName() {
        return 'IF';
    }

    public function getSql() {
        if (!empty($this->_sql)) {
            return $this->_sql;
        }

        if (is_array($this->expr) && count($this->expr) === 3) {

            $i = 0;
            $col = $this->expr[0];
            $comparisonOp = $this->expr[1];
            $valOrColumnArray = $this->expr[2];
            if (!is_array($valOrColumnArray) || count($valOrColumnArray) !== 2) {
                throw new RuntimeException('Ilegal Arguments!Please check $valOrColumnArray, need arg type.');
            } else {
                if (!in_array($comparisonOp, Zpdo_Simple::$comparisonOps)) {
                    throw new RuntimeException('Ilegal Arguments!Please check $comparisonOp: ' . $comparisonOp);
                }
                $this->_sql = 'IF(';
                $this->_sql .= $this->dbRef->quotedName($col);
                $this->_sql .= $comparisonOp;
                $this->_sql .= $this->getV2Sql($valOrColumnArray);
                $this->_sql .= ', ' . $this->getV2Sql($this->ifval1);
                $this->_sql .= ', ' . $this->getV2Sql($this->ifval2);
                $this->_sql .= ')';

                if (!is_null($this->comparisonOp) && in_array($this->comparisonOp, Zpdo_Simple::$comparisonOps)) {
                    $this->_sql .= ' ' . $this->comparisonOp;
                    $this->_sql .= ' ' . $this->dbRef->quotedValue($this->comparisonVal);
                }
            }
        } else {
            throw new RuntimeException('Illegal Arguments!Please check the $expr is not array!');
        }

        return $this->_sql;
    }

    private function getV2Sql($varAndType) {
        $sql = '';
        $ifval = $varAndType[0];
        $type = $varAndType[1];
        if ($type < Zpdo_Simple::ARGUMENT_TYPE_MIN || $type > Zpdo_Simple::ARGUMENT_TYPE_MAX) {
            throw new RuntimeException('Ilegal Arguments!Please check $valOrColumnArray, need arg type.');
        }
        switch ($type) {
            case Zpdo_Simple::ARGUMENT_TYPE_VALUE :
            {
                $sql .= $this->dbRef->quotedValue($ifval);
                break;
            }
            case Zpdo_Simple::ARGUMENT_TYPE_COLUMN :
            {
                $sql .= $this->dbRef->quotedName($ifval);
                break;
            }
            case Zpdo_Simple::ARGUMENT_TYPE_EXPR :
            {
                if (!$ifval instanceof Zpdo_Zfunc) {
                    throw new RuntimeException('Illegal Arguments!Not value , column , expr!');
                }
                $sql .= '(' . $ifval->getSql() . ')';
                break;
            }
            default :
                {
                throw new RuntimeException('Illegal call!');
                }
        }
        return $sql;
    }

}

/**
 * @property Zpdo_Simple _instance
 */
class Zpdo_Simple implements Zpdo_SimpleTable {
    private static $orderType = array('DESC', 'ASC');
    private static $updateSetOps = array('=', '+', '-', '*', '/', '%', '<<', '>>', '&', '|', '^');
    private static $comparisonOps4PreHandle = array(
        '<>' => ' <>', '>=' => ' >=',
        '<=' => ' <=', '!=' => ' !=',
        '=' => ' =',
        '<' => ' <', '>' => ' >'
    );
    public static $comparisonOps = array('=', '<', '>', '>=', '<=', '!=', '<>', 'LIKE');
    public static $whereLogic = array('AND', 'OR');

    private static $maxRowCount = 50000;


    public static $defaultTwittersDB = 'ctu'; // master
    public static $twittersSlaveDB = 'twitters_slave'; // slave

    private static $refs = array();

    private function __construct($deployedDbName = null) {
        $this->init($deployedDbName);
    }

    // See interface SimpleTable.
    // @authors: zhaoxi
    // Zpdo_Simple::instance('Users') vs new Zpdo_Simple('Users')
    // so static 方法调用一点也不方便.
    // 开销上,在php方便,我能做的一样的.
    /**
     * 简单工厂: 获得一个操作的实例.
     * @param null $deployedDbName
     * @return Zpdo_Simple
     */
    public static function instance($deployedDbName = null) {
        if (empty($deployedDbName)) {
            if (!isset(self::$refs['default'])) {
                self::$refs['default'] = new Zpdo_Simple();
            }
            return self::$refs['default'];
        } else {
            if (!isset(self::$refs[$deployedDbName])) {
                self::$refs[$deployedDbName] = new Zpdo_Simple($deployedDbName);
            }
            return self::$refs[$deployedDbName];
        }
    }

    private $_inTransaction = false;
    // CRUD
    private $_insert = null;
    private $_select = null;
    private $_update = null;
    private $_delete = null;
    // CRUD

    protected $_deployedDbName = null;
    /**
     * @var $_deployedDb PDO
     */
    protected $_deployedDb = null;
    /**
     * 数据库对象
     * @var $_db PDO
     */
    protected $_db = null;
    /**
     * 数据库对象
     * @var $_twitterDb PDO
     */
    protected $_twitterDb = null;


    /**
     * schema name
     * @var string $_schema
     */
    protected $_schema = null;
    /**
     * 表名
     * @var string $_tableName
     */
    protected $_tableName = null;


    /****************************************sql 拼接的临时变量 begin   *****/
    /**
     * 如果每个变量都有"前继变量",就在当前的变量值头部加上空格.
     * 请勿在尾部加空格.
     */
    //
    protected $_sql = null;

    protected $_where = null;

    protected $_orderBy = null;

    protected $_limit = null;

    protected $_groupBy = null;

    protected $_having = null;

    protected $_lastInsertId = null;

    /**
     * Data Manipulation Statements Type
     * @var $_type
     */
    private $_type = null;
    private $_isInserts = null;

    /**
     * 被新增/修改的数据内容
     * @var string|null
     */
    private $_dataContent = null;
    private $_insertPrefix = null;
    private $_duplicateKeyUpdate = null;
    private $_replacePrefix = null;
    private $_forceIndex = null;


    /****************************************sql 拼接的临时变量 end   *****/

    /**
     * 插入一条新数据
     * @param array $arr
     * @param string $PRIORITY
     * @param null|bool $ignore
     * @return $this
     * @throws RuntimeException
     */
    public function insert(array $arr, $PRIORITY = Zpdo_Simple::INSERT_DEFAULT_PRIORITY, $ignore = null) {
        switch ($PRIORITY) {
            case null:
                $PRIORITY = Zpdo_Simple::INSERT_DEFAULT_PRIORITY;
                break;
            case Zpdo_Simple::INSERT_DEFAULT_PRIORITY :
                break;
            case Zpdo_Simple::INSERT_LOW_PRIORITY:
                break;
            case Zpdo_Simple::INSERT_DELAYED:
                break;
            case Zpdo_Simple::INSERT_HIGH_PRIORITY:
                break;
            default:
                throw new RuntimeException('Illegal Arguments!$PRIORITY');
        }
        if (empty($arr) || !(is_null($ignore) || is_bool($ignore))) {
            throw new RuntimeException('Illegal Arguments!$ignore');
        }
        if (is_array(reset($arr))) {
            throw new RuntimeException('Illegal Call!See the other method: inserts!');
        }

        $this->_clear();
        $this->_type = self::DML_INSERT;

        $this->_insertPrefix = 'INSERT' . $PRIORITY;
        if ($ignore === true) {
            $this->_insertPrefix .= ' IGNORE ';
        }

        $keys = array_keys($arr);
        $values = array_values($arr);

        $qkeys = array();
        foreach ($keys as $key) {
            $qkeys[] = self::quotedName($key);
        }
        foreach ($values as &$v) {
            $v = $this->quotedValue($v);
        }
        $this->_dataContent = ' (' . implode(',', $qkeys) . ') VALUE (';
        $this->_dataContent .= implode(',', $values);
        $this->_dataContent .= ')';
        unset($values);
        unset($keys);
        return $this;
    }


    public function inserts(array $list, $PRIORITY = Zpdo_Simple::INSERT_DEFAULT_PRIORITY, $ignore = null) {
        switch ($PRIORITY) {
            case null:
                break;
            case Zpdo_Simple::INSERT_DEFAULT_PRIORITY :
                break;
            case Zpdo_Simple::INSERT_LOW_PRIORITY:
                break;
            case Zpdo_Simple::INSERT_DELAYED:
                break;
            case Zpdo_Simple::INSERT_HIGH_PRIORITY:
                break;
            default:
                throw new RuntimeException('Illegal Arguments!');
        }
        if (!(is_null($ignore) || is_bool($ignore))) {
            throw new RuntimeException('Illegal Arguments!$ignore');
        }
        $this->_clear();
        $this->_type = self::DML_INSERT;
        $this->_isInserts = true;

        $this->_insertPrefix = 'INSERT' . $PRIORITY;
        if ($ignore === true) {
            $this->_insertPrefix .= ' IGNORE ';
        }

        $keys = current($list);
        $qkeys = array();
        foreach ($keys as $k => $v) {
            $qkeys[] = self::quotedName($k);
        }

        foreach ($list as $z => $row) {
            foreach ($row as $k => $v) {
                $row[$k] = $this->quotedValue($v);
            }
            $list[$z] = '(' . implode(',', $row) . ')';
        }
        $this->_dataContent .= ' (' . implode(',', $qkeys) . ') VALUES ';
        $this->_dataContent .= implode(',', $list);
        unset($keys);
        unset($qkeys);
        return $this;
    }

    //END func inserts


    /**
     * 动态参数
     * @throws RuntimeException
     * @return $this
     */
    public function select() {
        $this->_clear();
        $this->_type = self::DML_SELECT;
        $args = func_get_args();
        if (empty($args)) {
            $this->_select = 'SELECT *';
        } else {
            if (count($args) == 1) {
                $arr = reset($args);
                if (is_array($arr)) {
                    $args = $arr;
                }
            }

            $tmp = array();
            foreach ($args as $v) {
                if (is_string($v)) {
                    // is columnName
                    $v = trim($v);
                    if ($v !== '') {
                        if ('*' !== $v) {
                            if (strlen($v) > 5 && preg_match("/\s|`/", $v) ) {
                                throw new RuntimeException('Illegal Arguments!It is not column nor func!Are you kidding me?You are teddy girl/boy.');
                            }
                            $tmp[] = ' ' . $this->quotedName($v);
                        } else {
                            // * : all columns
                            $tmp[] = ' ' . $v;
                        }
                    }
                } else if ($v instanceof Zpdo_Zfunc) {
                    $tmp[] = $v->getSql();
                    $v->clear();
                }
            }
            $this->_select = 'SELECT' . implode(',', $tmp);
            unset($tmp);
        }
        return $this;
    }


    /**
     *
     * replace 一整条记录
     * @param array $arr
     * @param string $PRIORITY
     * @throws RuntimeException
     * @return int
     */
    public function replace(array $arr, $PRIORITY = self::REPLACE_DEFAULT_PRIORITY) {
        if (empty($arr)) {
            throw new RuntimeException('Illegal Arguments!$ignore');
        }
        switch ($PRIORITY) {
            case null:
                $PRIORITY = self::REPLACE_DEFAULT_PRIORITY;
                break;
            case self::REPLACE_DEFAULT_PRIORITY :
                break;
            case self::REPLACE_LOW_PRIORITY:
                break;
            case self::REPLACE_DELAYED:
                break;
            default:
                throw new RuntimeException('Illegal Arguments!$PRIORITY');
        }

        $this->_clear();
        $this->_type = self::DML_REPLACE;


        $this->_replacePrefix = 'REPLACE' . $PRIORITY;

        $keys = array_keys($arr);
        $values = array_values($arr);

        $qkeys = array();
        foreach ($keys as $key) {
            $qkeys[] = self::quotedName($key);
        }
        foreach ($values as &$v) {
            $v = $this->quotedValue($v);
        }
        $this->_dataContent = ' (' . implode(',', $qkeys) . ') VALUE ( ';
        $this->_dataContent .= implode(',', $values);
        $this->_dataContent .= ') ';
        unset($values);
        unset($keys);
        return $this;
    }


    public function delete() {
        $this->_clear();
        $this->_type = self::DML_DELETE;
        return $this;
    }


    // See interface SimpleTable
    public function update(array $col2Val) {
        if (empty($col2Val)) {
            throw new RuntimeException('Illegal Arguments!');
        }
        $this->_clear();
        $this->_type = self::DML_UPDATE;

        $tmp = array();
        foreach ($col2Val as $k => $v) {
            if (!$v instanceof Zpdo_Zfunc) {
                if (is_string($k)) {
                    $kAndOp = explode(' ', trim($k));
                    if (empty($kAndOp) || count($kAndOp) < 2) {
                        throw new RuntimeException('Illegal Arguments! Has no operator. $k: ' . $k);
                    }
                    $col = self::quotedName(reset($kAndOp));
                    $op = strtoupper(end($kAndOp));
                    if ($op === '=') {
                        $tmp[] = $col . ' = ' . $this->quotedValue($v);
                    } else if (in_array($op, Zpdo_Simple::$updateSetOps)) {
                        $tmp[] = $col . ' = ' . $col . $op . $this->quotedValue($v);
                    } else {
                        throw new RuntimeException('Illegal Argument!Key: ' . $k . ', Value:' . $v);
                    }
                    unset($kAndOp);
                }
            } else {
                $tmp[] = $v->getSql();
                $v->clear();
            }
        }
        $this->_dataContent = ' SET ' . implode(' ,', $tmp);
        return $this;
    }

    public function whereCondition(array $args){
        if (empty($args)) {
            throw new RuntimeException('Illegal Arguments!Empty contents in where method!');
        }
        $count = count($args);
        if (self::isEven($count)) {
            // even 0,2,4,...
            throw new RuntimeException('Illegal Argument Count in where! ' . $count);
        }
        $tmpS = ' ';
        $i = 0;
        foreach ($args as $k => $v) {
            if (self::isEven($i)) {
                // e.g.  'id >=' => 1 , 'title like' => 'sda%'
                if ((!$v instanceof Zpdo_Zfunc)) {
                    $kAndOp = explode(' ', trim($k));
                    if (empty($kAndOp) || count($kAndOp) < 2) {
                        throw new RuntimeException('Illegal Arguments: column and op must! Check the space! Key: ' . $k . ' . Value: ' . $v);
                    }
                    $col = reset($kAndOp);
                    $op = strtoupper(end($kAndOp));
                    $_sql = null;
                    if (in_array($op, Zpdo_Simple::$comparisonOps)) {
                        switch ($op) {
                            case 'LIKE' :
                            {
                                $_sql = $this->_like($col, $op, $v);
                                break;
                            }
                            default :
                                {
                                $_sql = $this->defaultOneWhere($col, $op, $v);
                                }
                        }
                        // 空格特殊处理
                        $tmpS .= $_sql;
                    } else {
                        throw new RuntimeException('Illegal Argument: op not be allowed!Key: ' . $k . ', Value:' . $v);
                    }
                    unset($kAndOp);
                } else {
                    // 空格特殊处理
                    $tmpS .= $v->getSql();
                    $v->clear();
                }
            } else {
                // 空格特殊处理
                $v = strtoupper($v);
                if (in_array($v, Zpdo_Simple::$whereLogic)) {
                    $tmpS .= ' ' . $v . ' ';
                } else {
                    throw new RuntimeException('Illegal Argument!Index:' . $i . ', Value:' . $v);
                }
            }
            $i++;
        }
        return $tmpS;
    }

    // See interface SimpleTable
    public function where(array $args) {
        $this->_where = ' WHERE';
        $this->_where .= $this->whereCondition($args);
        return $this;
    }


    public function orderBy() {
        $args = func_get_args();
        if (empty($args)) {
            return $this;
        }
        $count = count($args);
        if (self::isOdd($count)) {
            throw new RuntimeException('Illegal Argument! Has neither DESC nor ASC');
        }
        if ($count > 6) {
            throw new RuntimeException('Max: two columns for ordering!');
        }
        $this->_orderBy = ' ORDER BY ';
        $cols = array();
        for ($i = 0; $i < $count; $i = $i + 2) {
            $col = $this->quotedName($args[$i]);
            $orderType = strtoupper($args[$i + 1]);
            if (in_array($orderType, self::$orderType)) {
                $cols[] = $col . ' ' . $orderType;
            } else {
                throw new RuntimeException('Illegal Argument!Unknown order type: ' . $args[$i + 1]);
            }
            unset($col);
            unset($orderType);
        }
        $this->_orderBy .= implode(',', $cols);
        return $this;
    }

    // See interface SimpleTable
    /**
     * mysql syntax
     * @param $offsetOrRows int
     * @param $rows int
     *   0 is invalid
     * @throws RuntimeException
     * @return $this
     */
    public function limit($offsetOrRows, $rows = 0) {
        if ((!is_numeric($offsetOrRows)) || (!is_numeric($rows))) {
            throw new RuntimeException('Illegal value ');
        }
        if ($rows > 0) {
            $rowCount = min($rows, self::$maxRowCount);
            // 两个参数都有
            $this->_limit = ' LIMIT ' . $offsetOrRows . ',' . $rowCount;
        } else {
            $rowCount = min($offsetOrRows, self::$maxRowCount);
            $this->_limit = ' LIMIT ' . $rowCount;
        }
        return $this;
    }


    /**
     * // See interface SimpleTable
     * @param null $isLastId
     * @throws RuntimeException
     * @return int
     */
    public function exec($isLastId = null) {
        // valid completed sql:
        switch ($this->_type) {
            case self::DML_SELECT:
                throw new RuntimeException('Illegal the way of calling!See methods: fetch*()!');
            case self::DML_INSERT:
                // insert
                if (empty($this->_dataContent)) {
                    throw new RuntimeException('Illegal Arguments! Have no key-value pairs.');
                }
                break;
            case self::DML_UPDATE:
                // update
                if (empty($this->_where) || empty($this->_limit)) {
                    throw new RuntimeException('Illegal Arguments! Have neither where nor limit.');
                }
                break;
            case self::DML_DELETE:
                // delete
                if (empty($this->_where) || empty($this->_limit)) {
                    throw new RuntimeException('Illegal Arguments! Have neither where nor limit.');
                }
                break;
        }

        $db = $this->getCompatibleDb();
        // $res init: excepted effected row
        if (!$this->inTransaction()) {
            // mogujie Framework , Zpdo_DonQuiXote | Zpdo_RedSea
            $res = $db->the_exec($this->getSql());
        } else {
            // mogujie Framework , Zpdo_Natural | Zpdo_RedSea
            $res = $db->the_exec($this->getSql(), null, true);
        }
        switch ($this->_type) {
            case self::DML_INSERT:
                // insert : lastId.
                // inserts() : rows ,  inserts(true) : lastId
                if ($this->_isInserts !== true || ($this->_isInserts === true && $isLastId === true)) {
                    $this->_lastInsertId = intval($db->lastInsertId());
                    $res = $this->_lastInsertId;
                }
                break;
            case self::DML_UPDATE:
                break;
            case self::DML_DELETE:
                break;
            case self::DML_REPLACE:
                break;
            default :
                {
                break;
                }
        }
        $this->_clear();
        return $res;
    }

    // See interface SimpleTable
    public function fetchRow() {
        if (empty($this->_where) || empty($this->_limit)) {
            throw new RuntimeException('Illegal calling! Has neither where nor limit!');
        }
        $this->_limit = ' LIMIT ' . 1;
        $db = $this->getCompatibleDb();
        $row = $db->the_one($this->getSql());
        $this->_clear();
        return $row;
    }

    // See interface SimpleTable
    public function fetchAll() {
        if (empty($this->_where) || empty($this->_limit)) {
            throw new RuntimeException('Illegal calling! Has neither where nor limit!');
        }
        $db = $this->getCompatibleDb();
        $res = $db->the_all($this->getSql());
        $this->_clear();
        return $res;
    }

    // See interface SimpleTable
    public function lastInsertId() {
        return $this->_lastInsertId;
    }

    // See interface SimpleTable
    public function groupBy($column1, $column2 = null) {
        if (empty($column1)) {
            throw new RuntimeException('Illegal Arguments!');
        }
        if (strlen($column1) > 5 && preg_match("/\s|`/", $column1)) {
            throw new RuntimeException('Illegal Arguments!It is not column nor func!Are you kidding me?You are teddy girl/boy.');
        }
        $this->_groupBy = ' GROUP BY ' . $this->quotedName($column1) . ' ';
        if (!empty($column2)) {
            if (strlen($column2) > 5 && preg_match("/\s|`/", $column2)) {
                throw new RuntimeException('Illegal Arguments!It is not column nor func!Are you kidding me?You are teddy girl/boy.');
            }
            $this->_groupBy .= ',' . $this->quotedName($column2) . ' ';
        }
        return $this;
    }

    public function having(array $args) {
        $this->_having = ' HAVING';
        $this->_having .= $this->whereCondition($args);
        return $this;
    }

    // See interface SimpleTable
    public function duplicate_key_update(array $col2Val) {
        if (empty($col2Val)) {
            throw new RuntimeException('Illegal Arguments! Empty!');
        }

        $tmp = array();
        foreach ($col2Val as $k => $v) {
            if (!$v instanceof Zpdo_Zfunc) {
                if (is_string($k)) {
                    $kAndOp = explode(' ', trim($k));
                    if (empty($kAndOp) || count($kAndOp) < 2) {
                        throw new RuntimeException('Illegal Arguments! $k: ' . $k);
                    }
                    $col = self::quotedName(reset($kAndOp));
                    $op = strtoupper(end($kAndOp));
                    if ($op === '=') {
                        $tmp[] = $col . ' = ' . $this->quotedValue($v);
                    } else if (in_array($op, Zpdo_Simple::$updateSetOps)) {
                        $tmp[] = $col . ' = ' . $col . $op . $this->quotedValue($v);
                    } else {
                        throw new RuntimeException('Illegal Argument!Key: ' . $k . ', Value:' . $v);
                    }
                    unset($kAndOp);
                }
            } else {
                throw new RuntimeException('Unsupported operation!');
            }
        }
        $this->_duplicateKeyUpdate = ' ON DUPLICATE KEY UPDATE ' . implode(' ,', $tmp) . ' ';
    }

    // See interface SimpleTable
    private function _clear() {
        $this->_sql = null;

        $this->_where = null;

        $this->_orderBy = null;

        $this->_limit = null;

        $this->_groupBy = null;
        $this->_having = null;

        $this->_lastInsertId = null;

        $this->_type = null;
        $this->_dataContent = null;
        $this->_insertPrefix = null;
        $this->_replacePrefix = null;
        $this->_forceIndex = null;

        $this->_isInserts = null;
    }

    public function quote($d) {
        return $this->_db->quote($d);
    }

    /**
     *
     * name: DatabaseName, TableName, ColumnName ...
     * @param $name
     * @return string
     */
    public function quotedName($name) {
        $name = $this->quotedValue($name);
        $name = trim($name, "' \t\n\r");
        return '`' . $name . '`';
    }


    public function quotedValue($v) {
        if (is_null($v)) {
            $str = 'NULL';
        } elseif ($this->is_num4SQL($v)) {
            $str = $v;
        } else {
            $str = $this->_db->quote($v);
        }
        return $str;
    }

    /**
     * 不允许有二进制的数值进入.
     * @param $v
     * @return bool
     * @throws RuntimeException
     */
    public function is_num4SQL($v) {
        if ($v instanceof Ztype) {
            switch ($v->type()) {
                case 'string':
                    return false;
                case 'integer':
                {
                    if (is_numeric($v->value())) {
                        return true;
                    } else {
                        throw new RuntimeException('SQL Injection happens!!! zint($v) , $v:' . $v);
                    }
                }
                default :
                    {
                    throw new RuntimeException('Illegal Arguments! Invalid Ztype function, return type:' . $v->type());
                    }
            }
        }
        return is_numeric($v);
    }

    /**
     * 获取暂时已经生成的sql语句
     * // See interface SimpleTable
     * @return mixed
     */
    public function getSql() {
        switch ($this->_type) {
            case self::DML_SELECT :
                // select
                $this->_sql = $this->_select;
                $this->_sql .= ' FROM' . $this->getQuotedTableInSql();
                if (!empty($this->_forceIndex)) {
                    $this->_sql .= $this->_forceIndex;
                }
                if (!empty($this->_where)) {
                    $this->_sql .= $this->_where;
                }
                if (!empty($this->_groupBy)) {
                    $this->_sql .= $this->_groupBy;
                }
                if (!empty($this->_having)) {
                    $this->_sql .= $this->_having;
                }
                if (!empty($this->_orderBy)) {
                    $this->_sql .= $this->_orderBy;
                }
                $this->_sql .= $this->_limit;
                break;
            case self::DML_INSERT:
                // insert
                $this->_sql = $this->_insertPrefix;
                $this->_sql .= ' INTO' . $this->getQuotedTableInSql();
                $this->_sql .= $this->_dataContent;
                if (!empty($this->_duplicateKeyUpdate)) {
                    $this->_sql .= $this->_duplicateKeyUpdate;
                }
                break;
            case self::DML_UPDATE:
                // update
                $this->_sql = 'UPDATE' . $this->getQuotedTableInSql();
                $this->_sql .= $this->_dataContent;
                $this->_sql .= $this->_where;
                if (!empty($this->_orderBy)) {
                    $this->_sql .= $this->_orderBy;
                }
                $this->_sql .= $this->_limit;
                break;
            case self::DML_DELETE:
                // delete
                $this->_sql = 'DELETE FROM' . $this->getQuotedTableInSql();
                $this->_sql .= $this->_where;
                if (!empty($this->_orderBy)) {
                    $this->_sql .= $this->_orderBy;
                }
                $this->_sql .= $this->_limit;
                break;
            case self::DML_REPLACE:
                // replace
                $this->_sql = $this->_replacePrefix;
                $this->_sql .= ' INTO' . $this->getQuotedTableInSql();
                $this->_sql .= $this->_dataContent;
                if (!empty($this->_limit)) {
                    $this->_sql .= $this->_limit;
                }
                break;
        }
        return $this->_sql;
    }

    // See interface SimpleTable
    public function getSqlType() {
        return $this->_type;
    }

    /**
     * See interface SimpleTable
     * @param $table
     * @param null $schema
     * @return $this
     * @throws RuntimeException
     */
    public function from($table, $schema = null) {
        if (empty($table) && empty($this->_tableName)) {
            throw new RuntimeException('Empty table name!');
        }
        $this->_tableName = $table;

        if (empty($schema)) {
            $this->_schema = null;
        } else {
            $this->_schema = $schema;
        }
        return $this;
    }


    // See interface SimpleTable
    public function into($table, $schema = null) {
        return $this->from($table, $schema);
    }


    // See interface SimpleTable
    public function close() {
        $this->_inTransaction = false;
        $this->_insert = null;
        $this->_select = null;
        $this->_update = null;
        $this->_delete = null;

        $this->_schema = null;
        $this->_tableName = null;

        $this->_clear();
    }

    /**
     * 1 , 3, ...
     * @param $num
     * @return bool
     */
    public static function isOdd($num) {
        return ($num & 1) != 0;
    }

    /**
     * 0 , 2, ...
     * @param $num
     * @return bool
     */
    public static function isEven($num) {
        return ($num & 1) == 0;
    }


    private function getQuotedTableInSql() {
        $res = null;
        if (!empty($this->_schema)) {
            $res = ' ' . $this->quotedName($this->_schema) . '.' . $this->quotedName($this->_tableName);
        } else {
            $res = ' ' . $this->quotedName($this->_tableName);
        }
        return $res;
    }


    private function init($deployedDbName) {
        $this->close();
        $this->_deployedDbName = $deployedDbName;
        $this->_db = Zpdo::instance($deployedDbName);
        $this->_deployedDb = null;
        if ($deployedDbName === self::$twittersSlaveDB) {
            $this->_twitterDb = Zpdo::instance($deployedDbName);
            $this->_deployedDb = & $this->_twitterDb;
        } else if ($deployedDbName === self::$defaultTwittersDB) {
            $this->_twitterDb = Zpdo::instance(self::$defaultTwittersDB);
            $this->_deployedDb = & $this->_twitterDb;
        } else {
            $this->_twitterDb = Zpdo::instance(self::$defaultTwittersDB);
            $this->_deployedDb = & $this->_db;
        }
    }

    /**
     * 有坑!!! 初始化没有带tableName,会导致twitter表不在相应的库上.
     * 避免坑, 多设置一个_deployedDb
     * @throws RuntimeException
     * @return Zpdo_DonQuiXote | Zpdo_Natural | Zpdo_RedSea
     */
    private function getCompatibleDb() {
        if (empty($this->_tableName)) {
            $db = $this->_deployedDb;
        } else {
            if ($this->_tableName !== 'Twitters') {
                $db = $this->_db;
            } else {
                $db = $this->_twitterDb;
            }
        }
        return $db;
    }

    private function _isNot($col, $op, $v) {
        $op = 'IS NOT';
        return $this->defaultOneWhere($col, $op, $v);
    }

    private function _in($col, $op, $v) {
        if (!is_array($v)) {
            throw new RuntimeException('Using IN,  value must be an array!');
        }
        $cnt = count($v);
        if ($cnt < 1) {
            throw new RuntimeException('The value array is empyy!');
        }
        $appendedCnt = 0;
        $resultVal = '';
        foreach ($v as $val) {
            $resultVal .= $this->quotedValue($val);
            if (($appendedCnt = $appendedCnt + 1) < $cnt) {
                $resultVal .= ',';
            }
        }
        return $this->quotedName($col) . ' ' . $op . ' (' . $resultVal . ')';
    }

    public function _like($col, $op, $v) {
        if (substr($v, 0, 1) === '%') {
            throw new RuntimeException('Illegal Argument contains %...!ColumnName: ' . $col . ', Value:' . $v);
        }
        return $this->defaultOneWhere($col, $op, $v);
    }

    public function defaultOneWhere($col, $op, $v) {
        // 空格特殊处理
        return $this->quotedName($col) . ' ' . $op . ' ' . $this->quotedValue($v);
    }

    /**
     * @param $column
     * @param $type
     * @param array $qData
     * @throws
     * @return Zpdo_Zfunc
     */
    public static function in($column, $type, array $qData) {
        $dao = Zpdo_Simple::getMe();
        $func = new Zpdo_In($column, $type, $qData);
        $func->setDbRef($dao);
        return $func;
    }


    /**
     * @param $column
     * @param $type
     * @param array $qData
     * @throws
     * @return Zpdo_Zfunc
     */
    public static function notin($column, $type, array $qData) {
        $dao = Zpdo_Simple::getMe();
        $func = new Zpdo_Notin($column, $type, $qData);
        $func->setDbRef($dao);
        return $func;
    }


    /**
     * @deprecated  see @Method isNull($column)
     * where $column is null
     * @param $column
     * @return Zpdo_Zfunc
     */
    public static function is($column) {
        $dao = Zpdo_Simple::getMe();
        $inFunc = new Zpdo_Is($column);
        $inFunc->setDbRef($dao);
        return $inFunc;
    }

    /**
     * where $column is null
     * @param $column
     * @return Zpdo_Zfunc
     */
    public static function isNull($column) {
        $dao = Zpdo_Simple::getMe();
        $inFunc = new Zpdo_IsNull($column);
        $inFunc->setDbRef($dao);
        return $inFunc;
    }


    /**
     * @deprecated  see @Method isNotNull($column)
     * where $column is not null
     * @param $column
     * @return Zpdo_Zfunc
     */
    public static function isnot($column) {
        $dao = Zpdo_Simple::getMe();
        $func = new Zpdo_Isnot($column);
        $func->setDbRef($dao);
        return $func;
    }

    /**
     * where $column is not null
     * @param $column
     * @return Zpdo_Zfunc
     */
    public static function isNotNull($column) {
        $dao = Zpdo_Simple::getMe();
        $func = new Zpdo_IsNotNull($column);
        $func->setDbRef($dao);
        return $func;
    }


    /**
     * @param $column
     * @param null $alias
     * @return Zpdo_Zfunc
     */
    public static function distinct($column, $alias = null) {
        $dao = Zpdo_Simple::getMe();
        $func = new Zpdo_Distinct($column, $alias);
        $func->setDbRef($dao);
        return $func;
    }


    /**
     * @param $columnOrFunc
     * @param null $alias
     * @return Zpdo_Count
     */
    public static function count($columnOrFunc, $alias = null) {
        $dao = Zpdo_Simple::getMe();
        $func = new Zpdo_Count($columnOrFunc, $alias);
        $func->setDbRef($dao);
        return $func;
    }

    /**
     * @param $columnOrFunc
     * @param null $alias
     * @return Zpdo_Max
     */
    public static function max($columnOrFunc, $alias = null) {
        $dao = Zpdo_Simple::getMe();
        $func = new Zpdo_Max($columnOrFunc, $alias);
        $func->setDbRef($dao);
        return $func;
    }

    /**
     * @param $columnOrFunc
     * @param null $alias
     * @return Zpdo_Min
     */
    public static function min($columnOrFunc, $alias = null) {
        $dao = Zpdo_Simple::getMe();
        $func = new Zpdo_Min($columnOrFunc, $alias);
        $func->setDbRef($dao);
        return $func;
    }

    /**
     * @return Zpdo_Zfunc
     */
    public static function expr() {
        $args = func_get_args();
        $dao = Zpdo_Simple::getMe();
        $func = new Zpdo_Expr();
        $func->setDbRef($dao);
        $func->setExpresses($args);
        return $func;
    }


    /**
     * @param $columnOrFunc
     * @param null $alias
     * @return Zpdo_Sum
     */
    public static function sum($columnOrFunc, $alias = null) {
        $dao = Zpdo_Simple::getMe();
        $func = new Zpdo_Sum($columnOrFunc, $alias);
        $func->setDbRef($dao);
        return $func;
    }

    /**
     * mysql as 别名使用.
     * note: mehtod name , as是php关键字
     * @param $columnOrFunc
     * @param $alias
     * @return Zpdo_As
     */
    public static function sqlAs($columnOrFunc, $alias) {
        $dao = Zpdo_Simple::getMe();
        $func = new Zpdo_As($columnOrFunc, $alias);
        $func->setDbRef($dao);
        return $func;
    }


    public static function between($columnName, $low, $high) {
        $dao = Zpdo_Simple::getMe();
        $func = new Zpdo_Between($columnName, $low, $high);
        $func->setDbRef($dao);
        return $func;
    }

    public static function subWhere(array $args) {
        $dao = Zpdo_Simple::getMe();
        $func = new Zpdo_SubWhere($args);
        $func->setDbRef($dao);
        return $func;
    }

    /**
     * @param $expr
     * array(
     *      $column,
     *      $comparisonOp,
     *      $valOrColumn : array($var, $type)
     * )
     * @param $val1
     *    expr or column or val.
     * array(
     *      $var, $type
     * )
     * @param $val2
     *      expr or column or val.
     * array(
     *      $var, $type
     * )
     * @param null $comparisonOp
     * @param null $comparisonVal
     * @return Zpdo_If
     */
    public static function sqlIf($expr, $val1, $val2, $comparisonOp = null, $comparisonVal = null) {
        $dao = Zpdo_Simple::getMe();
        $func = new Zpdo_If($expr, $val1, $val2, $comparisonOp, $comparisonVal);
        $func->setDbRef($dao);
        return $func;
    }

    public function forceIndex() {
        $indexList = func_get_args();
        if (!empty($indexList)) {
            $this->_forceIndex = ' FORCE INDEX(';
            $count = count($indexList);
            $appendedCnt = 0;
            foreach ($indexList as $index) {
                $this->_forceIndex .= $this->quotedName($index);
                if (($appendedCnt = $appendedCnt + 1) < $count) {
                    $this->_forceIndex .= ',';
                }
            }
            $this->_forceIndex .= ')';
        }
        return $this;
    }

    public function useIndex() {
        $indexList = func_get_args();
        if (!empty($indexList)) {
            $this->_forceIndex = ' USE INDEX(';
            $count = count($indexList);
            $appendedCnt = 0;
            foreach ($indexList as $index) {
                $this->_forceIndex .= $this->quotedName($index);
                if (($appendedCnt = $appendedCnt + 1) < $count) {
                    $this->_forceIndex .= ',';
                }
            }
            $this->_forceIndex .= ')';
        }
        return $this;
    }

    /**
     * @return Zpdo_Simple
     * @throws RuntimeException
     */
    private static function getMe() {
        if (empty(self::$refs)) {
            self::instance();
        }
        return reset(self::$refs);
    }

    public function beginTransaction() {
        $this->getCompatibleDb()->beginTransaction();
        $this->_inTransaction = true;
    }

    public function commit() {
        $this->getCompatibleDb()->commit();
        $this->_inTransaction = false;
    }

    /**
     * 如果你调用这个,就会清空掉改实例上的私有属性.
     */
    public function rollback() {
        $this->getCompatibleDb()->rollBack();
        $this->close();
    }

    public function inTransaction() {
        if ($this->_inTransaction) {
            return $this->getCompatibleDb()->inTransaction();
        }
        return false;
    }
}//END class
