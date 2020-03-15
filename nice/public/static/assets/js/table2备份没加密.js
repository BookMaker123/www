/*!
 *  Document   : table.js
 *  Description: 表格构建器
 */

jQuery(document).ready(function () {

    // 快速编辑的url提交地址
    $.fn.editable.defaults.url = aiguovip.quick_edit_url;
    // 值为空时显示的信息
    $.fn.editable.defaults.emptytext = '<i class="fa fa-pencil-alt"></i>';
    // 提交时的额外参数
    $.fn.editable.defaults.params = function (params) {
        params.table = $(this).data('table') || '';
        params.type = $(this).data('type') || '';
        params.validate = aiguovip.validate;
        params.validate_fields = aiguovip.validate_fields;
        return params;
    };
    // 提交成功时的回调函数
    $.fn.editable.defaults.success = function (res) {
        if (res.code) {
            AAiguovip.notify(res.msg, 'success');
        } else {
            return res.msg;
        }
    };
    // 提交失败时的回调函数
    $.fn.editable.defaults.error = function (res) {
        if (res.status === 500) {
            return '服务器内部错误. 请稍后重试.';
        } else {
            return res.responseText;
        }
    };

    // 可编辑单行文本
    $('.text-edit').editable();

    $('.kehus').editable({
        url: aiguovip.quick_edit_url,
        validate: function (value) {
            if (value.name == '') return '客户名称必须不为空';
        },
        display: function (value) {
            if (!value) {
                $(this).empty();
                return;
            }
            var html = '<b>' + $('<div>').text(value.name).html() + '</b>, ' + $('<div>').text(value.phone).html() + ',' + $('<div>').text(value.kuaidi).html();
            $(this).html(html);
        }
    });

    // 可编辑多行文本
    $('.textarea-edit').editable({
        showbuttons: 'bottom'
    });

    // 下拉编辑
    $('.select-edit').editable();
    $('.select2-edit').editable({
        select2: {
            multiple: true,
            tokenSeparators: [',', ' ']
        }
    });

    // 日期时间
    $('.combodate-edit').editable({
        combodate: {
            maxYear: 2036,
            minuteStep: 1
        }
    });

    //编辑客户信息


    // 跳转链接
    var goto = function (url, _curr_params) {
        var params = {};
        if ($.isEmptyObject(aiguovip.curr_params)) {
            params = jQuery.param(_curr_params);
        } else {
            $.extend(aiguovip.curr_params, _curr_params);
            params = jQuery.param(aiguovip.curr_params);
        }

        location.href = url + '?' + params;
    };

    // 初始化搜索
    var search_field = aiguovip.search_field;
    if (search_field !== '') {
        $('.search-bar .dropdown-menu a').each(function () {
            var self = $(this);
            if (self.data('field') == search_field) {
                $('#search-btn').html(self.text() + ' <span class="caret"></span>');
            }
        })
    }

    // 搜索
    $('.search-bar .dropdown-menu a').click(function () {
        var field = $(this).data('field') || '';
        $('#search-field').val(field);
        $('#search-btn').html($(this).text() + ' <span class="caret"></span>');
    });
    $('#search-input').on('keyup', function (e) {
        if (e.keyCode === 13) {
            var $url = $(this).data('url');
            var $filed = $('#search-field').val();
            var $keyword = $(this).val();
            var _curr_params = {
                'search_field': $filed || '',
                'keyword': $keyword || ''
            };

            goto($url, _curr_params);
        }
    });

    // 筛选
    $('.table-builder .field-filter').click(function () {
        var self = $(this),
            $field_display = self.data('field-display'), // 当前表格字段显示的字段名，未必是数据库字段名
            $filter = self.data('filter'), // 要筛选的字段
            $_type = self.data('type'), // 筛选方式
            $_filter = aiguovip._filter,
            $_filter_content = aiguovip._filter_content,
            $_field_display = aiguovip._field_display,
            $data = {
                table: self.data('table') || '', // 数据表名
                field: self.data('field') || '', // 数据库字段名
                map: self.data('map') || '', // 筛选条件
                options: self.data('options') || '' // 选项
            };
        layer.open({
            type: 1,
            title: '<i class="fa fa-filter"></i> 筛选',
            shadeClose: true,
            area: ['360px', '530px'],
            btn: ['确定', '取消'],
            content: '<div class="block-content" id="filter-check-content"><i class="fa fa-cog fa-spin"></i> 正在读取...</div>',
            success: function () {
                var $curr_filter_content = '';
                var $curr_filter = '';
                if ($_filter !== '') {
                    $curr_filter = $_filter.split('|');
                    var filed_index = $.inArray($filter, $curr_filter);
                    if (filed_index !== -1) {
                        $curr_filter_content = $_filter_content.split('|');
                        $curr_filter_content = $curr_filter_content[filed_index];
                        $curr_filter_content = $curr_filter_content.split(',');                   
                    }              
                }     
                // 获取数据-jun
                $.post(aiguovip.get_filter_list, $data, function (res) {
                    if (1 !== res.code) {
                        $('#filter-check-content').html(res.msg);
                        return false;
                    }
                    var list = '<div class="form-group"><div class="input-group"><div class="input-group-prepend">  <span class="input-group-text"> <i class="fa fa-search"></i>  </span></div> <input type="text" class="js-field-search form-control" id="example-group2-input1" name="example-group2-input1"></div>  </div>';
                    if ($_type === 'checkbox') {
                        list += '<div class="custom-control custom-checkbox custom-control-lg mb-1">';
                        list += '<input type="checkbox" class="custom-control-input custom-control-input" id="filter-check-all" name="filter-check-all">';
                        list += '<label class="custom-control-label" for="filter-check-all">全选</label></div>';
                    }
                    list += '<div class="filter-field-list">';
                    if(!res.list) list = '<i class="fa fa-database"></i> 没有可筛选内容... ';
                    for (var key in res.list) {
                        // 如果不是该对象自身直接创建的属性（也就是该属//性是原型中的属性），则跳过显示
                        if (!res.list.hasOwnProperty(key)) {
                            continue;
                        }                
                        list += '<div class="row col-sm-12" data-field="' + res.list[key] + '"><div class="custom-control custom-checkbox custom-control-lg mb-1 ">';
                        if ($_type === 'checkbox') {           
                            list += '<input type="checkbox" value="'+key+'" class="custom-control-input" id="checkbox' + key + '" name="checkbox' + key+'"';
                            if ($curr_filter_content !== '' && $.inArray(key, $curr_filter_content) !== -1) {
                                list += ' checked ';
                            }                      
                            list += ' >';
                            list += '<label class="custom-control-label" for="checkbox' + key+ '"> ' + res.list[key] +'</label>'; 
                        } else {
                            //其他选择
                        }
                        list += '</div></div>';
                    }
                    list += '</div>';
                    $('#filter-check-content').html(list);

                    // 查找要筛选的字段
                    var $searchItems = jQuery('.filter-field-list > div');
                    var $searchValue = '';
                    var reg;
                    $('.js-field-search').on('keyup', function () {
                        $searchValue = $(this).val().toLowerCase();

                        if ($searchValue.length >= 1) {
                            $searchItems.hide().removeClass('field-show');

                            $($searchItems).each(function () {
                                reg = new RegExp($searchValue, 'i');
                                if ($(this).text().match(reg)) {
                                    $(this).show().addClass('field-show');
                                }
                            });
                        } else if ($searchValue.length === 0) {
                            $searchItems.show().removeClass('field-show');
                        }
                    });

                }).fail(function () {
                    AAiguovip.notify('服务器发生错误~', 'danger');
                });
            },
            yes: function () {
                var filed_index = -1;
                if ($('#filter-check-content input[class=custom-control-input]:checked').length == 0) {
                    // 没有选择筛选字段，则删除原先该字段的筛选
                    $_filter = $_filter.split('|');
                    filed_index = $.inArray($filter, $_filter);
                    if (filed_index !== -1) {
                        $_filter.splice(filed_index, 1);
                        $filter = $_filter.join('|');

                        $_field_display = $_field_display.split(',');
                        $_field_display.splice(filed_index, 1);
                        $field_display = $_field_display.join(',');

                        $_filter_content = $_filter_content.split('|');
                        $_filter_content.splice(filed_index, 1);
                        $fields = $_filter_content.join('|');
                    }
                } else {
                    // 当前要筛选字段内容
                    var $fields = [];
                    $('#filter-check-content input[class=custom-control-input]:checked').each(function () {
                        if ($(this).val() !== '') {
                            $fields.push($(this).val())
                        }
                    });
                    $fields = $fields.join(',');

                    if ($_filter !== '') {
                        $_filter = $_filter.split('|');
                        filed_index = $.inArray($filter, $_filter);
                        $_filter = $_filter.join('|');

                        if (filed_index === -1) {
                            $filter = $_filter + '|' + $filter;
                            $fields = $_filter_content + '|' + $fields;
                            $field_display = $_field_display + ',' + $field_display;
                        } else {
                            $filter = $_filter;
                            $field_display = $_field_display;
                            $_filter_content = $_filter_content.split('|');
                            $_filter_content[filed_index] = $fields;
                            $fields = $_filter_content.join('|');
                        }
                    }
                }
                var _curr_params = {
                    _filter: $filter || '',
                    _filter_content: $fields || '',
                    _field_display: $field_display || ''
                };
                goto(aiguovip.curr_url, _curr_params);
            }
        });
        return false;
    });
    //W
    $('.table-builder .field-wid').click(function () {        
        var self = $(this),
            $field_display = self.data('field-display'), // 当前表格字段显示的字段名，未必是数据库字段名
            $filter = self.data('filter'), // 要筛选的字段
            $_type = self.data('type'), // 筛选方式
            $_filter = aiguovip._filter,
            $_filter_content = aiguovip._filter_content,
            $_field_display = aiguovip._field_display,
            $data = {
                tiamo: self.data('id') || '', // ID 
                tiamowid: self.data('wid') || '', // ID 
                c_type: 'wx' || '', // ID 
                    
            };
            $('#tiamoo-'+self.data('id') +'wx').html('<i class="fa fa-spinner fa-spin"></i>');
        layer.open({
            type: 1,
            title: '<i class="fab fa-apple"></i> 维修进度',
            shadeClose: true,  //true 
            area: '350px',
            skin:'layui-layer-lan',
            offset:'10px',        
            content: '<div class="" id="filter-check-content"><div class="block-content"><i class="fa fa-cog fa-spin"></i> 正在查询'+self.data('wid')+'维修进度...</div></div>',
            success: function () {               
                // 获取数据-jun
                $.post(aiguovip.wajax_url, $data, function (res) {
                    if (res.status!==true) {
                        var errortishi='<div class="alert alert-danger alert-dismissable" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button><h3 class="alert-heading font-w300 my-2">维修ID：'+self.data('wid').substr(0,5)+'***'+ self.data('wid').substr(8)+'</h3><p class="mb-0">'+res.tishi+'</p></div>';

                        $('#filter-check-content').html(errortishi);
                        $('#tiamoo-'+self.data('id') +'wx').html(res.tishi);
                        return false;
                    }
                    $('#tiamoo-'+self.data('id') +'wx').html(res.tishi);                    
                    var list='<div class="alert alert-success alert-dismissable" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button><h3 class="alert-heading font-w300 my-2">维修ID：'+self.data('wid').substr(0,5)+'***'+ self.data('wid').substr(8)+'</h3><p class="mb-0">'+res.arr.x+'</p><p class="mb-0">'+res.arr.t2+'</p>'+ res.tishi+'</div>';
                    list+='<div class="block block-rounded block-bordered">';
                    var iii=1;
                    for(var sarr of res.arr.s) { 
                        if(iii==1) list+='<div class="block-header block-header-default"><h3 class="block-title"><i class="fa fa-fw fa-truck fa-x text-info"></i>  申请服务</h3><small>'+sarr.statusDate+'</small></div><div class="block-content"><p class="font-w600 mb-2">'+sarr.stepLabel+'</p>'+sarr.stepHeader+'</div>';
                        if(iii==2)list+='<div class="block-header block-header-default"><h3 class="block-title"><a class="img-link mr-2"><i class="fa fa-cog text-primary"></i></a>  诊断设备</h3><small>'+sarr.statusDate+'</small></div><div class="block-content"><p class="font-w600 mb-2">'+sarr.stepLabel+'</p>'+sarr.stepHeader+'</div>';
                        if(iii==3)list+='<div class="block-header block-header-default"><h3 class="block-title"><i class="fa fa-check-square text-success"></i>  服务完成</h3><small>'+sarr.statusDate+'</small></div><div class="block-content"><a class="img-link mr-2"><p class="font-w600 mb-2">'+sarr.stepLabel+'</p>'+sarr.stepHeader+'</div>';
                        iii++;
                    };         
                    list+='</div>';       
                    $('#filter-check-content').html(list);                    
                }).fail(function () {
                    AAiguovip.notify('服务器发生错误~', 'danger');
                    $('#tiamoo-'+self.data('id') +'').html('服务器发生错误');
                });
            },
            
        });
        return false;
    });

    // 筛选框全选或取消全选
    $('body').delegate('#filter-check-all', 'click', function () {
        var $checkStatus = $(this).prop('checked');
        if ($('.js-field-search').val()) {
            $('#filter-check-content .field-show .custom-control-input').each(function () {
                $(this).prop('checked', $checkStatus);
            });
        } else {
            $('#filter-check-content .custom-control-input').each(function () {
                $(this).prop('checked', $checkStatus);
            });
        }
    });

    // 开关
    $('.table-builder .switch input:checkbox').on('click', function () {


        var $switch = $(this);
        var $data = {
            value: $switch.prop('checked'),
            table: $switch.data('table') || '',
            name: $switch.data('field') || '',
            type: 'switch',
            pk: $switch.data('id') || '',
        };
        // 发送ajax请求-Jun
        AAiguovip.loading();
        $.post(aiguovip.quick_edit_url, $data, function (res) {
            AAiguovip.loading('hide');
            if (res.code) {
                AAiguovip.notify(res.msg, 'success');
            } else {
                $switch.prop('checked', !$data.status);//返回按钮
                AAiguovip.notify(res.msg, 'danger');
                return false;
            }

        }).fail(function () {
            AAiguovip.loading('hide');
            AAiguovip.notify('服务器发生错误~', 'danger');
        });
    });

    // 分页搜索
    $('.pagination-info input').click(function () {
        $(this).select();
    });
    $('#go-page,#list-rows').on('keyup', function (e) {
        if (e.keyCode === 13) {
            var _curr_params = {
                'page': $('#go-page').val(),
                'list_rows': $('#list-rows').val()
            };

            goto(aiguovip.curr_url, _curr_params);
        }
    });

    // 时间段搜索
    $('#btn-filter-time').click(function () {
        var _curr_params = {
            '_filter_time_from': $('#_filter_time_from').val(),
            '_filter_time_to': $('#_filter_time_to').val(),
            '_filter_time': $('#_filter_time').val()
        };

        goto(aiguovip.curr_url, _curr_params);
    });

    // 弹出框显示页面
    $('.pop').click(function () {
        var $url = $(this).attr('href');
        var $title = $(this).attr('title') || $(this).data('original-title');
        var $layer = $(this).data('layer');
        var $options = {
            title: $title,
            content: $url
        };

        if ($layer !== undefined) {
            $.extend($options, aiguovip.layer, $layer);
        } else {
            $.extend($options, aiguovip.layer);
        }

        layer.open($options);
        return false;
    });
});