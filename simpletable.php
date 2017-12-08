<?php
/**
 * Created by PhpStorm.
 * User: zhaoxi
 * Date: 13-11-18
 * Time: 17:05
 */

interface Zpdo_SimpleTable {
    const INSERT_DEFAULT_PRIORITY = '';
    const INSERT_LOW_PRIORITY = ' LOW_PRIORITY';
    const INSERT_DELAYED = ' DELAYED';
    const INSERT_HIGH_PRIORITY = ' HIGH_PRIORITY';

    const REPLACE_DEFAULT_PRIORITY = '';
    const REPLACE_LOW_PRIORITY = ' LOW_PRIORITY';
    const REPLACE_DELAYED = ' DELAYED';


    //
    const ARGUMENT_TYPE_MIN = 1;
    const ARGUMENT_TYPE_MAX = 3;
    const ARGUMENT_TYPE_VALUE = 1;
    const ARGUMENT_TYPE_COLUMN = 2;
    const ARGUMENT_TYPE_EXPR = 3;


    // DML : 1 select,2 insert,3 update,4 delete,5 replace
    const DML_SELECT = 1;
    const DML_INSERT = 2;
    const DML_UPDATE = 3;
    const DML_DELETE = 4;
    const DML_REPLACE = 5;


    /**
     * 获得实例,单例模式
     * @param null $deployedDbName
     * @return Zpdo_SimpleTable
     */
    public static function instance($deployedDbName = null);

    /**
     * 插入单条数据
     * @param array $col2Val 要更新的内容
     * @param string $PRIORITY mysql语法中的priority,程序用常量表示
     * @param null|boolean $ignore mysql语法中的ignore
     * @return $this
     * @throws RuntimeException
     */
    public function insert(array $col2Val, $PRIORITY = self::INSERT_DEFAULT_PRIORITY, $ignore = null);

    /**
     * 插入很多条数据
     * @param array $records 多条数据
     *   per record(每一条数据): array $col2Val
     * @param string $PRIORITY mysql语法中的priority,程序用常量表示
     * @param null|boolean $ignore mysql语法中的ignore
     * @return $this
     * @throws RuntimeException
     */
    public function inserts(array $records, $PRIORITY = self::INSERT_DEFAULT_PRIORITY, $ignore = null);

    /**
     * @param array $col2Val
     * @param string $PRIORITY mysql语法中的priority,程序用常量表示
     * @return $this
     */
    public function replace(array $col2Val, $PRIORITY = self::REPLACE_DEFAULT_PRIORITY);


    /**
     * 要查询的结果列(可以是函数类型的表达式)
     * dynamic args.
     * per arg in dynamic args : columnName or Zpdo_Zfunc
     */
    public function select();

    /**
     * 要更新的内容.
     * 运算符或赋值支持: '=', '+', '-', '*', '/', '%', '<<', '>>', '&', '|', '^'
     * @param array $col2Val
     *  key: 包含列名和(运算符或赋值=),一定用英文空格分开.
     *  value: 实际值
     *      'id -' => 1             // sql: id = id - 1
     *      'id =' => 2             // sql: id = 2
     *      'id +' => 300           // sql: id = id + 300
     *      'name =' => 'yifu'      // sql: name = yifu
     * @return $this
     * @throws RuntimeException
     */
    public function update(array $col2Val);

    /**
     * MDL的delete操作,表示要开始删除数据,不要任何参数
     * @return $this
     */
    public function delete();

    /**
     * 要操作的表名
     * @param $table : string
     * @param null $schema
     * @return $this
     * @throws RuntimeException
     */
    public function from($table, $schema = null);

    /**
     * 要操作的表名
     * @param $table
     * @param null $schema
     * @return $this
     * @throws RuntimeException
     */
    public function into($table, $schema = null);


    /**
     * @param array $args
     * 不能为空.奇数位是key-value或是静态方法生成的一个对象,偶数位是逻辑关键字:'AND' 'OR'.
     * key-value :
     *  key: 包含列名和比较符,一定用英文空格分开.
     *  value: 实际值
     *      'id >=' => 1
     *      'id =' => 2
     *      'id <' => 300
     * <pre>
     * e.g. :
     *  where( array (
     *      'id >=' => 1 ,
     *      'OR',
     *      'name =' => 'yifu'
     *      'AND',
     *      'title like' => 'sda%'
     *  ))
     * </pre>
     * @return $this
     * @throws RuntimeException
     */
    public function where(array $args);

    /**
     * dynamic args
     * 默认可以为空.
     * 必须为偶数个参数.奇数位是列名,偶数为排序值desc/asc/DESC/ASC.
     * <pre>
     *  e.g.
     *  'id' , 'desc' , 'name' , 'asc'
     * </pre>
     *
     * @return $this
     * @throws RuntimeException
     */
    public function orderBy();

    /**
     * mysql syntax
     * @param $offsetOrRows int
     * @param $rows int
     *   0 is invalid
     * @return
     */
    public function limit($offsetOrRows, $rows = 0);

    /**
     * 最后要执行的sql的动作,并返回结果.
     * 执行后清空内部变量,及时释放内存占用.予便下个sql使用.
     * @param null $isLastId
     * @return int
     * update exec 返回影响行数,不区分事务与否
     * delete exec 返回影响行数,不区分事务与否
     * insert exec 单条新增,返回物理主键值(无论自增与否),不区分事务与否
     * inserts exec 多条新增,返回影响行数,   不区分事务与否
     * inserts exec 传入的(可选)参数指明需要返回的最后一条物理主键值
     * replace exec 执行单条记录,返回影响行数,不区分事务与否
     */
    public function exec($isLastId = null);

    /**
     * 执行后清空内部变量,及时释放内存占用.予便下个sql使用.
     * @return $record : array
     */
    public function fetchRow();

    /**
     * 执行后清空内部变量,予便下个sql使用.
     * @return  $records : array
     *      one record : array
     */
    public function fetchAll();

    /**
     * MDL insert 后返回的LastInsertedId
     * @return null|int
     */
    public function lastInsertId();

    /**
     * @param $column1
     * @param null | $column2
     * @return $this
     * @throws RuntimeException
     */
    public function groupBy($column1, $column2 = null);

    /**
     * @param array $col2Val
     *  key: 包含列名和(运算符或赋值=),一定用英文空格分开.
     *  value: 实际值
     *      'id -' => 1
     *      'id =' => 2
     *      'id +' => 300
     *
     * @return mixed
     */
    public function duplicate_key_update(array $col2Val);

    /**
     * 获得组装后的sql
     * @return string
     */
    public function getSql();

    public function quote($d);

    public function quotedName($name);

    public function quotedValue($value);

    public function close();


    /**
     * 不支持跨库.
     * @return mixed
     */
    public function beginTransaction();

    public function commit();

    public function rollback();

    public function inTransaction();
}