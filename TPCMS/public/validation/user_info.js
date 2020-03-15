/*****************************************************************
 * jQuery Validate扩展验证方法  (linjq)  2342432  
 * Modified by guojunhui
 * Date modified:01/01/2017  
*****************************************************************/
$(function(){
     $("#user-info").validate({
          rules:{
               nickname:{
                    required:true,
                    minlength:4,
                    maxlength:16,
               },
               phone:{
                    required:false,
                    isPhone:true,
               },
               email:{
                    required:false,
                    email:true,
               },
               // password:"required",
               password2:{
                    equalTo: "#password"
               },
          },
          onkeyup:false,
          focusCleanup:true,
          success:"valid",
          submitHandler:function(form){
               $("#user-info").ajaxSubmit({
                    success: function(data){
                         layer.msg(data, {icon:1,time:1000}, function(){
                                        var index = parent.layer.getFrameIndex(window.name);
                                        // parent.location.reload(); //刷新父页面
                                        parent.layer.close(index);
                                   });
                    },
                    error: function(XmlHttpRequest, textStatus, errorThrown){
                         layer.msg(textStatus, {icon:1,time:1000}, function(){
                                        var index = parent.layer.getFrameIndex(window.name);
                                        // parent.location.reload(); //刷新父页面
                                        parent.layer.close(index);
                                   });
                    }
               });
          }
     });
})