class Zpdo_Simple implements SimpleTable 使用方法
===========================
### 目标
- 100%防止sql注入 , 确保database系统的100%安全.
- 严格限制无where limit的条件, 确保交易安全 

### API限制
       只支持单表操作
       LIMIT中的rowCount,最大是 50,000
       [重要]默认将能转成numeric的变量全部转成numeric传递给mysql,为了去除这个隐式转换请用全局的zstr($val)函数将字符串传递给mysql. 这一点关乎mysql查询性能!!! 这是一个大坑!
       [重要]请不要使用非明确支持扩展类或函数.
(以下表格: +表示必选项, -表示可选项, ×表示不能使用的)

\       | select | update | insert | delete
--------|--------|--------|--------|--------
from    |+       |+       |×       |+
into    |×       |×       |+       |×
where   |+       |+       |×       |+
groupBy |-       |×       |×       |×
orderBy |-       |×       |×       |× 
limit   |+       |+       |×       |+

### Demo
#### more demos,see /tests/simple_DML/TableApiTest.php
        /**在php进程中获得一个操作实例*/
		$dao = Zpdo_Simple::instance(); // 默认master,请尽可能不要走master.
		// $param: $deployedDbName, see: /appbeta/config/zpdo.php
		$dao = Zpdo_Simple::instance('event');

#### $dao = Zpdo_Simple::instance('slave');


### Tips
- 使用 $dao->getSql()调试自己生成的单表Sql语句
- 使用 try{ … }catch(Exception $e){ var_dump($e) }, 调试自己的调用方式

###### different refs in the same process.       
        $master = Zpdo_Simple::instance(); // master:读写
        $slave = Zpdo_Simple::instance('slave'); // slaves:读
        
        
        // 不好意思,Twitters表历史遗留,特殊处理
        $masterTwitter = Zpdo_Simple::instance(Zpdo_Simple::$defaultTwittersDB); // table Twitters 读写
        $slaveTwitter = Zpdo_Simple::instance(Zpdo_Simple::$twittersSlaveDB); // table Twitters 读
        // 不好意思,Twitters表历史遗留,特殊处理

###### select one record
        // SELECT count(requestId) as totalItems FROM mogujie.TradePayRequest WHERE payType = 2 and toUserId = 50958371 and status > 0
        $where = array(
            'payType =' => 2, // mysql data type: Numeric Type , do not use function zstr()
            'AND',
            'toUserId =' => 50958371, // mysql data type: Numeric Type , do not use function zstr()
            'AND',
            'status >' => 0 // mysql data type: Numeric Type , do not use function zstr()
        );
        $dao->select(Zpdo_Simple::count('requestId', 'totalItems'))
            ->from('TradePayRequest')
            ->where($where)
            ->limit(1);
        // var_dump($dao->getSql());
        // SELECT COUNT(`requestId`) AS `totalItems` FROM `TradePayRequest` WHERE `payType` = 2 AND `toUserId` = 50958371 AND `status` > 0 LIMIT 1
        $res = $dao->fetchRow();
        
        // DEMO: * and useIndex 
        $ids = array(36801, 36804);
        $where = array(
            Zpdo_Simple::in('buyerUserId', 'NUMBER', $ids),
            'AND',
            'sellerUserId =' => 62539573 // mysql data type: Numeric Type , do not use function zstr()
        );
        $dao->select('*')
            ->useIndex('idx_itemInfoId')
            ->from('TradeOrder')
            ->where($where)
            ->orderBy('orderId', 'DESC')
            ->limit(0, 100);
        // var_dump($dao->getSql());
		// SELECT * FROM `TradeOrder` USE INDEX(`idx_itemInfoId`) WHERE `buyerUserId` IN (36801,36804) AND `sellerUserId` = 62539573 ORDER BY `orderId` DESC  LIMIT 0,100
        $res = $dao->fetchAll();

##### [重要]mysql index type: string
        $expressCode = '013130764484';
        // 或是 $expressCode = 013130764484;
        $where = array(
            'isDeleted =' => 0, // mysql data type: Numeric Type , do not use function zstr()
            'AND',
            'expressCode =' => zstr($expressCode) // mysql expressCode data type: String type(not Numeric) 
            // 013099719142
        );
        $dao->select('id')->from('TradeItemCheck')->where($where)->limit(1);
        echo $dao->getSql();
        // SELECT `id` FROM `TradeItemCheck` WHERE `isDeleted` = 0 AND `expressCode` = '013130764484' LIMIT 1
        $repeat = $dao->fetchRow();

###### select using like
	    $where = array(
            'userId >=' => 1000, // mysql data type: Numeric Type , do not use function zstr()
            'OR',
            'uname like' => 'sss%'  // 不能用做左模糊!!!不然给你个异常
        );
        $dao->select('userId', 'uname')
            ->from('Users')
            ->where($where)
            ->limit(1);
        $res = $dao->fetchRow();        
        
###### select some records
		// SELECT orderId,buyerUserId,price,status FROM mogujie.TradeOrder WHERE itemInfoId = 43590633 and created > 1374501001 and status >= 2  and level=0 order by orderId DESC limit 100
        $where = array(
            'itemInfoId = ' => 43590633, // mysql data type: Numeric Type , do not use function zstr()
            'AND',
            'created > ' => 1374501001, // mysql data type: Numeric Type , do not use function zstr()
            'AND',
            'status >=' => 2, // mysql data type: Numeric Type , do not use function zstr()
            'AND',
            'level =' => 0 // mysql data type: Numeric Type , do not use function zstr()
        );
        $dao->select('orderId', 'buyerUserId', 'price', 'status')
            ->from('TradeOrder')
            ->where($where)
            ->orderBy('orderId', 'DESC')
            ->limit(100);
        // var_dump($dao->getSql());
		// SELECT `orderId`,`buyerUserId`,`price`,`status` FROM `TradeOrder` WHERE `itemInfoId` = 43590633 AND `created` > 1374501001 AND `status` >= 2 AND `level` = 0 ORDER BY `orderId` DESC  LIMIT 100
        $dao->fetchAll();
        
        // DEMO: IN (…) , dataType: 'NUMBER' , 'STRING'
        // SELECT * FROM mogujie.TradeOrder WHERE  buyerUserId in (36801,36804) and sellerUserId=62539573 order by orderId desc limit 1,100
        $ids = array(36801, 36804);
        $where = array(
            Zpdo_Simple::in('buyerUserId', 'NUMBER', $ids),
            'AND',
            'sellerUserId =' => 62539573 // mysql data type: Numeric Type , do not use function zstr()
        );
        $dao->select('*')
            ->from('TradeOrder')
            ->where($where)
            ->orderBy('orderId', 'DESC')
            ->limit(1, 100);
        // var_dump($dao->getSql());
		// SELECT * FROM `TradeOrder` WHERE `buyerUserId` IN (36801,36804) AND `sellerUserId` = 62539573 ORDER BY `orderId` DESC  LIMIT 1,100
        $dao->fetchAll();

###### delete
        $testId = 633;
        $where = array(
            'id =' => $testId
        );
        $dao->delete()
            ->from($testTable)
            ->where($where)
            ->limit(1);
        // var_dump($dao->getSql());
        // DELETE FROM `MdlMysqlTypesTest` WHERE `id` = 633 LIMIT 1
        $dao->exec();

###### insert
        $testTable = 'MdlMysqlTypesTest';
        $record = array();
        $record['username'] = 'mdltest';
        $record['phone'] = '18688162000';
        $record['deleted'] = 0;
        $record['itemType'] = 1;
        $record['levels'] = -123;
        $record['salary'] = 2312.92;
        $record['birthday'] = '2000-01-01';
        $record['registerDT'] = '2000-01-01 00:01:00';
        $record['expired'] = '2040-01-01 00:00:00';
        $record['created'] = time();
        $record['updated'] = time() + 10000;

        $dao->insert($record)->into($testTable);
        // var_dump($dao->getSql());
        // INSERT INTO `MdlMysqlTypesTest` (`username`,`phone`,`deleted`,`itemType`,`levels`,`salary`,`birthday`,`registerDT`,`expired`,`created`,`updated`) VALUE ( 'mdltest',18688162000,0,1,-123,2312.92,'2000-01-01','2000-01-01 00:01:00','2040-01-01 00:00:00',1384421745,1384431745)
        $lastInsertId = $dao->exec();

###### batch inserts
        $records = array();
        $record = array();
        $record['username'] = 'mdltest';
        $record['phone'] = '18688162000';
        $record['deleted'] = 0;
        $record['itemType'] = 1;
        $record['levels'] = -123;
        $record['salary'] = 2312.92;
        $record['birthday'] = '2000-01-01';
        $record['registerDT'] = '2000-01-01 00:01:00';
        $record['expired'] = '2040-01-01 00:00:00';
        $record['created'] = time();
        $record['updated'] = time() + 10000;
        $records[] = $record;

        $record = array();
        $record['username'] = 'mdltest';
        $record['phone'] = '18688162001';
        $record['deleted'] = 0;
        $record['itemType'] = 2;
        $record['levels'] = 1;
        $record['salary'] = 23;
        $record['birthday'] = '2000-01-01';
        $record['registerDT'] = '2000-01-01 00:01:00';
        $record['expired'] = '2040-01-01 00:00:00';
        $record['created'] = time();
        $record['updated'] = time() + 10000;
        $records[] = $record;

        $dao->inserts($records)->into($testTable);
		// var_dump($dao->getSql());
        $res = $dao->exec();


###### update
	    $updated = 1288785141;
        $upd = array(
            'updated =' => $updated
        );
        $table = 'Twitters';
        $where = array(
        	'twitterId =' => 250000 // mysql data type: Numeric Type , do not use function zstr()
        );
        $dao->update($upd);
        $dao->from($table);
        $dao->where($where);
        $dao->limit(1);
        // var_dump($dao->getSql());
        // UPDATE `Twitters` SET `updated` = 1288785141  WHERE `twitterId` = 250000 LIMIT 1
        $rowCount = $dao->exec();

###### as
        $where = array(
            'userId >=' => 1000, // mysql data type: Numeric Type , do not use function zstr()
        );
        $dao->select(Zpdo_Simple::sqlAs('userId', 'userId'), Zpdo_Simple::sqlAs('uname', 'name'))
            ->from('Users')
            ->where($where)
            ->limit(1);
		// SELECT `userId` AS `userId`, `uname` AS `name` FROM `Users` WHERE `userId` >= 1000 LIMIT 1
        $res = $dao->fetchRow();
        	

#### methods declare <br/>

select : 动态参数列表  <br/>
update : 要更新的内容, array key-value <br />
insert : 要更新的内容, array key-value <br />
delete : (无) <br />


    /**
     * 获得实例,单例模式
     * @param null $table
     * @param null $dbName
     * @return SimpleTable
     */
    public static function instance($table = null, $dbName = null);
<br />

    /**
     * 插入单条数据
     * @param array $col2Val 要更新的内容
     * @param string $PRIORITY mysql语法中的priority,程序用常量表示
     * @param null|boolean $ignore mysql语法中的ignore
     * @return $this
     * @throws RuntimeException
     */
    public function insert(array $col2Val, $PRIORITY = self::INSERT_DEFAULT_PRIORITY, $ignore = null);
<br />

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
<br />

    /**
     * @param array $col2Val
     * @param string $PRIORITY mysql语法中的priority,程序用常量表示
     * @return $this
     */
    public function replace(array $col2Val, $PRIORITY = self::REPLACE_DEFAULT_PRIORITY);
<br />


    /**
     * 要查询的结果列(可以是函数类型的表达式)
     * dynamic args.
     * per arg in dynamic args : columnName or Zpdo_Zfunc
     */
    public function select();
<br />

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
<br />

    /**
     * MDL的delete操作,表示要开始删除数据,不要任何参数
     * @return $this
     */
    public function delete();
<br />

    /**
     * 要操作的表名
     * @param $table : string
     * @param null $database
     * @return $this
     * @throws RuntimeException
     */
    public function from($table, $database = null);
<br />

    /**
     * 要操作的表名
     * @param $table
     * @param null $database
     * @return $this
     * @throws RuntimeException
     */
    public function into($table, $database = null);
<br />


    /**
     * @param array $args
     * 不能为空.奇数位是key-value或是静态方法生成的一个对象,偶数是逻辑关键字:'AND' 'OR'.
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
     *      'title LIKE' => 'sda%'
     *  ))
     * </pre>
     * @return $this
     * @throws RuntimeException
     */
    public function where(array $args);
<br />

    /**
     * dynamic args
     * 默认可以为空.
     * 必须为偶数个参数.奇数位是列名,偶数为排序值desc/asc/DESC/ASC.
     * 推荐: DESC/ASC
     * <pre>
     *  e.g.
     *  'id' , 'DESC' , 'name' , 'ASC'
     * </pre>
     *
     * @return $this
     * @throws RuntimeException
     */
    public function orderBy();
<br />

    /**
     * mysql syntax
     * @param $offsetOrRows int
     * @param $rows int
     *   0 is invalid
     * @throws RuntimeException
     * @return $this
     */
    public function limit($offsetOrRows, $rows = 0)
<br />

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
    public function exec();
<br />

    /**
     * 执行后清空内部变量,及时释放内存占用.予便下个sql使用.
     * @return $record : array
     */
    public function fetchRow();
<br />

    /**
     * 执行后清空内部变量,予便下个sql使用.
     * @return  $records : array
     *      one record : array
     */
    public function fetchAll();
<br />

    /**
     * MDL insert 后返回的LastInsertedId
     * @return null|int
     */
    public function lastInsertId();
<br />

    /**
     * @param $column1
     * @param null | $column2
     * @return $this
     * @throws RuntimeException
     */
    public function groupBy($column1, $column2 = null);
<br />

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


######  getSql 可以用于查看生成的sql
    /**
     * 获得组装后的sql
     * @return string
     */
    public function getSql();
<br/>

######  sqlAs
    /**
     * mysql as 别名使用.
     * note: mehtod name , as是php关键字
     * @param $columnOrFunc
     * @param $alias
     * @return Zpdo_As
     */
    public static function sqlAs($columnOrFunc, $alias);
<br/>

######  sqlIf
    /**
     * 这个方法很复杂,尽量少用, 建表的时候多多注意初始值/默认值去避开使用SQL IF.
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
    public static function sqlIf($expr, $val1, $val2, $comparisonOp = null, $comparisonVal = null);
		// SQL IF DEMO:
	    $expr = array('payTime', '>=', array(
            'shipTime', Zpdo_Simple::ARGUMENT_TYPE_COLUMN
        ));
        $ifval1 = array(
            'payTime', Zpdo_Simple::ARGUMENT_TYPE_COLUMN
        );
        $ifval2 = array(
            'shipTime', Zpdo_Simple::ARGUMENT_TYPE_COLUMN
        );
        $sqlif = Zpdo_Simple::sqlIf($expr, $ifval1, $ifval2, '>=', 100);
        $where = array(
            'orderId =' => $res['orderId'],
            'AND',
            $sqlif
        );
        $dao->select()
            ->from($tbl)
            ->where($where)
            ->limit(1);
<br/>

######  subWhere
    /**
     * 和 function where(array $args); 一样的用法
     */
    public static function subWhere(array $args);

######  others  
    public function useIndex();
    
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
    public static function sqlIf($expr, $val1, $val2, $comparisonOp = null, $comparisonVal = null);
    /**
     * @param $column
     * @param $type
     * @param array $qData
     * @throws
     * @return Zpdo_Zfunc
     */
    public static function notin($column, $type, array $qData)
    public function beginTransaction();
    public function commit();
    public function rollback();
    public function inTransaction();
