<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/1/15
 * Time: 18:29
 */
?>
<div>
    进程：<?php echo $pid; ?>&nbsp;开始时间：<?php echo date('Y-m-d H:i:s',$pid); ?>
    <table border="1" cellpadding="7" width="100%">
        <tr>
            <th rowspan="2">过程</th>
            <th rowspan="2">开始时间</th>
            <th style="background-color: green" class="running" colspan="2">取空闲坐席</th>
            <th style="background-color: red" class="running" colspan="2">读取数据库和筛选数据</th>
            <th style="background-color: orange" class="running" colspan="2">取最终学员数据</th>
            <th style="background-color: blue" class="running" colspan="2">执行呼叫</th>
            <th rowspan="2">总用时</th>
        </tr>
        <tr>
            <th>个数</th>
            <th>用时</th>
            <th>个数</th>
            <th>用时</th>
            <th>个数</th>
            <th>用时</th>
            <th>次数</th>
            <th>用时</th>
        </tr>
        <?php
        $key=1;
        if(!empty($do_while_log)){
            foreach($do_while_log as $do_while){
                $do_while=json_decode($do_while,true);
                if($do_while['pid']==$pid) {
                    ?>
                    <tr>
                        <td>第<?php echo $key; ?>次</td>
                        <td><?php echo date('Y-m-d H:i:s',$do_while['id']); ?></td>
                        <td><?php echo $do_while['free_clients_count']; ?></td>
                        <td><?php echo makeZero($do_while['free_clients_time']); ?></td>
                        <td><?php echo $do_while['auto_calling_cc_user_count']; ?></td>
                        <td><?php echo makeZero($do_while['auto_calling_cc_user_time']); ?></td>
                        <td><?php echo $do_while['mobile_count']; ?></td>
                        <td><?php echo makeZero($do_while['mobile_time']); ?></td>
                        <td><?php echo $do_while['called_count']; ?></td>
                        <td><?php echo makeZero($do_while['calling_time']); ?></td>
                        <td><?php if($do_while['run_time']>60){echo '<font color="red">'.makeZero($do_while['run_time']).'</font>';}else{echo makeZero($do_while['run_time']);} ?></td>
                    </tr>
                    <?php
                    $key++;
                }
            }
        }else{
            ?>
            <tr><td colspan="11" align="center">暂无记录</td></tr>
        <?php
        }
        ?>
    </table>
</div>
