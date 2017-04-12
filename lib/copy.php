<?php
/**
 * User: zcstation
 * Date: 2017/4/11
 * Time: 21:46
 */
set_time_limit(0);
$data = $_POST;
file_put_contents('../tables/log.txt', json_encode($data));
if (empty($data) || empty($data['table'])) {
    $result = array(
        'errCode' => 'fail',
        'errMsg'  => '请先选择要复制的表！'
    );

    exit(json_encode($result));
}

if ($data['type'] == 'sqlsrv') {
    $dsn = "sqlsrv:Database={$data['database']};Server={$data['host']}";
    $toDsn = "mysql:dbname={$data['toDatabase']};host={$data['toHost']};charset=utf8mb4";
} else {
    $dsn = "mysql:dbname={$data['database']};host={$data['host']};charset=utf8mb4";
    $toDsn = "sqlsrv:Database={$data['toDatabase']};Server={$data['toHost']}";
}

try {
    $dbh = new PDO($dsn, $data['user'], $data['pwd']);
    $toDbh = new PDO($toDsn, $data['toUser'], $data['toPwd']);
    $result = copyDb($dbh, $toDbh, $data);
    if ($result['errCode'] == 0) {
        $res = array(
            'errCode' => 'ok',
            'errMsg'  => $result['errMsg']
        );
    } else {
        $res = array(
            'errCode' => 'ok',
            'errMsg'  => $result['errMsg']
        );
    }

    exit(json_encode($res));
} catch (PDOException $e) {
    $result = array(
        'errCode' => 'fail',
        'errMsg'  => '数据路连接失败: ' . $e->getMessage()
    );
    exit(json_encode($result));
}
/*
 * 根据数据库类型执行不同的sql语句
 * @param   $type   string   数据库类型
 * @return  $result array    返回的sql语句数组集合
 */
function sql($type, $table)
{
    if ($type == 'mysql') {
        $result = array(
            'column'     => "DESC {$table}"
        );

        return $result;
    }

    $result = array(
        'column'     => "Select a.name tableName,b.name fieldColumn,c.name fieldType,b.length From sysobjects a,syscolumns b,systypes c Where a.id=b.id And a.name='$table' And a.xtype='U' And b.xtype=c.xtype"
    );

    return $result;
}
/*
 * 复制数据库
 * @param   $from   源数据库
 * @param   $to     目标数据库
 * @param   $dbh    源数据库连接对象
 * @param   $toDbh  目标数据库连接对象
 * @param   $tables 数据表
 * @return  array   复制结果和错误信息
 */
function copyDb(PDO $dbh, PDO $toDbh, $data, $from = 'sqlsrv', $to = 'mysql')
{
    $i = 0;

    foreach ($data['table'] as $val) {
        $sql = sql($from, $val);
        $sth = $dbh->prepare($sql['column']);
        $sth->execute();
        $result = $sth->fetchAll();
        file_put_contents('../tables/' . $val . '.log', json_encode($result));
        if ($data['cover'] == 'true') {
            $toDbh->exec("DROP TABLE IF EXISTS {$val}");
        }

        $pk = $dbh->prepare("select b.column_name PK
from information_schema.table_constraints a
inner join information_schema.constraint_column_usage b
on a.constraint_name = b.constraint_name
where a.constraint_type = 'PRIMARY KEY' and a.table_name = '$val'");
        $pk->execute();
        $re = $pk->fetch();

        if (!empty($re)) {
            $pkName = $re['PK'];
        } else {
            $pkName = null;
        }

        $createSql = getSql($val, $result, $pkName);

        $to = $toDbh->prepare($createSql);

        $res = $to->execute();

        if ($res) {
            $i++;
        } else {
            file_put_contents('../tables/error.log', $val);
        }
    }

    if (count($data['table']) == $i) {
        $res = array(
            'errCode' => 0,
            'errMsg'  => "数据库全部复制成功！"
        );
    } else {
        $res = array(
            'errCode' => 1,
            'errMsg'  => "共对" . count($data['table']) . "个表进行复制，成功{$i}个！"
        );
    }

    return $res;
}
/*
 * 拼接sql语句
 * @param   $table  表名
 * @param   $data   数据
 * @param   $pk   主键字段
 * @return  string  返回字符串
 */
function getSql($table, $data, $pk = null)
{
    $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (";

    $array = array();

    foreach ($data as $val) {
        if (!in_array($val['fieldColumn'], $array)) {

            if ($pk == $val['fieldColumn']) {
                file_put_contents('../tables/fieldType.log', $val['fieldColumn'] . $val['fieldType']);
                if ($val['fieldType'] == 'numeric') {
                    $sql .= "`{$val['fieldColumn']}` ".parseField($val['fieldType'], $val['length'])." AUTO_INCREMENT,";
                } else {
                    $sql .= "`{$val['fieldColumn']}` ".parseField($val['fieldType'], $val['length']).",";
                }
            } else {
                $sql .= "`{$val['fieldColumn']}` ".parseField($val['fieldType'], $val['length']).",";
            }
        }
        $array[] = $val['fieldColumn'];
    }

    if ($pk === null) {
        $sql = rtrim($sql, ',') . ') ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_general_ci';
    } else {
        $sql .= ' PRIMARY KEY (`'.$pk.'`)) ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_general_ci';
    }

    file_put_contents('../tables/field.txt', $sql . "\r\n\r\n", FILE_APPEND);

    return $sql;
}
/*
 * 替换sqlsrv字段类型
 * @param   $field  字段类型
 * @param   $length 长度
 * @return  string  替换后的字段类型
 */
function parseField($field, $length)
{
    switch ($field) {
        case 'smallint':
            $name = 'tinyint('.$length.') NOT NULL';
            break;
        case 'bit':
            $name = 'bit('.$length.') NOT NULL';
            break;
        case 'numeric':
            if ($length > 11) {
                $name = 'bigint('.$length.') NOT NULL';
            } else {
                $name = 'int('.$length.') NOT NULL';
            }
            break;
        case 'nvarchar':
            if (empty($length)) {
                $name = 'varchar(2048) DEFAULT NULL';
            } else {
                $name = 'varchar('.$length.') DEFAULT NULL';
            }
            break;
        case 'decimal':
            $name = "decimal($length, 0) NOT NULL";
            break;
        case 'datetime':
            $name = 'datetime DEFAULT \'0000-00-00 00:00:00\'';
            break;
        case 'smalldatetime':
            $name = 'datetime DEFAULT \'0000-00-00 00:00:00\'';
            break;
        case 'sysname':
            $name = 'varchar('.$length.') DEFAULT NULL';
            break;
        case 'real':
            $name = 'float('.$length.') NOT NULL';
            break;
        case 'image':
            $name = 'varchar(255)';
            break;
        default:
            $name = $field . "({$length}) DEFAULT NULL";
    }

    return strtoupper($name);
}
