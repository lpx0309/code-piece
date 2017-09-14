<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/1/15
 * Time: 18:22
 */
/*if(!empty($admin_user)) {
    echo 'ID：' . $admin_user['id'] . '&nbsp;';
    echo '用户名：' . $admin_user['user_name'] . '&nbsp;';
    echo '中文名：' . $admin_user['name_zh'] . '&nbsp;';
    echo '所在组：' . $admin_user['group_id'] . '&nbsp;';
    echo '坐席号：' . $admin_user['join_number'] . '&nbsp;';
}*/
?>
<form class="log_control">
    <input type="text" id="remark_call_admin" placeholder="坐席帐号或中文名" value="<?php echo $remark_call_admin; ?>">
    <button onclick="remark_call_search();return false">搜索</button>
</form>
<table border="0" cellpadding="7" width="100%">
    <tr>
        <th>时间</th>
        <th>ID</th>
        <th>用户名</th>
        <th>中文名</th>
        <th>所在组</th>
        <th>坐席号</th>
        <th>失败原因</th>
    </tr>
    <?php
    if(!empty($remark_call_list)){
        foreach($remark_call_list as $key=>$remark){
            ?>
            <tr>
                <td><?php echo $remark['time']; ?></td>
                <td><?php echo $remark['admin_id']; ?></td>
                <td><?php echo $remark['user_name']; ?></td>
                <td><?php echo $remark['name_zh']; ?></td>
                <td><?php echo $remark['group_id']; ?></td>
                <td><?php echo $remark['join_number']; ?></td>
                <td><?php echo $remark['reason']; ?></td>
            </tr>
        <?php
        }
    }else{
        ?>
        <tr><td colspan="16" align="center">暂无记录</td></tr>
    <?php
    }
    ?>
</table>
