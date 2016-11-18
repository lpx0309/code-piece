<script type="text/javascript">
    ///sdfsdf
    /*//数据重新加载绑定
    var dataBindSource = function (){
        var url = "<?php echo $this->createurl("Segment");?>";
		var recordCount = $('#recordCount').val();
        $.ajax({url: url,
            type: 'get',
            data:$('#students-index').serialize()+'&recordCount='+recordCount,
            dataType: 'html',
			beforeSend: function(){
				openOnload();
			},
            success: function(result){
				closeOnload();
                $("#studentsinfo").html(result);
            }
        });
    };

    //分页添加ajax事件
    $(document).ready(function(){
        $('.right').css('height','auto');
        //列表分页
        $('.yiiPager a').live('click',function (){
            $.ajax({
                url:$( this ).attr( 'href' ),
				beforeSend: function(){
					openOnload();
				},
                success: function (html){
					closeOnload();
                    $('#studentsinfo' ).html(html);
                }
            });
            return  false ;
        });
		
    });
	*/

    
	//导入照片
	function ImportPhoto(){
		var form=$('#FormImportPhoto').serialize().split('&');
		//alert(form);
		/*for(i in form){
			var param=form[i].split('=');
			switch(param[0]){
				case 'YearTerm':
				  if(!param[1]){
					  systemInfoDialog('入学年度学期未选择！', 3000);
					  return false;
				  }
				break;
				
				case 'StudentType':
				  if(!param[1]){
					  systemInfoDialog('学生类型未选择！', 3000);
					  return false;
				  }
				break;
				
				case 'collegeCode':
				  if(!param[1]){
					  systemInfoDialog('学院未选择！', 3000);
					  return false;
				  }
				break;
				
				case 'learningCenterCode':
				  if(!param[1]){
					  systemInfoDialog('学习中心未选择！', 3000);
					  return false;
				  }
				break;
				
				default:
				break;
			}
		}*/
		if(!$('#inPhotoRadio').attr('checked')&&!$('#gradPhotoRadio').attr('checked')){
			systemInfoDialog('照片类型未选择！', 3000);
			return false;
		}
		if(!$('#StudentCodeRadio').attr('checked')&&!$('#idCardRadio').attr('checked')){
			systemInfoDialog('证件类型未选择！', 3000);
			return false;
		}
		if(!$('#replaceYesRadio').attr('checked')&&!$('#replaceNoRadio').attr('checked')){
			systemInfoDialog('是否替换未选择！', 3000);
			return false;
		}
		if(!$('#photoFile').val()){
			systemInfoDialog('文件未选择！', 3000);
			return false;
		}
		//$('#FormImportPhoto').submit();
		var url = "<?php echo $this->createUrl('Import');?>";
		$('#FormImportPhoto').ajaxSubmit({
			url:url,
			type:'POST',
			async:true,
			data:$(this).serialize(),
			beforeSubmit: function(){
				openOnload();
			},
			success: function (data){
				closeOnload();
//				alert(data);
//				console.log(data);
//				return false;
				var result=$.parseJSON(data);
				//console.log(result);
				var error=result.Error;
				var msg=new Array();
				for(i in error){
					if(error[i].count>0){
						msg.push('错误原因：“'+error[i].reason+'”错误数：'+error[i].count);
					}
				}
				if(msg==''){
					alert('导入成功！');
				}else{
					alert( msg.join('     ') );
				}
			}
		});
	}
	
	//下载不成功结果
	function DownloadFailResult(){
        var url = "<?php echo $this->createurl("ImportFailResult");?>";
        $.ajax({url: url,
            type: 'post',
            data:'',
			beforeSend: function(){
				openOnload();
			},
            success: function(data){
				closeOnload();
//                alert(data);
//				console.log(data);
//				return false;
				if(data){
					var result=$.parseJSON(data);
					var error=result.Error;
					if(error!=''){
						var msg=new Array();
						for(i in error){
							msg.push('错误原因：“'+error[i].reason+'”错误数：'+error[i].count);
						}
						alert( msg.join('     ') );
					}else{
						window.location=result.Path;
					}
				}else{
					alert('没有错误');
				}
            }
        });
	}
</script>


<style>
#importPhotoTable{
	text-align:right
	
}
#importPhotoTable th{
	width:150px
}
#importPhotoTable td{
	width:200px;
	text-align:left
}
</style>

<div class="search-form">
<?php echo CHtml::beginForm($this->createUrl('Import'), 'post', array('id'=>'FormImportPhoto', 'name'=>'FormImportPhoto','style'=>'margin-top:20px','enctype'=>'multipart/form-data')); ?>
    <table class="Gridview" id="importPhotoTable" cellpadding="5" cellspacing="5">
        <tbody>
          <?php /*?><tr>
              <th>
                  <?php echo CHtml::label('入学年度学期', 'YearTerm'); ?>
              </th>
              <td>
                  <?php echo CHtml::dropDownList('YearTerm', '', $YearTerm, array('empty' => '全部'));?>
              </td>
              <th>
                  <?php echo CHtml::label('学生类型', 'StudentType'); ?>
              </th>
              <td>
                  <?php echo CHtml::dropDownList('StudentType', '', $StudentType, array('empty' => '全部'));?>
              </td>
          </tr>
          
          
          <tr>
              <th>
                  <?php echo CHtml::label('学院', 'collegeCode') ?>
              </th>
              <td>
                  <?php echo CHtml::dropDownList('collegeCode', '', $collegeCode,
                  array(
                      'empty'=>'全部',
                      'ajax' => array(
                          'type' => 'POST',
                          'url' => Yii::app()->createUrl('/Linkage/GetLearningCenter'),
                          'update' => '#learningCenterCode',
                          'data' => array('college' => 'js:$("#collegeCode").val()'),
                      )
                  )); ?>
              </td>
              <th>
                  <?php echo CHtml::label('学习中心', 'learningCenterCode') ?>
              </th>
              <td>
                  <?php echo CHtml::dropDownList('learningCenterCode','', '', array('empty'=>'全部')); ?>
              </td>
          </tr><?php */?>
          <tr>
            <th>导入照片类型</th>
            <td>
              <input type="radio" id="inPhotoRadio" name="PhotoRadio" value="RecruitPIC" />入学照片
              <input type="radio" id="gradPhotoRadio" name="PhotoRadio" value="GraduatePIC" />毕业照片
            </td>
            <th>导入文件命名方式</th>
            <td>
              <input type="radio" id="StudentCodeRadio" name="CodeRadio" value="0" />学号
              <input type="radio" id="idCardRadio" name="CodeRadio" value="1" />证件号
            </td>
          </tr>
          
          <tr>
            <th>如果已存在学生照片，是否使用导入照片替换已有照片</th>
            <td>
              <input type="radio" id="replaceYesRadio" name="replaceRadio" value="1" />是
              <input type="radio" id="replaceNoRadio" name="replaceRadio" value="0" />否
            </td>
            <th></th>
            <td>
            </td>
          </tr>
          
          <tr>
            <th>选择文件</th>
            <td>
              <input type="file" id="photoFile" name="photoFile" /><!--<br />
              <input type="file" id="photoFile2" name="photoFile2" />-->
            </td>
            <th colspan="2" style="text-align:left">
              <a href="javascript:;" onclick="ImportPhoto()">导入</a>
            </th>
          </tr>
        </tbody>
    </table>
<?php echo CHtml::endForm(); ?>
    <div class="instruction">
        导入说明：可以一次批量导入多张照片文件，照片文件请使用压缩工具进行压缩后导入，导入文件格式为.ZIP。此处不支持导入.JPG格式。
    </div>

</div><!-- search-form -->

<?php 
$this->breadcrumbs=array(
    '照片管理',
    '导入照片'=>array('ImportPhoto/Segment'),
);
?>

<div class="btn2" style="width:760px;">
    <a href="javascript:;" onclick="DownloadFailResult()">导入不成功结果下载</a>
</div>


