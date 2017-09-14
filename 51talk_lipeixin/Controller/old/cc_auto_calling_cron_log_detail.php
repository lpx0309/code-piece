<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/1/15
 * Time: 18:22
 */
?>
<table border="0" cellpadding="7" width="100%">
    <tr>
        <th rowspan="2">进程</th>
        <th rowspan="2">开始时间</th>
        <th style="background-color: brown" class="running" rowspan="2">取配置用时</th>
        <th style="background-color: green" class="running" colspan="2">取空闲坐席</th>
        <th style="background-color: red" class="running" colspan="2">读取数据库和筛选数据</th>
        <th style="background-color: orange" class="running" colspan="4">取最终学员数据</th>
        <th style="background-color: blue" class="running" colspan="3">执行呼叫</th>
        <th rowspan="2">结束时间</th>
        <th rowspan="2">总用时</th>
        <?php
        if($run['is_dowhile']==1) {
            ?>
            <th rowspan="2">动态次数</th>
            <th rowspan="2">动态总用时</th>
            <th rowspan="2">动态外呼</th>
        <?php
        }
        ?>
    </tr>
    <tr>
        <th>个数</th>
        <th>用时</th>
        <th>个数</th>
        <th>用时</th>
        <th>自动外呼池</th>
        <th>藏金阁</th>
        <th>总共</th>
        <th>用时</th>
        <th>失败</th>
        <th>成功</th>
        <th>用时</th>
    </tr>
    <?php
    if(!empty($run_log)){
        foreach($run_log as $key=>$run){
            $run=json_decode($run,true);
            ?>
            <tr id="cron_<?php echo $key; ?>">
                <td>
                    <a href="javascript:;" onclick="cron_detail('<?php echo $key; ?>')">
                        <?php echo $run['id']; ?>
                    </a>
                </td>
                <td><?php echo $run['start_time']; ?></td>
                <td><?php echo makeZero($run['config_time']); ?></td>
                <td><?php echo $run['free_clients_count']; ?></td>
                <td><?php echo makeZero($run['free_clients_time']); ?></td>
                <td><?php echo $run['auto_calling_cc_user_count']; ?></td>
                <td><?php echo makeZero($run['auto_calling_cc_user_time']); ?></td>
                <td>
                    <?php
                    if(isset($run['auto_calling_admin']) && !empty($run['auto_calling_admin'])) {
                        $auto_calling_admin_arr=explode(',',$run['auto_calling_admin']);
                        $auto_calling_admin_count = count($auto_calling_admin_arr);
                        $auto_calling_admin_percent = round($auto_calling_admin_count / $run['free_clients_count'] * 100,PERCENT_ROUND);
                        ?>
                        <a href="javascript:;" onclick="auto_calling_admin('<?php echo $key; ?>')">
                            <?php echo $auto_calling_admin_count . '(' . $auto_calling_admin_percent . '%)'; ?>
                        </a>
                        <?php
                    }else{
                        echo '0(0%)';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if(isset($run['dispatch_admin']) && !empty($run['dispatch_admin'])) {
                        $dispatch_admin_arr=explode(',',$run['dispatch_admin']);
                        $dispatch_admin_count = count($dispatch_admin_arr);
                        $dispatch_admin_percent = round($dispatch_admin_count / $run['free_clients_count'] * 100,PERCENT_ROUND);
                        ?>
                            <a href="javascript:;" onclick="dispatch_admin('<?php echo $key; ?>')">
                                <?php echo $dispatch_admin_count . '(' . $dispatch_admin_percent . '%)'; ?>
                            </a>
                        <?php
                    }else{
                        echo '0(0%)';
                    }
                    ?>
                </td>
                <td><?php echo $run['mobile_count']. '(' . round($run['mobile_count'] / $run['free_clients_count'] * 100,PERCENT_ROUND) . '%)'; ?></td>
                <td><?php echo makeZero($run['mobile_time']); ?></td>
                <td>
                    <?php
                    if(isset($run['uncall_admin']) && !empty($run['uncall_admin'])) {
                        $uncall_admin_arr=explode(',',$run['uncall_admin']);
                        $uncall_admin_arr_unique=array();
                        foreach($uncall_admin_arr as $uncall_admin){
                            $uncall_admin_arr_unique[]=explode('-',$uncall_admin)[0];
                        }
                        $uncall_admin_arr_unique = array_unique($uncall_admin_arr_unique);
                        $uncall_admin_count = count($uncall_admin_arr_unique);
                        $uncall_admin_percent = round($uncall_admin_count / $run['free_clients_count'] * 100,PERCENT_ROUND);
                        ?>
                        <a href="javascript:;" onclick="uncall_admin('<?php echo $key; ?>','<?php echo $run['start_time']; ?>','<?php echo $run['uncall_admin']; ?>')">
                            <?php echo $uncall_admin_count . '(' . $uncall_admin_percent . '%)'; ?>
                        </a>
                        <?php
                    }else{
                        echo '0(0%)';
                    }
                    ?>
                </td>
                <td><?php echo $run['called_count']. '(' . round($run['called_count'] / $run['free_clients_count'] * 100,PERCENT_ROUND) . '%)';; ?></td>
                <td><?php echo makeZero($run['calling_time']); ?></td>
                <td><?php echo $run['end_time']; ?></td>
                <td><?php if($run['run_time']>60 && $run['is_dowhile']==0){echo '<font color="red">'.makeZero($run['run_time']).'</font>';}else{echo makeZero($run['run_time']);} ?></td>
                <?php
                if($run['is_dowhile']==1) {
                    ?>
                    <td><?php echo $run['do_while_count']; ?></td>
                    <td><?php if($run['do_while_run_time']>60){echo '<font color="red">'.makeZero($run['do_while_run_time']).'</font>';}else{echo makeZero($run['do_while_run_time']);} ?></td>
                    <td><a href="javascript:;" onclick="pop_div(1200,700,'动态外呼','dowhile','cc_auto_calling_config.php?status=3&op=dowhile&pid=<?php echo $run['id']; ?>')">查看</a></td>
                <?php
                }else{
                    //echo '<td colspan="3" align="center">未开启</td>';
                }
                ?>
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
