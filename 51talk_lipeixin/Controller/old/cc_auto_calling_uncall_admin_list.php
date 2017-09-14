<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/1/15
 * Time: 18:22
 */
if(!empty($admin_user)) {
    echo 'ID：' . $admin_user['id'] . '&nbsp;';
    echo '用户名：' . $admin_user['user_name'] . '&nbsp;';
    echo '中文名：' . $admin_user['name_zh'] . '&nbsp;';
    echo '所在组：' . $admin_user['group_id'] . '&nbsp;';
    echo '坐席号：' . $admin_user['join_number'] . '&nbsp;';
}
?>
<table border="0" cellpadding="7" width="100%">
    <tr>
        <th>时间</th>
        <th>失败原因</th>
    </tr>
    <?php
    if(!empty($uncall_admin_list)){
        foreach($uncall_admin_list as $key=>$admin){
            ?>
            <tr>
                <td><?php echo $admin['time']; ?></td>
                <td><?php echo $admin['uncall_admin']; ?></td>
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
