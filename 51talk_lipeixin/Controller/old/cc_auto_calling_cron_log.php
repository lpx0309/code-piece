<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/1/15
 * Time: 18:22
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link href="../../js/jquery.ui/jquery-ui.css" rel="stylesheet" type="text/css">
    <script src="../../js/jquery.min.js" type="text/javascript" language="javascript"></script>
    <script src='../../js/jquery.ui/jquery-ui.min.js' type="text/javascript" language="javascript"></script>
    <script src='../../js/jquery.cookie.js' type="text/javascript" language="javascript"></script>
    <title>外呼进程监控</title>
    <style type="text/css">
        table{
            border: 1px solid #bbb;
            border-collapse: collapse;
            border-radius: 10px;
        }
        tr:hover{
            background-color:#ccc
        }
        th{
            border: 1px solid #bbb;
            background-color: rgb(219, 230, 237);
            color: #3E4448;
        }
        td{
            border: 1px solid #bbb;
        }
        .log_control{
            text-align: right;
            margin-bottom: 5px;
        }
        .running{
            color: white;
        }
        .cron{
            background-color: #aaa;
        }
    </style>
</head>
<body>

<div>
    <form class="log_control">
        <input type="text" id="stu_id" placeholder="学员ID">
        <button onclick="stu_search();return false;">学员搜索</button>
        <a href="javascript:;" onclick="gold_fail()">放入藏金阁失败</a>&nbsp;
        <a href="javascript:;" onclick="remark_call()">备注外呼失败</a>&nbsp;
        <input type="text" id="user_name" placeholder="坐席帐号或中文名" />
        <button type="submit" onclick="uncall_admin_list();return false;">脚本外呼失败</button>&nbsp;
        <button type="submit" onclick="data_admin_list();return false;">脚本取数据成功</button>&nbsp;
        时间单位：秒&nbsp;
        <a href="javascript:;" onclick="log_clear()">清空运行日志</a>
        自动刷新
        <select id="refresh_setting" onchange="refresh_set()">
            <option value="0">关闭</option>
            <option value="3000">3秒</option>
            <option value="30000" selected>30秒</option>
            <option value="60000">1分钟</option>
        </select>
        <a href="javascript:;" onclick="log_detail()">刷新</a>
    </form>
    <div id="log_detail"></div>
</div>

</body>
</html>

<script type="text/javascript">
    $(function(){
        refresh_set();//初始化表
        $('button').button();
    });

    //自动刷新表
    var refresh;
    function  refresh_set() {
        log_detail();
        var interval = $('#refresh_setting').val();
        if (interval > 0) {
            refresh = setInterval(log_detail, interval);
        }else{
            clearInterval(refresh);
        }
    }

    //载入表
    function log_detail(){
        pop_loading();
        $('#log_detail').empty();
        $('#log_detail').load('cc_auto_calling_config.php?status=3&op=cron&time='+new Date().getTime(),function(){
            var key=$.cookie('cron');
            if(key){
                $('#cron_'+key).attr('class','cron');
            }
            pop_loading_close();
        });
    }

    //清空表
    function log_clear(){
        if(confirm('您确定要清空所有数据？')){
            $.ajax({
                url:'cc_auto_calling_config.php?status=3&op=clear',
                beforeSend:function(){
                    pop_loading();
                },
                success:function(){
                    pop_loading_close();
                    //alert('清空成功！');
                    log_detail();
                }
            })
        }
    }

    //选中进程行
    function cron_selected(key){
        $('.cron').attr('class','');
        $('#cron_'+key).attr('class','cron');
        $.cookie('cron',key);
    }

    //进程详情
    function cron_detail(key){
        cron_selected(key);
        alert('建设中');
    }

    //按进程查询取自动外呼池的坐席
    function auto_calling_admin(key){
        cron_selected(key);
        pop_div(1200,700,'取自动外呼池的坐席','auto_calling_admin','cc_auto_calling_config.php?status=3&op=auto_calling_admin');
    }

    //按进程查询取藏金阁的坐席
    function dispatch_admin(key){
        cron_selected(key);
        pop_div(1200,700,'取藏金阁的坐席','dispatch_admin','cc_auto_calling_config.php?status=3&op=dispatch_admin');
    }

    //按进程查询外呼失败的坐席
    function uncall_admin(key,start_time,uncall_admin){
        cron_selected(key);
        pop_div(1200,700,'外呼失败的坐席 - '+start_time,'uncall_admin','cc_auto_calling_config.php?status=3&op=uncall_admin',{'uncall_admin':uncall_admin});
    }

    //按帐号姓名查询外呼失败的坐席
    function  uncall_admin_list(){
        var user_name=$('#user_name').val();
        if(!user_name){
            alert('请输入坐席帐号或中文名！');
            return false;
        }
        pop_div(1200,700,'脚本外呼失败','uncall_admin_list','cc_auto_calling_config.php?status=3&op=uncall_admin_list',{'user_name':user_name});
    }

    //按帐号姓名查询取数据成功记录
    function data_admin_list(){
        var user_name=$('#user_name').val();
        if(!user_name){
            alert('请输入坐席帐号或中文名！');
            return false;
        }
        pop_div(1200,700,'脚本取数据成功','data_admin_list','cc_auto_calling_config.php?status=3&op=data_admin_list',{'user_name':user_name});
    }

    //放入藏金阁失败
    function gold_fail(){
        pop_div(1200,700,'放入藏金阁失败','gold_fail','cc_auto_calling_config.php?status=3&op=gold_fail');
    }

    //搜索放入藏金阁失败
    function gold_fail_search(){
        var gold_fail_admin=$('#gold_fail_admin').val();
        pop_load('gold_fail','cc_auto_calling_config.php?status=3&op=gold_fail',{'gold_fail_admin':gold_fail_admin});
    }

    //备注外呼失败
    function remark_call(){
        pop_div(1200,700,'备注外呼失败','remark_call','cc_auto_calling_config.php?status=3&op=remark_call');
    }

    //搜索备注外呼失败
    function remark_call_search(){
        var remark_call_admin=$('#remark_call_admin').val();
        pop_load('remark_call','cc_auto_calling_config.php?status=3&op=remark_call',{'remark_call_admin':remark_call_admin});
    }

    //学员搜索
    function stu_search(){
        var stu_id=$('#stu_id').val();
        pop_div(1200,700,'学员 - '+stu_id,'stu_search','cc_auto_calling_config.php?status=3&op=stu_search',{'stu_id':stu_id});
    }

    //学员详情页放入藏金阁按钮
    function removecustom(sid){
        var cid=$('#stu_admin').val();
        if(!cid){
            alert('必填admin_id！');
            return false;
        }
        if(confirm('确定将此数据放入藏金阁？')){
            pop_loading();
            /*$.post('admin_edit_comstorm.php',{'sid':sid,'comstorid':cid,'type':'cc',forgc:'gongchi'},function(msg){
                pop_loading_close();
                if(msg == 1){
                    alert('加入藏金阁成功！');
                    pop_load('stu_search', 'cc_auto_calling_config.php?status=3&op=stu_search', {'stu_id': stu_id});
                }else if(msg != 0 && msg.length > 0){
                    alert(msg+'用户已付费，不能放入藏金阁！');
                }else{
                    alert('加入藏金阁失败！');
                }
            });*/
            $.ajax({
                url:'ajax_gongchi_remove.php',
                type:'POST',
                data:{'user_id':sid,'admin_id':cid,'time':new Date().getTime()},
                dataType:'json',
                success:function(data){
                    if(data == 0) {
                        alert('放入藏金阁成功');
                        window.location.reload();
                    }else{
                        //console.log(data);
                        if(data[3] || data[4]) {
                            var msg=new Array();
                            if (data[4]) {
                                msg.push(data[4].join(',') + '用户已付费');
                            }
                            if (data[3]) {
                                var data3=data[3].join(',');
                                msg.push(data3 + '放入藏金阁失败！<br><font color="red">点击修复数据会自动修复并放入藏金阁</font>');
                                msg=msg.join('，');
                                $('body').append('<div id="pop_repair">'+msg+'</div>');
                                $('#pop_repair').dialog({
                                    width: '300px',
                                    autoOpen: false,
                                    title: '提示',
                                    modal: true,
                                    close: function() {
                                        $(this).remove();
                                    },
                                    buttons: {
                                        '修复数据': function() {
                                            repair_remove(data3,cid);
                                        },
                                        '关闭': function() {
                                            $(this).dialog('close');
                                        }
                                    }
                                });
                                $('#pop_repair').dialog('open');
                            }else{
                                alert(msg[0]);
                            }
                        }else{
                            alert('放入藏金阁失败');
                        }
                    }
                }
            });
        }
    }

    //修复并放入藏金阁
    function repair_remove(sid,cid){
        $.ajax({
            url: 'ajax_repair_remove.php',
            type: 'POST',
            data: {'user_id': sid, 'admin_id': cid, 'time': new Date().getTime()},
            success:function(msg){
                if(msg){
                    alert(msg);
                }else{
                    alert('数据已放入藏金阁！');
                    window.location.reload();
                }
            }
        });
    }

    //强制放入藏金阁
    function into_user_gongchi(stu_id){
        var data='stu_id='+stu_id;
        var stu_admin=$('#stu_admin').val();
        if(stu_admin){
            data+='&stu_admin='+stu_admin;
        }
        if(confirm('您确定要将该学员强制放入藏金阁？')){
            $.ajax({
                url:'cc_auto_calling_config.php?status=3&op=into_user_gongchi',
                type:'POST',
                data:data,
                beforeSend:function(){
                    pop_loading();
                },
                success:function(msg){
                    pop_loading_close();
                    if(msg){
                        alert(msg);
                    }else {
                        pop_load('stu_search', 'cc_auto_calling_config.php?status=3&op=stu_search', {'stu_id': stu_id});
                    }
                }
            });
        }
    }

    //强制放入自动外呼池
    function into_auto_calling_cc_user(stu_id){
        var stu_admin=$('#stu_admin').val();
        if(!stu_admin){
            alert('必填admin_id！');
            return false;
        }
        if(confirm('您确定要将该学员强制放入自动外呼池？')){
            $.ajax({
                url:'cc_auto_calling_config.php?status=3&op=into_auto_calling_cc_user',
                type:'POST',
                data:'stu_id='+stu_id+'&stu_admin='+stu_admin,
                beforeSend:function(){
                    pop_loading();
                },
                success:function(msg){
                    pop_loading_close();
                    if(msg){
                        alert(msg);
                    }else {
                        pop_load('stu_search', 'cc_auto_calling_config.php?status=3&op=stu_search', {'stu_id': stu_id});
                    }
                }
            });
        }
    }

    //公共弹出层
    function pop_div(width,height,title,id,url,data){
        $("body").append('<div id="pop_div_'+id+'"></div>');
        $("#pop_div_"+id).dialog({
            modal: true,
            autoOpen:false,
            resizable:false,
            width:width,
            height:height,
            title:title,
            close:function(){
                $(this).remove();
            }
        });
        $("#pop_div_"+id).dialog("open");
        pop_load(id,url,data);
    }
    //弹出层载入内容
    function pop_load(id,url,data){
        pop_loading();
        $("#pop_div_"+id).empty();
        $("#pop_div_"+id).load(url+"&time="+new Date().getTime(),data,function(response,status,xhr){
            pop_loading_close();
            if(status=='error'){
                $(this).html(response);
            }
        });
    }
    //加载页面
    function pop_loading(){
        var loading='<img src="../../images/admin/loadding2.gif" />';
        $("body").append('<div id="pop_loading"><div style="text-align:center">'+loading+'</div></div>');
        $("#pop_loading").dialog({
            bgiframe: true,
            autoOpen: true,
            resizable: false,
            modal: true,
            height: 160,
            width: 300,
            open: function(){
                $(this).parent().find('.ui-dialog-titlebar').hide();
            },
            close: function(){
                $(this).remove();
            }
        });
    }
    //加载页面关闭
    function pop_loading_close(){
        $('#pop_loading').dialog('close');
    }
</script>