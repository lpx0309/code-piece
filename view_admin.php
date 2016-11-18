<?php
if(!isset($_SESSION['user'])){
	header('location:'.UNITY_INDEX.'/index.php?view=login&site=competition');
}
if($_SESSION['user']['id_user']!=1){
	echo '您没有权限访问';
	exit;
}

require_once(FILE_ROOT.'/classes/class_competition.php');
$Competition=new Competition($_GET);
$competitions_list=$Competition->Manage_list();
$type_zh=$Competition->type_zh;

$title='竞赛列表';
?>

<?php require(FILE_ROOT.'/views/view_header.php'); ?>

<link rel="stylesheet" href="<?php echo LINK_ROOT; ?>/css/admin.css" type="text/css" />
<link rel="stylesheet" href="<?php echo LINK_ROOT; ?>/css/jquery.wysiwyg.css" type="text/css" />
<link rel="stylesheet" href="<?php echo LINK_ROOT; ?>/css/jquery.Jcrop.css" type="text/css" />

<script type="text/javascript" src='<?php echo LINK_ROOT; ?>/js/jquery.form.js'></script>
<script type="text/javascript" src='<?php echo LINK_ROOT; ?>/js/jquery.wysiwyg.js'></script>
<script type="text/javascript" src='<?php echo LINK_ROOT; ?>/js/wysiwyg.image.js'></script>
<script type="text/javascript" src='<?php echo LINK_ROOT; ?>/js/wysiwyg.link.js'></script>
<script type="text/javascript" src='<?php echo LINK_ROOT; ?>/js/wysiwyg.table.js'></script>
<script type="text/javascript" src='<?php echo LINK_ROOT; ?>/js/jquery.Jcrop.js'></script>
<script type="text/javascript" src="<?php echo LINK_ROOT; ?>/js/My97DatePicker/WdatePicker.js"></script>
            
<div class="Main">
  <?php require(FILE_ROOT.'/views/view_admin_left.php'); ?>
  
  <div class="my_right">
    <h2>
	  <a href="<?php echo COMPETITION_INDEX;?>/index.php?view=admin" style="font-size:20px"><?php echo $title ?></a>&nbsp;
      <button class="add" onClick="competition_add()">添加</button>&nbsp;
      <!--<button class="del" onClick="">批量删除</button>-->
      <form id="form_search" style="float:right;">
        <input type="text" id="keyword" value="<?php if(isset($_GET['kw'])){echo $_GET['kw'];} ?>" placeholder="竞赛名称关键字" style="height:35px" />
        <button type="submit" class="keyword_search" onclick="keyword_search();return false" style="font-size:18px">搜索</button>
      </form>
      <div style="clear:both"></div>
    </h2>
   
    <table border="0" width="99%" cellpadding="5" cellspacing="2" class="my_table">
      <thead>
        <tr>
          <?php
		  if(isset($_GET['st'])){
			 $od=explode('_',$_GET['st'])[0]; 
			 $by=explode('_',$_GET['st'])[1]; 
		  }else{
			 $od='id';
			 $by='asc';
		  }
		  $asc='<img src="'.LINK_ROOT.'/images/sort-ascending.png" />';
		  $desc='<img src="'.LINK_ROOT.'/images/sort-descending.png" />';
		  $not='<img src="'.LINK_ROOT.'/images/sort-not-sorted.png" />';
		  ?>

          <!--<th width="20"><input type="checkbox" /></th>-->
          <th width="30"><a href="javascript:;" onclick="list_sort('id','desc')">ID&nbsp;<?php if($od=='id'){if($by=='asc'){echo $asc; }else{echo $desc;}}else{echo $not;} ?></a></th>
          <th width="60">图片</th>
          <th width=""><a href="javascript:;" onclick="list_sort('name','desc')">名称&nbsp;<?php if($od=='name'){if($by=='asc'){echo $asc; }else{echo $desc;}}else{echo $not;} ?></a></th>
          <!--<th width="">说明</th>-->
          <th width="50"><a href="javascript:;" onclick="list_sort('type','desc')">类型&nbsp;<?php if($od=='type'){if($by=='asc'){echo $asc; }else{echo $desc;}}else{echo $not;} ?></a></th>
          <th width="180"><a href="javascript:;" onclick="list_sort('start','asc')">开始&nbsp;<?php if($od=='start'){if($by=='asc'){echo $asc; }else{echo $desc;}}else{echo $not;} ?></a><!--</th>-->
          <!--<th width="20%">--><a href="javascript:;" onclick="list_sort('end','asc')">结束&nbsp;<?php if($od=='end'){if($by=='asc'){echo $asc; }else{echo $desc;}}else{echo $not;} ?></a></th>
          <th width="50"><a href="javascript:;" onclick="list_sort('deadline','desc')">剩余&nbsp;<?php if($od=='deadline'){if($by=='asc'){echo $asc; }else{echo $desc;}}else{echo $not;} ?></a></th>
          <th width="60"><a href="javascript:;" onclick="list_sort('reward','desc')">报酬&nbsp;<?php if($od=='reward'){if($by=='asc'){echo $asc; }else{echo $desc;}}else{echo $not;} ?></a></th>
          <th width="90"><a href="javascript:;" onclick="list_sort('entries','desc')">提交&nbsp;<?php if($od=='entries'){if($by=='asc'){echo $asc; }else{echo $desc;}}else{echo $not;} ?></a></th>
          <th width="80">操作</th>
        </tr>
      </thead>
      <tbody>
	  <?php
	  if(count($competitions_list)>0){
		  foreach($competitions_list as $competition){
		  ?>
          <tr onmousemove="move_color(this)" onmouseout="out_color(this)">
            <!--<td><input type="checkbox" /></td>-->
            <td><?php echo $competition['id_competition']; ?></td>
            <td>
              <a href="<?php echo COMPETITION_INDEX; ?>/index.php?view=competition&id_competition=<?php echo $competition['id_competition']; ?>" target="_blank">
                <img src="<?php echo $competition['image']; ?>" width="50" height="50" />
              </a>
            </td>
              
            <td>
              <a href="<?php echo COMPETITION_INDEX; ?>/index.php?view=competition&id_competition=<?php echo $competition['id_competition']; ?>" target="_blank">
				<?php echo $competition['name_competition']; ?>
              </a>
            </td>
            
            <!--<td>
              <a href="<?php echo COMPETITION_INDEX; ?>/index.php?view=competition&id_competition=<?php echo $competition['id_competition']; ?>" target="_blank">
              <?php echo substr($competition['introduce'],0,30); ?>
              </a>
            </td>-->
            
            <td><?php echo $competition['type']; ?></td>
            
            <td>开始：<?php echo $competition['start_time']; ?><br /><!--</td>-->
            <!--<td>-->结束：<?php echo $competition['end_time']; ?></td>
            
            <td><?php echo $competition['deadline']; ?></td>
            
            <td><?php echo $competition['reward']; ?></td>
            
            <td>
            <?php echo $competition['entries']; ?>&nbsp;<a href="javascript:;" onclick="submission(<?php echo $competition['id_competition']; ?>)">下载/评分</a>       
            </td>
            
            <td>
              <button class="modify" onclick="competition_modify(<?php echo $competition['id_competition']; ?>)">修改</button><br>
              <button class="del" onclick="competition_del(<?php echo $competition['id_competition']; ?>)">删除</button>
            </td>
          </tr>
		  <?php	
		  }
	  }else{
      ?>
          <tr><td align="center" colspan="9" class="my_table_td">暂无任何竞赛</td></tr>
      <?php
	  }
	  ?>
      </tbody>
    </table>
  
  </div>
</div>


<!--添加修改竞赛-->
<div id="competition">
  
  <form id="form_competition">
    <input type="hidden" name="id_competition" id="id_competition" value="0" />
    <div class="tabs">
      <ul>
        <li><a href="#tab1">*总览</a></li>
        <li><a href="#tab2">数据</a></li>
        <li><a href="#tab3">*描述</a></li>
        <li><a href="#tab4">评价</a></li>
        <li><a href="#tab5">*规则</a></li>
        <li><a href="#tab6">奖励</a></li>
      </ul>
      
      <div id="tab1"><!--图片，名称，说明，类型，是否可参与，是否学习，结束时间-->
        <div>*名称&nbsp;
          <input type="text" name="name_competition" id="name_competition" style="width:92%" />
        </div>
        
        <div style="float:left; border:0px solid #ccc; margin-top:5px; width:350px; height:350px; line-height:30px">
          <ul>
            <li>*说明&nbsp;
              <textarea name="introduce" id="introduce" style="width:83%; height:200px"></textarea>
            </li>
            <li>*类型&nbsp;
              <select name="type" id="type">
              	<option value="0">请选择</option>
                <?php
                foreach($type_zh as $key=>$type){
                ?>
                    <option value="<?php echo $key; ?>"><?php echo $type; ?></option>
                <?php
                }
                ?>
              </select>
            </li>
            
            <li>是否可参与&nbsp;
              <input name="enterable" id="enterable"  type="checkbox" checked="checked" />&nbsp;（不勾选为保护，不可参与）
            </li>
            
            <li>in-class&nbsp;
              <input name="in_class" id="in_class" type="checkbox" />&nbsp;
              是否积分&nbsp;
              <input name="is_pts" id="is_pts" type="checkbox" checked="checked" />&nbsp;
              是否评级&nbsp;
              <input name="is_rank" id="is_rank" type="checkbox" checked="checked" />&nbsp;
            </li>
            
            <li>*开始时间&nbsp;
              <input class="Wdate" type="text" onClick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm:ss'})" value="" name="start_time" id="start_time" size="25">
            </li>
            
            <li>*结束时间&nbsp;
              <input class="Wdate" type="text" onClick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm:ss'})" value="" name="end_time" id="end_time" size="25">
            </li>
          </ul>
        </div>
        
        <div style="width:350px; height:350px; border:0px solid #ccc; margin-top:5px; float:right">
          <div style="float:left">图片</div>
          <div style="width:290px; height:290px; border:1px solid #ccc; float:right; margin-right:20px">
          
            <input type="hidden" id="x" name="x" />
            <input type="hidden" id="y" name="y" />
            <input type="hidden" id="w" name="w" />
            <input type="hidden" id="h" name="h" />
            <input type="hidden" id="name_image" name="name_image" />
            <img src="" id="competition_image" />
            
          </div>
          <div style="clear:both"></div>
          <div style="margin-top:5px; margin-left:40px"><input type="file" id="file_image" /></div>
        </div>
        
        <div style="clear:both"></div>
      </div>
      
      <div id="tab2"><!--文件，数据说明-->
        <div>
          数据说明
          <textarea name="data" id="data"></textarea>
        </div>
        <div style="float:left; width:70%; margin-top:5px">
          <input type="hidden" name="tmp_id" id="tmp_id" value="0" />
          <input type="hidden" name="file_num" id="file_num" value="0" />
          <table border="1" width="100%">
            <thead>
              <tr>
                <th width="60%">名称</th>
                <th width="30%">大小</th>
                <th width="10%">操作</th>
              </tr>
            </thead>
            <tbody id="file_list">
              <tr>
                <td colspan="3">暂无任何文件</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div style="float:right; width:28%; margin-top:5px">
          <input type="file" id="file_data" />
        </div>
      </div>
      
      <div id="tab3">*描述
        <textarea name="description" id="description"></textarea>
      </div>
      <div id="tab4">评价
        <textarea name="evaluation" id="evaluation"></textarea>
      </div>
      <div id="tab5">*规则
        <textarea name="rule" id="rule"></textarea>
      </div>
      <div id="tab6">
        <div style="line-height:25px">
          奖励
          <ul>
            <li>1等奖&nbsp;￥&nbsp;<input type="text" name="prize_1" id="prize_1" /></li>
            <li>2等奖&nbsp;￥&nbsp;<input type="text" name="prize_2" id="prize_2" /></li>
            <li>3等奖&nbsp;￥&nbsp;<input type="text" name="prize_3" id="prize_3" /></li>
          </ul>
        </div>
        <div style="margin-top:10px">
          奖励说明
          <textarea name="prize" id="prize"></textarea>
        </div>
      </div>
    </div>
  </form>
</div>

<!--提交列表弹出层-->
<div id="submission">
  <form id="form_submission">
    <table border="0" width="99%" cellpadding="5" cellspacing="2" class="my_table">
      <thead>
        <tr>
          <!--<th width="20"><input type="checkbox" /></th>-->
          <th width="50%">文件</th>
          <!--<th width="30%">描述</th>-->
          <th width="15%">用户</th>
          <th width="10%">评分</th>
          <th width="25%">提交时间</th>
        </tr>
      </thead>
      <tbody id="submission_list">
      </tbody>
    </table>
  </form>
</div>


<script type="text/javascript">
$(document).ready(function(){
	$('#competition').dialog({
		modal: true,				 
		autoOpen:false,
		resizable:false,
    	modal: true,
		width:800,
		height:600,
		/*title:'添加竞赛',*/
		close:function(){
		},
		buttons:{
			'确定':function(){
				competition();
			},
			'取消':function(){
				$(this).dialog('close');
			}
		}
	});
	$('#submission').dialog({
		modal: true,				 
		autoOpen:false,
		resizable:false,
    	modal: true,
		width:800,
		height:600,
		title:'提交列表（红色为用户标记作为优先评分的）',
		close:function(){
		},
		buttons:{
			'确定':function(){
				score();
			},
			'取消':function(){
				$(this).dialog('close');
			}
		}
	});
	
	var jcrop_api;
	$('#competition_image').Jcrop({
		onSelect: updateCoords,
		aspectRatio:1,
		boxWidth:290,
		boxHeight:290,
	},function(){
		jcrop_api = this;
	});
	
	function updateCoords(c){
	  $('#x').val(c.x);
	  $('#y').val(c.y);
	  $('#w').val(c.w);
	  $('#h').val(c.h);
	}
	
	$("#file_image").uploadify({	
		swf:$('#competition_index').val()+"/js/uploadify.swf",
		uploader:$('#competition_index').val()+"/index.php?ajax=user&op=user_image&time="+ new Date().getTime(),
		auto:true,
		width:200,
		height:30,
		queueSizeLimit : 1,
		buttonText:"选择文件",
		onUploadStart:function(){
			pop_loading();
		},
		onUploadSuccess:function(file, data, response){
			/*if(data){
				alert(data);
				pop_loading_close();
				return false;
			}*/
			//$('#link_root').val()+'/images/tmp/'+file.name
			$('#name_image').val(file.name);
			jcrop_api.setImage(data,function(){
				this.setSelect([0,0,150,150]);	
				pop_loading_close();
			});
		}
	});
	$("#file_data").uploadify({	
		swf:$('#competition_index').val()+"/js/uploadify.swf",
		uploader:$('#competition_index').val()+"/index.php?ajax=competition&op=manage_data&time="+ new Date().getTime(),
		auto:true,
		width:200,
		height:30,
		queueSizeLimit : 1,
		buttonText:"选择文件",
		formData:{'id_competition':0,'tmp_id':0},
		onUploadStart:function(){
			$("#file_data").uploadify('settings','formData',{'id_competition':$('#id_competition').val(),'tmp_id':$('#tmp_id').val()});
			pop_loading();
		},
		onUploadSuccess:function(file, data, response){
			var file_num=parseInt($('#file_num').val());
			if(file_num==0){
				$('#file_list').html('');
			}
			pop_loading_close();
			data=$.parseJSON(data);
			$('#tmp_id').val(data['tmp_id']);
			var file_list='';
			file_list+='<tr id="file_'+$('#file_list').children().length+'">';
			file_list+='<td><a href="'+data['url']+'" target="_blank">'+file.name+'</a></td>';
			file_list+='<td>'+file.size+'</td>';
			file_list+='<td align="center"><a href="javascript:;" onclick=file_del("'+data['url']+'","file_'+$('#file_list').children().length+'")>删除</a></td>';
			file_list+='</tr>';
			$('#file_list').append(file_list);
			file_num++;
			$('#file_num').val(file_num);
		}
	});	
	 
	$('#data').wysiwyg();
	$('#description').wysiwyg();
	$('#evaluation').wysiwyg();
	$('#rule').wysiwyg();
	$('#prize').wysiwyg();
	
	$('.wysiwyg').width('100%');
	$('.wysiwyg').height('370px');
	$('#data').parent().height('200px');
	$('#prize').parent().height('250px');
	
	$('#data-wysiwyg-iframe').width('100%');
	$('#data-wysiwyg-iframe').css('min-height','170px');
	$('#description-wysiwyg-iframe').width('100%');
	$('#description-wysiwyg-iframe').css('min-height','340px');
	$('#evaluation-wysiwyg-iframe').width('100%');
	$('#evaluation-wysiwyg-iframe').css('min-height','340px');
	$('#rule-wysiwyg-iframe').width('100%');
	$('#rule-wysiwyg-iframe').css('min-height','340px');
	$('#prize-wysiwyg-iframe').width('100%');
	$('#prize-wysiwyg-iframe').css('min-height','220px');
});

//添加竞赛
function competition_add(){
	$("#form_competition").resetForm();
	$('#id_competition').val(0);
	$('#tmp_id').val(0);
	$('#file_num').val(0);
	
	$('#file_list').html('<tr><td colspan="3">暂无任何文件</td></tr>');
	$('#competition').dialog('option','title','添加竞赛（*为必填）');
	$('#competition').dialog('open');
}

//修改竞赛
function competition_modify(id_competition){
	$("#form_competition").resetForm();
	$('#id_competition').val(id_competition);
	$('#tmp_id').val(0);
	$('#file_num').val(0);
	
	$.ajax({
		url:$('#competition_index').val()+"/index.php?ajax=competition&op=manage_competition&time="+ new Date().getTime(),
		data:'id_competition='+id_competition,
		dataType:'json',
		type:'POST',
		async:true,
		beforeSend: function(){
			pop_loading();
		},
		success: function(competition){
			console.log(competition);
			//总览
			$('#name_competition').val(competition['name_competition']);
			$('#introduce').val(competition['introduce']);
			$('#type').val(competition['type']);
			if(competition['enterable']==1){
				$('#enterable').attr('checked',true);
			}else{
				$('#enterable').attr('checked',false);
			}
			if(competition['in_class']==1){
				$('#in_class').attr('checked',true);
			}else{
				$('#in_class').attr('checked',false);
			}
			if(competition['is_pts']==1){
				$('#is_pts').attr('checked',true);
			}else{
				$('#is_pts').attr('checked',false);
			}
			if(competition['is_rank']==1){
				$('#is_rank').attr('checked',true);
			}else{
				$('#is_rank').attr('checked',false);
			}
			$('#start_time').val(competition['start_time']);
			$('#end_time').val(competition['end_time']);
			
			//数据
			$('#data').wysiwyg('setContent',competition['data']);
			var file_list='';
			if(competition['file'].length>0){
				for(i in competition['file']){
					file_list+='<tr id="file_'+i+'">';
					file_list+='<td><a href="'+competition['file'][i]['url']+'" target="_blank">'+competition['file'][i]['name']+'</a></td>';
					file_list+='<td>'+competition['file'][i]['size']+'</td>';
					file_list+='<td align="center"><a href="javascript:;" onclick=file_del("'+competition['file'][i]['url']+'","file_'+i+'")>删除</a></td>';
					file_list+='</tr>';
				}
			}else{
				file_list+='<tr><td colspan="3">暂无任何文件</td></tr>';
			}
			$('#file_list').html(file_list);
			$('#file_num').val(competition['file'].length);
			
			//描述，评价，规则
			$('#description').wysiwyg('setContent',competition['description']);
			$('#evaluation').wysiwyg('setContent',competition['evaluation']);
			$('#rule').wysiwyg('setContent',competition['rule']);
			
			//奖励
			$('#prize_1').val(competition['prize_1']);
			$('#prize_2').val(competition['prize_2']);
			$('#prize_3').val(competition['prize_3']);
			$('#prize').wysiwyg('setContent',competition['prize']);
			
			pop_loading_close();
		}
	});
	$('#competition').dialog('option','title','修改竞赛（*为必填）');
	$('#competition').dialog('open');
}


//添加修改竞赛
function competition(){
	if(!$("#name_competition").val()){
		alert("请填写名称！");
		return false;
	}else{
	}
	if(!$("#introduce").val()){
		alert("请填写说明！");
		return false;
	}else{
	}
	if($("#type").val()==0){
		alert("请选择类型！");
		return false;
	}else{
	}
	if(!$("#start_time").val()){
		alert("请填写结束时间！");
		return false;
	}else{
	}
	if(!$("#end_time").val()){
		alert("请填写结束时间！");
		return false;
	}else{
	}
	if((new Date($("#end_time").val()).getTime()-new Date($("#start_time").val()).getTime())<=(1000*3600*24*7)){
		alert("持续时间必须大于一星期！");
		return false;
	}else{
	}
	if(!$("#description").val()){
		alert("请填写描述！");
		return false;
	}else{
	}
	if(!$("#rule").val()){
		alert("请填写规则！");
		return false;
	}else{
	}
	$.ajax({
		url:$('#competition_index').val()+"/index.php?ajax=competition&op=manage_set&time="+ new Date().getTime(),
		data:$('#form_competition').serialize(),
		type:'POST',
		async:true,
		beforeSend: function(){
			pop_loading();
		},
		success: function(text){
			pop_loading_close();
			if(text){
				alert(text);
				console.log(text);
			}else{
				window.location.reload();
			}
		}
	});
}

//删除竞赛
function competition_del(id_competition){
	if(confirm('您确定要删除这个竞赛吗？')){
		$.ajax({
			url:$('#competition_index').val()+"/index.php?ajax=competition&op=manage_del&time="+ new Date().getTime(),
			data:'id_competition='+id_competition,
			type:'POST',
			async:true,
			beforeSend: function(){
				pop_loading();
			},
			success: function(text){
				pop_loading_close();
				if(text){
					alert(text);
				}else{
					window.location.reload();
				}
			}
		});
	}
}

//删除文件
function file_del(url,tr){
	if(confirm('您确定要删除这个文件吗？')){
		$.ajax({
			url:$('#competition_index').val()+"/index.php?ajax=competition&op=manage_data_del&time="+ new Date().getTime(),
			data:'url='+url,
			type:'POST',
			async:true,
			beforeSend: function(){
				pop_loading();
			},
			success: function(text){
				pop_loading_close();
				if(text){
					alert(text);
					console.log(text);
				}else{
					$('#'+tr).remove();
					var file_num=parseInt($('#file_num').val());
					file_num--;
					$('#file_num').val(file_num);
					if(file_num==0){
						$('#file_list').html('<tr><td colspan="3">暂无任何文件</td></tr>');
					}
				}
			}
		});
	}
}

//提交列表
function submission(id_competition){
	$.ajax({
		url:$('#competition_index').val()+"/index.php?ajax=competition&op=manage_submission&time="+ new Date().getTime(),
		data:'id_competition='+id_competition,
		dataType:'json',
		type:'POST',
		async:true,
		beforeSend: function(){
			pop_loading();
		},
		success: function(submission){
			console.log(submission);
			var submission_list='';
			if(submission.length>0){
				for(i in submission){
					submission_list+='<tr onmousemove=move_color(this) onmouseout=out_color(this)>';
					if(submission[i]['checked']==1){
						var color='style="color:red"';
					}else{
						var color='';
					}
					submission_list+='<td><a href="'+submission[i]['url']+'" target="_blank" '+color+'>'+submission[i]['file']+'</a>';
					if(submission[i]['description']){
						submission_list+='&nbsp;（'+submission[i]['description']+'）';
					}
					submission_list+='</td>';
					//submission_list+='<td>'+submission[i]['description']+'</td>';
					submission_list+='<td><a href="'+$('#competition_index').val()+'/index.php?view=my&id_user='+submission[i]['id_user']+'" target="_blank">'+submission[i]['display_name']+'</a></td>';
					submission_list+='<td><input type="text" name="'+submission[i]['id_submission']+'" id="'+submission[i]['id_submission']+'" class="score" value="'+submission[i]['score']+'" size="5" maxlength="5" /></td>';
					submission_list+='<td>'+submission[i]['submission_time']+'</td>';
					submission_list+='</tr>';
				}
			}else{
				submission_list+='<tr><td colspan=5>暂无任何提交</td>';
			}
			$('#submission_list').html(submission_list);
			pop_loading_close();
		}
	});
	
	$('#submission').dialog('open');
}

//打分
function score(){
	var not_num=0;;
	$('.score').each(function(){
		if(isNaN($(this).val())){
			not_num++;
		}
	});
	if(not_num>0){
		alert('得分请填写数字！');
		return false;
	}
	
	$.ajax({
		url:$('#competition_index').val()+"/index.php?ajax=competition&op=manage_score&time="+ new Date().getTime(),
		data:$('#form_submission').serialize(),
		type:'POST',
		async:true,
		beforeSend: function(){
			pop_loading();
		},
		success: function(text){
			pop_loading_close();
			if(text){
				alert(text);
				console.log(text);
			}else{
				window.location.reload();
			}
		}
	});
}
</script>

<?php require(FILE_ROOT.'/views/view_footer.php'); ?>
