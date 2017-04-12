/**
 * Created by zcstation on 2017/4/12.
 */
// 判断是否进行了mysql数据库连接检测
var flag = false;
// 全选与反选
function choose(name, type) {
    var $table = $('#' + name + '_table');
    if (type === 'all') {
        $table.find('input').prop('checked', true);
    } else {
        $table.find('input').prop('checked', false);
    }
}
// 获得表名
function getTables(name, obj) {
    var str = [];
    for (var i = 0; i < obj.length; i++) {
        var $table = $('#' + name + '_' + i);
        if ($table.prop('checked') === true) {
            str.push($table.val());
        }
    }
    return str;
}
// 开始复制
function startCopy(name, to, obj) {
    if (flag === false) {
        weui.alert('请先测试MySQL数据库连接！');
    } else {
        var $host = $('#' + name + '_host'),
            $user = $('#' + name + '_user'),
            $pwd = $('#' + name + '_pwd'),
            $db = $('#' + name + '_database'),
            $toHost = $('#' + to + '_host'),
            $toUser = $('#' + to + '_user'),
            $roPwd = $('#' + to + '_pwd'),
            $toDb = $('#' + to + '_database'),
            $obj = $(obj),
            loading = weui.loading('加载中……', {className: ''}),
            $table = $('#' + name + '_table');

        $.post('lib/copy.php', {
            type: name,
            host: $host.val(),
            user: $user.val(),
            pwd: $pwd.val(),
            database: $db.val(),
            toHost: $toHost.val(),
            toUser: $toUser.val(),
            toPwd: $roPwd.val(),
            toDatabase: $toDb.val(),
            table:getTables(name, $table.find('input')),
            cover:$('#switchCP').prop('checked')
        }, function (res) {
            var oRes = JSON.parse(res);
            loading.hide();
            if (oRes.errCode === 'ok') {
                alert(oRes.errMsg);
                if (name === 'sqlsrv') {
                    $name.append(oRes.errMsg);
                }
            } else {
                weui.alert(oRes.errMsg);
            }
        }, 'text');
    }
}
// 检查数据库链接
function checkConnect(name, obj) {
    var $host = $('#' + name + '_host'),
        $user = $('#' + name + '_user'),
        $pwd = $('#' + name + '_pwd'),
        $db = $('#' + name + '_database'),
        $obj = $(obj),
        $name = $('#' + name);

    var loading = weui.loading('加载中……', {
        className: ''
    });

    $.post('lib/check.php', {
        type: name,
        host: $host.val(),
        user: $user.val(),
        pwd: $pwd.val(),
        database: $db.val()
    }, function (res) {
        var oRes = JSON.parse(res);
        loading.hide();

        if (oRes.errCode === 'ok') {
            weui.toast('连接成功！', 1000);
            $name.find('input').attr('disabled', true);
            $obj.parent('div').remove();
            if (name === 'sqlsrv') {
                $name.append(oRes.errMsg);
            } else {
                flag = true;
            }
        } else {
            weui.alert('连接失败！');
        }
    }, 'text');
}
