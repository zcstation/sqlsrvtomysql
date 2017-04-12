<?php
/**
 * User: zcstation
 * Date: 2017/4/11
 * Time: 21:46
 */
set_time_limit(0);
$data = $_POST;

if (empty($data)) {
    exit(false);
}

if ($data['type'] == 'sqlsrv') {
    $dsn = "sqlsrv:Database={$data['database']};Server={$data['host']}";
} else {
    $dsn = "mysql:dbname={$data['database']};host={$data['host']};charset=utf8mb4";
}

try {
    $dbh = new PDO($dsn, $data['user'], $data['pwd']);
    $sql = sql($data['type']);
    $sth = $dbh->prepare($sql['table']);
    $sth->execute();
    $result = $sth->fetchAll();
    $str = '<div class="weui-cells__title">选择需要复制的表（共'.count($result).'个表）</div><div class="weui-cells weui-cells_checkbox" id="'.$data['type'].'_table">';
    foreach ($result as $key => $value) {
        $str .= '<label class="weui-cell weui-check__label" for="'.$data['type'].'_'.$key.'">
                <div class="weui-cell__hd"><input class="weui-check" id="'.$data['type'].'_'.$key.'" type="checkbox" value="'.$value[0].'">
                    <i class="weui-icon-checked"></i>
                </div>
                <div class="weui-cell__bd">'.$value[0].'</div>
            </label>';
    }
    $str .= '</div><br><a href="javascript:choose(\'sqlsrv\', \'all\');">全选</a> <a href="javascript:choose(\'sqlsrv\', \'cancel\');">取消全选</a><br><div class="weui-cell weui-cell_switch">
            <div class="weui-cell__bd">如果数据表已存在是否覆盖？</div>
            <div class="weui-cell__ft">
                <label class="weui-switch-cp" for="switchCP">
                    <input class="weui-switch-cp__input" id="switchCP" type="checkbox">
                    <div class="weui-switch-cp__box"></div>
                </label>
            </div>
        </div>
        <br>
        <div style="width: 80%;margin: auto">
            <button class="weui-btn weui-btn_primary" onclick="startCopy(\'sqlsrv\', \'mysql\', this)">开始复制</button>
        </div>';
    $result = array(
        'errCode' => 'ok',
        'errMsg'  => $str
    );
    exit(json_encode($result));
} catch (PDOException $e) {
    $result = array(
        'errCode' => 'fail',
        'errMsg'  => 'Connection failed: ' . $e->getMessage()
    );
    exit(json_encode($result));
}
/*
 * 根据数据库类型执行不同的sql语句
 * @param   $type   string   数据库类型
 * @return  $result array    返回的sql语句数组集合
 */
function sql($type, $table = null)
{
    if ($type == 'mysql') {
        $result = array(
            'table'     => "SHOW TABLES"
        );

        return $result;
    }

    $result = array(
        'table'     => "Select name from sysObjects Where xtype = 'U' and name <> 'dtproperties'"
    );

    return $result;
}