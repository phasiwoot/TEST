<script id="scriptPlanUI">
///////////////////// Custom Theme /////////////////////
//if (!$('body.sidebar-mini').hasClass('sidebar-collapse')) {
//    $('body.sidebar-mini').addClass('sidebar-collapse');
//}
////////////////////////////////////////////////////////
function createOption(elem, data, init) {
    //$(elem).select2('destroy');
    $(elem).html('<option value=""><?php echo $GLOBALS['_lng114'];?></option>')
        .append(
            $.map(data, function (o) {
                return '<option value="'+ o.id +'">'+ o.text +'</option>';
            }).join('')
        ).select2();
}

////////////////////////////////////////////////////////// Order //////////////////////////////////////////////////////////
function defaultOrderGroupList() {
    $('#order_format_panel').html(null);
    
    $.each(pickOrderGroup, function(i,k){
        if (k == 'order_id') {
            return;
        }
        
        k = orderGroupList.filter(function(o){ return o.key == k; })['0'];
        
        $('#order_format_panel').append('<span class="format-box format-sort selected" data-sort="'+ k.key +'" onclick="openOrderGroupList(this, \''+ k.key +'\')">'+ k.title
            +'</span><span class="format-box"><i class="fa fa-arrow-right"></i></span>');
    });
    
    $('#order_format_panel').append('<span class="format-box format-sort empty" data-sort="empty" onclick="openOrderGroupList(this, \'empty\')"></span>'
        +'<span class="format-box"><i class="fa fa-arrow-right"></i></span>');

    $('#order_format_panel').append('<span class="format-box format-sort selected disabled" data-sort="order_id"><?php echo $GLOBALS['_lng4'];?></span>');
}

function resetOrderGroupList() {
    pickOrderGroup = ['consignment_no', 'order_id'];
    
    defaultOrderGroupList();
    keepPickOrderGroup();
}

function openOrderGroupList(elem, key) {
    createOrderGroupList();
    
    var _this = $(elem);
    var _key = key;
    
    if (_this.hasClass('disabled') || _key == 'order_no') {
        return false;
    }
    
    $('#order_format_panel').data('sort', _key);
    $('input:checkbox[name="cb_order_format"]:checked').prop('checked', false);
    $('input:checkbox[name="cb_order_format"][value="'+ _key +'"]').prop('checked', true);
    
    $('#modal_pick_order_group').modal('show');
}

function createOrderGroupList() {
    $('#modal_pick_order_group .modal-body').html(
            $.map(orderGroupList, function(o){
                if (o.key == 'order_id') {
                    return null;
                }
                
                return '<div class="margin-bottom"><input type="checkbox" class="iCheck" name="cb_order_format" value="'+ o.key +'" /> '+ o.title +'</div>';
            }).join('')
        );
            
    $('input:checkbox[name="cb_order_format"]').off('change').on('change', function(){
        if ($(this).prop('checked')) {
            $('input:checkbox[name="cb_order_format"]:checked').not(this).prop('checked', false);
        }
    });
}

function keepPickOrderGroup() {
    pickOrderGroup = $('#order_format_panel .format-sort.selected').toArray().map(function(o){ return o.dataset.sort; });
    
    var localDb = window.localStorage;
    localDb.setItem('cache-pickOrderGroup', pickOrderGroup.join(','));
}
    
function pickOrderGroupList() {
    //$('#order_format > .planUI-box').loadingUI('show');
    
    var _boxSort = $('#order_format_panel').data('sort');
    var _this = $('#order_format_panel .format-sort[data-sort="'+ _boxSort +'"]');
    
    if ($('input:checkbox[name="cb_order_format"]:checked').length) {
        var _key = $('input:checkbox[name="cb_order_format"]:checked').val();
        var _title = orderGroupList.filter(function(o){ return o.key == _key; })[0].title;
        
        $('#order_format_panel .format-sort[data-sort="'+ _key +'"]').next().remove();
        $('#order_format_panel .format-sort[data-sort="'+ _key +'"]').remove();
        
        _this.next().after('<span class="format-box format-sort new"></span><span class="format-box"><i class="fa fa-arrow-right"></i></span>');
        $('#order_format_panel .format-sort.new')[0].dataset.sort = _key;
        $('#order_format_panel .format-sort.new')[0].setAttribute('onclick', 'openOrderGroupList(this, \''+ _key +'\')');
        $('#order_format_panel .format-sort.new')[0].innerText = _title;
        $('#order_format_panel .format-sort.new').removeClass('new').addClass('selected');
        
        $('#order_format_panel .format-sort[data-sort="'+ _boxSort +'"]').next().remove();
        $('#order_format_panel .format-sort[data-sort="'+ _boxSort +'"]').remove();
        
        // If Empty was used then new empty
        if ($('#order_format_panel .format-sort[data-sort="empty"]').length === 0) {
            $('#order_format_panel .format-sort[data-sort="order_id"]')
                .before('<span class="format-box format-sort empty" data-sort="empty" onclick="openOrderGroupList(this, \'empty\')"></span>'
                        +'<span class="format-box"><i class="fa fa-arrow-right"></i></span>');
        }
    } else {
        if (!_this.hasClass('empty')) {
            _this.next().remove();
            _this.remove();
        }
    }
    
    keepPickOrderGroup();
    
    window.setTimeout(function(){
        getOrderGroup();
    }, 300);
    
    
    /////////////////////////////////////////////////////////////////
    createVehicleLoadStatusBox();
    getCustomerConfigPickup();
        
    //$('#order_format > .planUI-box').loadingUI('hide');
    $('#modal_pick_order_group').modal('hide');
}

function calPadLeft(level) {
    return (+level === 1)? 120: 35;
}

function groupBy(xs, key, value) {
    return xs.reduce(function(rv, x) {
        let k = x[key];
        
        if (!rv[k]) {
            rv[k] = {sub: []};
        }
        
        rv[k].key = x[key];
        rv[k].value = x[value];
        rv[k].total_weight = (rv[k].total_weight || 0) + (x.total_item_weight || 0);
        rv[k].sub.push(x);
        
        return rv;
    }, {});
}

function createOrderGroup(parent, level, data) {
    if (!data.length) {
        return;
    }
    
    var thisGroupLevel = level + 1, thisGroupKey = pickOrderGroup[level];
    
    if (thisGroupLevel < pickOrderGroup.length) {
        var thisGroupObject = orderGroupList.filter(function(o){ return o.key == thisGroupKey; })['0']
        , thisGroupValue = thisGroupObject.value
        //, thisGroupTitle = thisGroupObject.title
        , thisGroupData = groupBy(data, thisGroupKey, thisGroupValue)
        , subGroupTitle = orderGroupList.filter(function(o){ return o.key == pickOrderGroup[thisGroupLevel]; })['0'].title
        , margL = calPadLeft(thisGroupLevel);
         
        $.each(thisGroupData, function(k,v){
            var title = v.value +'&emsp;('+ v.sub.length +' '+ subGroupTitle +', <?php echo getWeightWord_WithoutKG($GLOBALS['_lng373']);?> '+ number_format(v.total_weight, 0, 2) +' <?php echo $GLOBALS['_lng375'];?>)';
            var checkboxHTML = '<input type="checkbox" class="iCheck" name="cb_'+ thisGroupKey +'" onchange="eventOrderGroup(event, this)" value="'+ v.key +'" />';
            var groupTitleHTML = '<span class="group-label">'+ checkboxHTML +'<span onclick="OpenOrderGroup(this)">'+ title +'</span></span>';
            var groupItem = $('<li class="item-group group-level-'+ thisGroupLevel +'" data-group-level="'+ thisGroupLevel +'" style="margin-left:'+ margL +'px">'+ groupTitleHTML +'<ul class="sub-group"></ul></li>');

            // Insert to parent
            parent.append(groupItem);
//            // Bind Event
//            bindEventOrderGroup(thisGroupKey, groupItem);
            
            createOrderGroup(groupItem.find('ul.sub-group'), thisGroupLevel, v.sub);
        });
    } else {
        $.each(data, function(k,v){
            /////////////////////// Order ///////////////////////
            createOrder(parent, thisGroupKey, thisGroupLevel, v);
        });
    }
}

function createOrder(parent, groupKey, level, obj) {
    var margL = calPadLeft(level);
    var provice = obj.abb_prov;
    var title = obj.order_no +'&emsp;'+ provice +'&emsp;(<?php echo getWeightWord_WithoutKG($GLOBALS['_lng373']);?> '+ number_format(obj.total_item_weight, 0, 2) +' <?php echo $GLOBALS['_lng375'];?>)';
    var checkboxHTML = '<input type="checkbox" class="iCheck" name="cb_order_id" onclick="eventOrder(event, this)" onchange="eventOrder(event, this)" value="'+ obj.order_id +'" />';
    var groupTitleHTML = '<span class="group-label">'+ checkboxHTML +'<span onclick="OpenOrder(this)">'+ title +'</span></span>';
    var groupItem = $('<li class="item-group order_no group-level-'+ level +'" data-group-level="'+ level +'" style="margin-left:'+ margL +'px">'+ groupTitleHTML +'<ul class="sub-group"></ul></li>');
        groupItem.data({'group-value': obj.order_no, 'total-item-weight': obj.total_item_weight, 'config-pickup': obj.config_pickup});
        
    var total = Number(obj.total_item_qty), planned = Number(obj.total_item_qty_planned);
    //if (total <= planned) {
	if (obj.order_status == '2' || obj.order_status == '3') {
        groupItem.find('input:checkbox[name="cb_order_id"]').removeAttr('name').prop('disabled', true);
        groupItem.addClass('planned');
    } else if (total > planned && planned > 0) {
        //groupItem.find('input:checkbox[name="cb_order_id"]').removeAttr('name').prop('disabled', false);
        groupItem.addClass('planning');
    }
    
    // Insert to parent
    parent.append(groupItem);
    // Bind Event
    //bindEventOrder(groupKey, groupItem);
}

function OpenOrderGroup(elem) {
    var _this = $(elem);
    var _parent = _this.closest('li.item-group');
    
    if (!_parent.hasClass('collapsed')) {
        _parent.addClass('collapsed');
        _parent.find('> .group-label .btn_group_details').addClass('fa-minus-square-o').removeClass('fa-plus-square');
    } else {
        _parent.removeClass('collapsed');
        _parent.find('> .group-label .btn_group_details').addClass('fa-plus-square').removeClass('fa-minus-square-o');
    }
}

function OpenOrder(elem) {
    var _this = $(elem);
    var _parent = _this.closest('li.item-group');
    var _value = _parent.data('group-value').trim();
    
    if (!_parent.hasClass('collapsed')) {
        _parent.addClass('collapsed');
        _parent.find('> .group-label .btn_group_details').addClass('fa-minus-square-o').removeClass('fa-plus-square');

        getOrderDetail(_parent.find('ul.sub-group'), _value);
    } else {
        _parent.removeClass('collapsed');
        _parent.find('> .group-label .btn_group_details').addClass('fa-plus-square').removeClass('fa-minus-square-o');
        _parent.find('.order_detail').hide();
    }
}

function eventOrderGroup(event, elem) {
    var _this = $(elem);
    var _parent = _this.closest('li.item-group');

    if (event.type == 'change') {
        //var _level = _parent.data('groupLevel');
        
        if (_this.prop('checked')) {
            _parent.find('input:checkbox:not(:disabled)').prop('checked', true);
            $('input:checkbox[name="cb_order_id"]:checked:not(:disabled):last').trigger('change');
        } else {
            _parent.find('input:checkbox:not(:disabled)').prop('checked', false);
            $('input:checkbox[name="cb_order_id"]:checked:not(:disabled):last').trigger('change');
        }
    }
}

function eventOrder(event, elem) {
    var _this = $(elem);
    var _parent = _this.closest('li.item-group');
    
    if (event.type == 'change') {
        createVehicleLoadStatusBox();
        getCustomerConfigPickup();
                
        // Call Delivery Routing Simulation
        getDeliveryFleetRoute();
    }
}

function createOrderDetail(parent, data) {
    var orderDetail = parent.find('.order_detail');
    if (!orderDetail.length) {
        orderDetail = $('<div class="order_detail" style="display:block;padding:10px 22px"></div>');
        parent.append(orderDetail);
    }
    
    ///////////////////////////////// Table Order Detail /////////////////////////////////
    var btnView = '', btnDelete = '';
    if (+data[0].delivery_id > 0) {
        btnView = '';
        btnDelete = '<button class="button btn btn-danger btn-xs" onclick="deletePlanOrder(\''+ data[0].delivery_id +'\',\''+ data[0].order_id +'\')"><i class="fa fa-close"></i> <?php echo $GLOBALS['_lng39'];?></button>';
        
        if (+data[0].plan_status != 0) {
            btnDelete = '<button class="button btn btn-danger btn-xs" disabled><i class="fa fa-close"></i> <?php echo $GLOBALS['_lng39'];?></button>';
        }
    }
    
    var str_builder = '<table style="width:88%;margin-left:3%">';//margin:auto
    str_builder += '<tr>';
    str_builder += '<td style="width:100px"><b><?php echo $GLOBALS['_lng76'];?></b></td>';
    str_builder += '<td style="">'+ data[0].order_no +'</td>';
    str_builder += '<td style="width:100px"><b><?php echo $GLOBALS['_lng65'];?></b></td>';
    str_builder += '<td style="">'+ data[0].request_date +'</td>';
    str_builder += '<td style="width:100px"><b><?php echo $GLOBALS['_lng12'];?></b></td>';
    str_builder += '<td style="width:160px">'+ data[0].src_name +'</td>';
    str_builder += '</tr>';
    str_builder += '<tr>';
    str_builder += '<td><b><?php echo $GLOBALS['_lng7'];?></b></td>';
    str_builder += '<td>'+ data[0].customer_name +'</td>';
    str_builder += '<td><b><?php echo $GLOBALS['_lng79'];//426?></b></td>';
    str_builder += '<td>'+ data[0].des_name +'</td>';
    str_builder += '<td colspan="2"><div class="pull-right">'+ btnDelete +'</div></td>';
    str_builder += '</tr>';
	if (data[0].plan_detail.length > 0) {
		let split_plandetail = data[0].plan_detail.split('@');
		
		str_builder += '<tr>';
		str_builder += '<td><b><?php echo $GLOBALS['_lng72'];?></b></td>';
		str_builder += '<td>'+ split_plandetail[0] +' ( '+ split_plandetail[1] +' )</td>';
		//str_builder += '<td><b><?php echo $GLOBALS['_lng64'];?></b></td>';
		//str_builder += '<td>'+ split_plandetail[1] +'</td>';
		str_builder += '<td><b><?php echo $GLOBALS['_lng80'];?></b></td>';
		str_builder += '<td>'+ split_plandetail[2] +'</td>';
		str_builder += '</tr>';
	}
    str_builder += '</table>';
    
    ///////////////////////////////// Table Order Item /////////////////////////////////
    str_builder += '<table class="table table-bordered" style="width:90%;margin-top:10px;margin-left:2%">';//margin:auto;
    str_builder += '<thead>';
    str_builder += '<tr>';
    str_builder += '<th style="width:7%"><b><?php echo $GLOBALS['_lng149'];?></b></th>';
    str_builder += '<th style="width:20%"><?php echo $GLOBALS['_lng77'];?></th>';
    str_builder += '<th><?php echo $GLOBALS['_lng73'];?></th>';
    str_builder += '<th style="width:18%"><?php echo getWeightWord_WithoutKG($GLOBALS['_lng78']);?></th>';
    //str_builder += '<th style="width:18%"><?php echo $GLOBALS['_lng74'];?></th>';
    str_builder += '</tr>';
    str_builder += '</thead>';
    str_builder += '<tbody>';
    
    var sum_item_qty = 0, sum_item_weight = 0;
    $.each(data, function(i,v){
        sum_item_qty += Number(v.item_qty);
        sum_item_weight += Number(v.total_weight);
        
        str_builder += '<tr>';
        str_builder += '<td class="text-center">'+ (i+1) +'</td>';//<input type="checkbox" class="iCheck" checked disabled />
        str_builder += '<td>'+ v.item_line_no +'</td>';
        str_builder += '<td>'+ v.item_name +'</td>';
        str_builder += '<td class="text-right">'+ number_format(v.item_weight, 0, 2) +' <item-unit><?php echo $GLOBALS['_lng375'];?></item-unit></td>';
        //str_builder += '<td class="text-right">'+ number_format(v.item_qty, 0, 0) +' <item-unit>'+ data[0].item_unit_name +'</item-unit></td>';
        str_builder += '</tr>';
    });
    
    str_builder += '</tbody>';
    str_builder += '</table>';
    
    orderDetail.html(str_builder).show();
    
    // Bind Event
    bindEventOrderDetail(parent);
    
    // Callback
    parent.closest('li.item-group').find('input:checkbox[name="cb_order_id"]:checked').trigger('ifChanged');
}

function bindEventOrderDetail(parent) {
    
}

////////////////////////////////////////////////////////// Vehicle //////////////////////////////////////////////////////////
function createVehicleFleet(parent, data) {
    var str_builder = '<table id="tb_vehicle_list" class="table table-bordered">';
    str_builder += '<thead>';
    str_builder += '<tr>';
    str_builder += '<th rowspan="2" style="width:8%"></th>';
    str_builder += '<th style="width:15%"><?php echo $GLOBALS['_lng80'];?></th>';
    str_builder += '<th style="width:15%"><?php echo $GLOBALS['_lng120'];?></th>';
    str_builder += '<th style="width:15%"><?php echo $GLOBALS['_lng11'];?></th>';
    str_builder += '<th style="width:15%"><?php echo $GLOBALS['_lng12'];?></th>';
    str_builder += '<th rowspan="2" style="width:15%">Max. Loading</th>';
    str_builder += '<th rowspan="2" style="width:18%"><?php echo $GLOBALS['_lng445'];?></th>';
    str_builder += '<th rowspan="2" style="width:14%"><?php echo $GLOBALS['_lng446'];?></th>';
    str_builder += '</tr>';
    str_builder += '<tr>';
    str_builder += '<th><input type="text" class="form-control" name="txt_vehicle_name" value="" placeholder="search.." /></th>';
    str_builder += '<th><input type="text" class="form-control" name="txt_vehicle_type_name" value="" placeholder="search.." /></th>';
    str_builder += '<th><input type="text" class="form-control" name="txt_driver_name" value="" placeholder="search.." /></th>';
    str_builder += '<th><input type="text" class="form-control" name="txt_vehicle_contractor" value="" placeholder="search.." /></th>';
    str_builder += '</tr>';
    str_builder += '</thead>';
    str_builder += '<tbody>';
    $.each(data, function(i,v){
        var btnViewJob = '';//'<span class="pull-right"><i class="fa fa-search"></i></span>';
        str_builder += '<tr data-vehicle-name="'+ v.vehicle_name +'" data-vehicle-type="'+ v.vehicle_type_name +'" data-max-load="'+ v.max_loading_weight +'">';
        str_builder += '<td class="text-center"><input type="checkbox" class="iCheck" name="cb_vehicle_fleet" onchange="eventVehicleFleet(event, this)" value="'+ v.vehicle_id +'" /></td>';
        str_builder += '<td>'+ v.vehicle_name + btnViewJob +'</td>';
        str_builder += '<td>'+ v.vehicle_type_name +'</td>';
        str_builder += '<td>'+ v.driver_name +'</td>';
        str_builder += '<td>'+ v.contractor_name +'</td>';
        str_builder += '<td class="text-right">'+ number_format(v.max_loading_weight, 0, 2) +' <item-unit><?php echo $GLOBALS['_lng375'];?></item-unit></td>';
        str_builder += '<td style="font-size:8pt">'+ v.last_plan_route +'</td>';
        str_builder += '<td style="font-size:8pt">'+ v.last_plan_time +'</td>'; /*(v.last_delivery_date).substr(11,5)*/
        str_builder += '</tr>';
    });
    str_builder += '</tbody>';
    str_builder += '</table>';
    
    parent.html(str_builder);
    
    $('input:radio[name="rad_plan_type"]').trigger('change');
    // Bind Event
    bindEventVehicleFleet(parent);
}

function eventVehicleFleet(event, elem) {
    var _this = $(elem);
    var _parent = _this.closest('tr');
    
    if (event.type == 'change') {
        var _value = _this.val();
        
        if (_this.prop('checked')) {
            if ($('input:radio[name="rad_plan_type"]:checked').val() == "normal") {
                _parent.closest('tbody').find('input:checkbox[name="cb_vehicle_fleet"]:checked').not(_this).prop('checked', false);
                
                // Call Delivery Fleet (Plan)
                getDeliveryFleet(_parent, _value); 
            }           
        } else {
            $('#tr_delivery_list').remove();
            $('#route_example_flowchart').empty();
            
            abortRequest(ajaxReqDeliveryFleetRoute);
            ////////////////////////////////////////
        }
        
        /////////////////////////////////////////////////////////////////
        createVehicleLoadStatusBox();
        getCustomerConfigPickup();
    }
}

function bindEventVehicleFleet(parent) {
    // Bind Search on Table Header
    $('#tb_vehicle_list > thead').on('keyup', function(){
        var _this = $(this);
        var _table = $('#tb_vehicle_list');
        
        _table.find('input:checkbox[name="cb_vehicle_fleet"]:checked').prop('checked', false);
        //_table.find('input:checkbox[name="cb_vehicle_fleet"]:checked').iCheck('uncheck');
        
        var txtSearch1 = _this.find('input[name="txt_vehicle_name"]').val().trim().toLowerCase();
        var txtSearch2 = _this.find('input[name="txt_vehicle_type_name"]').val().trim().toLowerCase();
        var txtSearch3 = _this.find('input[name="txt_driver_name"]').val().trim().toLowerCase();
        var txtSearch4 = _this.find('input[name="txt_vehicle_contractor"]').val().trim().toLowerCase();
        
        _table.find('tbody > tr').each(function(i,v){
            var txtColumn1 = $(v).find('td:eq(1)').text().trim().toLowerCase();
            var txtColumn2 = $(v).find('td:eq(2)').text().trim().toLowerCase();
            var txtColumn3 = $(v).find('td:eq(3)').text().trim().toLowerCase();
            var txtColumn4 = $(v).find('td:eq(4)').text().trim().toLowerCase();
            
            if (( new RegExp(txtSearch1) ).test(txtColumn1) && ( new RegExp(txtSearch2) ).test(txtColumn2)
                && ( new RegExp(txtSearch3) ).test(txtColumn3) && ( new RegExp(txtSearch4) ).test(txtColumn4)) {
            
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
}

function createDeliveryFleet(parent, data) {
    var str_builder = '<div id="tb_delivery_list" style="padding:10px">';
    str_builder += '<table class="table table-bordered" style="">';
    str_builder += '<thead>';
    str_builder += '<tr>';
    str_builder += '<th style="width:8%"></th>';
    str_builder += '<th style="width:20%">Delivery #</th>';
    str_builder += '<th style="width:14%"><?php echo $GLOBALS['_lng74'];?></th>';
    str_builder += '<th style="width:18%"><?php echo $GLOBALS['_lng438'];?></th>';
    str_builder += '<th style="width:22%"><?php echo $GLOBALS['_lng367'];?></th>';
    str_builder += '<th style="width:18%"></th>';
    str_builder += '</tr>';
    str_builder += '</thead>';
    str_builder += '<tbody>';
    
    var btnView, btnEdit, btnDelete;
    $.each(data, function(i,v){
        btnView = '<button class="btn btn-xs btn-primary btn_view_delivery_fleet" onclick="viewPlan(\''+ v.delivery_id +'\')"><i class="fa fa-search"></i></button>';
        btnEdit = '<button class="btn btn-xs btn-warning btn_edit_delivery_fleet" onclick="getRouting(\''+ v.delivery_id +'\')"><i class="fa fa-edit"></i></button>';
        btnDelete = '<button class="btn btn-xs btn-danger btn_delete_delivery_fleet" onclick="deletePlan(\''+ v.delivery_id +'\')"><i class="fa fa-trash"></i></button>';
        
        if (Number(v.plan_status) !== 0) {
            btnEdit = '<button class="btn btn-xs btn-warning" disabled><i class="fa fa-edit"></i></button>';
            btnDelete = '<button class="btn btn-xs btn-danger" disabled><i class="fa fa-trash"></i></button>';
        }
        
        str_builder += '<tr data-delivery-no="'+ v.delivery_no +'" data-current-load="'+ v.current_load_weight +'">';
        str_builder += '<td class="text-center"><input type="checkbox" class="iCheck" name="cb_delivery_fleet" onchange="eventDeliveryFleet(event, this)" value="'+ v.delivery_id +'" /></td>';
        str_builder += '<td>'+ v.delivery_no +'</td>';
        str_builder += '<td class="text-center">'+ v.order_qty +'</td>';
        str_builder += '<td class="text-right">'+ number_format(v.total_plan_distance, 0, 2) +' <item-unit><?php echo $GLOBALS['_lng437'];?></item-unit></td>';
        str_builder += '<td class="text-right">'+ number_format(v.current_load_weight, 0, 2) +' ('+ number_format(v.percent_loading_weight, 0, 2) +'%)' +' <item-unit><?php echo $GLOBALS['_lng375'];?></item-unit></td>';
        str_builder += '<td class="text-center">'+ btnView +'&nbsp;'+ btnEdit +'&nbsp;'+ btnDelete +'</td>';
        str_builder += '</tr>';
    });
    
    str_builder += '</tbody>';
    str_builder += '</table>';
    str_builder += '</div>';
    
    parent.after('<tr id="tr_delivery_list"><td colspan="8" style="padding:0">'+ str_builder +'</td></tr>');
    parent.find('td:eq(5)').html(data['0'].last_plan_route);
    parent.find('td:eq(6)').html(data['0'].last_plan_time);
    
    $('#tb_vehicle_list').find('input:checkbox[name="cb_delivery_fleet"]:first').prop('checked', true);//.trigger('change');
}

function eventDeliveryFleet(event, elem) {
    var _this = $(elem);
    var _parent = _this.closest('tr');
    
    if (event.type == 'change') {
        //var _value = _this.val();
        
        if (_this.prop('checked')) {
            _parent.closest('table').find('input:checkbox[name="cb_delivery_fleet"]:checked').not(_this).prop('checked', false);
        } else {            
            //$('#route_example_flowchart').empty();
        }
        
        // Call Delivery Routing Simulation
        getDeliveryFleetRoute();
        
        /////////////////////////////////////////////////////////////////
        createVehicleLoadStatusBox();
        getCustomerConfigPickup();
    }
}

function createDeliveryFleetRoute(data) {
    var parentWidth = $('#route_example_flowchart').innerWidth();
    var numberOfRow = 0, numberOfColum = 5, totalColum = 0;
    var grid_column = '25% 12.5% 25% 12.5% 25%';
    
    if (parentWidth >= 1200) {
        numberOfColum = 5;
        grid_column = '13% 8.75% 13% 8.75% 13% 8.75% 13% 8.75% 13%';
    } else if (parentWidth >= 870) {
        numberOfColum = 4;
        grid_column = '17.5% 10% 17.5% 10% 17.5% 10% 17.5%';
    } else if (parentWidth >= 640) {
        numberOfColum = 3;
        grid_column = '25% 12.5% 25% 12.5% 25%';
    } else if (parentWidth >= 400) {
        numberOfColum = 2;
        grid_column = '40% 20% 40%';
    } else {
        numberOfColum = 1;
        grid_column = '100%';
    }
    
    $('#route_example_flowchart').css('grid-template-columns', grid_column);
    
    totalColum = (numberOfColum * 2) - 1;
    numberOfRow = Math.ceil(data.length / numberOfColum);
    
    for (var i=0; i<numberOfRow; i++) {
        var dataSlice = data.slice(numberOfColum * i, numberOfColum * (i+1));
        //var btnAddRoute = '<div class="line"><span class="btn_add_route_point" onclick="openAddRoutePoint()"></span></div><div class="point"></div>';
        var btnAddRoute = '<div class="line"></div><div class="point"></div>';
        var arrowLine = '<div class="flowchart-diagram"><span class="arrow-line"><div class="arrow arrow-right">'+ btnAddRoute +'</div></span></div>';
        var arrowNextRow = ('<div class="flowchart-diagram"></div>').repeat(totalColum - 1) + '<div class="flowchart-diagram"><span class="arrow-line"><div class="arrow arrow-bottom">'+ btnAddRoute +'</div></span></div>';
        var leftEmpty = '';
        var rightEmpty = '';
        
        if (dataSlice.length < numberOfColum) {
            var numOfEmpty = totalColum - ((dataSlice.length * 2) - 1);
            leftEmpty = '';
            rightEmpty = ('<div class="flowchart-diagram"></div>').repeat(numOfEmpty);
        }
        
        if (i%2 === 1) {
            dataSlice = dataSlice.reverse();//flip array when row is odd
            arrowLine = '<div class="flowchart-diagram"><span class="arrow-line"><div class="arrow arrow-left">'+ btnAddRoute +'</div></span></div>';
            arrowNextRow = '<div class="flowchart-diagram"><span class="arrow-line"><div class="arrow arrow-bottom">'+ btnAddRoute +'</div></span></div>'+ ('<div class="flowchart-diagram"></div>').repeat((numberOfColum*2)-2);
            leftEmpty = rightEmpty;
            rightEmpty = '';
        }
        
        $('#route_example_flowchart').append(leftEmpty
            , $($.map(dataSlice, function(o){
                    return '<div class="flowchart-diagram">'
                            +'<div class="step-route '+ (!+o.delivery_route_id? 'new': '') +'" title="'+ o.station_name +'">'
                                +'<span class="title">'+ o.route_seq +'. '+ o.station_name +'</span>'
                                +'<span><?php echo $GLOBALS['_lng259'];?>: '+ (o.plan_in_time==null? '-': o.plan_in_time) +'</span>'
                                +'<span><?php echo $GLOBALS['_lng260'];?>: '+ o.plan_out_time +'</span>'
                            +'</div>'
                        +'</div>';
                }).join(arrowLine))
            , rightEmpty);

        if (i + 1 < numberOfRow) {
            $('#route_example_flowchart').append(arrowNextRow);
        }
    }
    
    if (!!$('#route_example_flowchart .step-route:not(.new)').length) {
        $('#btn_edit_plan').addClass('visible');
    } else {
        $('#btn_edit_plan').removeClass('visible');
    }
}

function openAddRoutePoint() {
    $('#modal_add_route_point').modal('show');
}

function createViewPlan(data) {
    var v = data['0'];
    
    var btnDelete = '<button class="button btn btn-danger btn-xs" onclick="deletePlan(\''+ v.delivery_id +'\')"><i class="fa fa-trash"></i> <?php echo $GLOBALS['_lng118'];?></button>';
    if (+v.plan_status != 0) {
        btnDelete = '<button class="button btn btn-danger btn-xs" disabled><i class="fa fa-trash"></i> <?php echo $GLOBALS['_lng118'];?></button>';
    }
    
    var str_builder = '<div class="box-row clear" style="padding-top:4px">'
        +'<div style="float:left;width:38%">'
            +'<table class="header" style="float:left;width:auto">'
                +'<tbody>'
                    +'<tr>'
                        +'<td colspan="2"><b><?php echo $GLOBALS['_lng72'];?></b>'+ v.delivery_no +'</td>'
                    +'</tr>'
                    +'<tr>'
                        +'<td><b><?php echo $GLOBALS['_lng64'];?>&emsp;</b></td>'
                        +'<td>'+ v.delivery_date +'</td>'
                    +'</tr>'
                    +'<tr>'
                        +'<td style="width:auto"><b><?php echo $GLOBALS['_lng367']/*getWeightWord_WithoutKG($GLOBALS['_lng373']);*/;?>&emsp;</b></td>'
                        +'<td>'+ number_format(v.current_load_weight, 0, 2) +' ('+ number_format(v.percent_loading_weight, 0, 2) +'%) <item-unit><?php echo $GLOBALS['_lng375'];?></item-unit></td>'
                    +'</tr>'
                +'</tbody>'
            +'</table>'
        +'</div>'
        +'<div style="float:left;width:27%">'
            +'<table class="header" style="width:auto;margin: auto;">'
                +'<tbody>'
                    +'<tr>'
                        +'<td style="width:auto"><b><?php echo $GLOBALS['_lng80'];?>&emsp;</b></td>'
                        +'<td>'+ v.vehicle_name +'</td>'
                    +'</tr>'
                    +'<tr>'
                        +'<td><b><?php echo $GLOBALS['_lng22'];?>&emsp;</b></td>'
                        +'<td>'+ v.vehicle_type_name +'</td>'
                    +'</tr>'
                    +'<tr>'
                        +'<td><b><?php echo $GLOBALS['_lng11'];?>&emsp;</td>'
                        +'<td>'+ v.driver_name +'</td>'
                    +'</tr>'
                +'</tbody>'
            +'</table>'
        +'</div>'
        +'<div style="float:left;width:35%">'
            +'<table class="header" style="float:right;width:auto">'
                +'<tbody>'
                    +'<tr>'
                        +'<td><b><?php echo $GLOBALS['_lng12'];?>&emsp;</b></td>'
                        +'<td>'+ v.contractor_name +'</td>'
                    +'</tr>'
                    +'<tr><td colspan="2">&nbsp;</td></tr>'
                    +'<tr>'
                        +'<td colspan="2"><div class="pull-right">'+ btnDelete +'</div></td>'
                    +'</tr>'
                +'</tbody>'
            +'</table>'
        +'</div>'
    +'</div><hr/>';
    
    $('#modal_view_plan .modal-body').html(str_builder);
}

function createViewPlanOrder(data) {
	if (data.length) {
		var str_builder = '', str_builder_body = '';
		var ptr_order_no = '', running_seq = 0;
		
		$.each(data, function(i,v){
			/*
			var btnDeleteItem = '<button class="button btn btn-danger btn-xs" onclick="deletePlanItem(\''+ v.delivery_id +'\',\''+ v.delivery_item_id +'\')"><i class="fa fa-trash"></i></button>';
			if (Number(v.plan_status) !== 0) {
				btnDeleteItem = '<button class="button btn btn-danger btn-xs" disabled><i class="fa fa-trash"></i></button>';
			}*/
			
			if (v.order_no != ptr_order_no) {
				var btnDelete = '<button class="button btn btn-danger btn-xs" onclick="deletePlanOrder(\''+ v.delivery_id +'\',\''+ v.order_id +'\')"><i class="fa fa-close"></i> <?php echo $GLOBALS['_lng39'];?></button>';
				
				if (+v.plan_status != 0) {
					btnDelete = '<button class="button btn btn-danger btn-xs" disabled><i class="fa fa-close"></i> <?php echo $GLOBALS['_lng39'];?></button>';
				}

				str_builder += (i > 0 ? '</table>' : ''); // Close table tag previous ORDER NO
				str_builder += '<table class="table table-bordered" style="margin-top:10px;border:0">'
					+'<tbody>'
						+'<tr>'
							+'<td colspan="6" class="" style="border:0">'
								+'<table class="no-border" style="width:auto;table-layout:fixed">'
									+'<tr>'
										+'<td colspan="2"><b><?php echo $GLOBALS['_lng76']; ?></b>'+ v.order_no +'</td>'
										+'<td><b><?php echo $GLOBALS['_lng65']; ?></b>&emsp;</td>'
										+'<td>'+ v.request_date +'</td>'
									+'</tr>'
									+'<tr>'
										+'<td><b><?php echo $GLOBALS['_lng12']; ?></b>&emsp;</td>'
										+'<td style="width:140px">'+ v.contractor_name +'</td>'
										+'<td><b><?php echo $GLOBALS['_lng7']; ?></b>&emsp;</td>'
										+'<td>'+ v.customer_name +'</td>'
									+'</tr>'
									+'<tr>'
										+'<td colspan="3"></td>'
										+'<td><i>'+ v.des_address +'</i></td>'
									+'</tr>'
								+'</table>'
								+'<div class="pull-right">'+ btnDelete +'</div>'
							+'</td>'
						+'</tr>'
					+'</tbody>'
					+'<tbody>'
						+'<tr>'
							+'<th style="width:8%"><?php echo $GLOBALS['_lng149']; ?></th>'
							+'<th style="width:13%"><?php echo $GLOBALS['_lng77']; ?></th>'
							+'<th style="width:30%"><?php echo $GLOBALS['_lng73']; ?></th>'
							+'<th style="width:15%"><?php echo getWeightWord_WithoutKG($GLOBALS['_lng78']); ?></th>'
							//+'<th style="width:15%"><?php echo $GLOBALS['_lng74']; ?></th>'
						+'</tr>'
					+'</tbody>'
					+'<tbody>';
					
				ptr_order_no = v.order_no;
				running_seq = 0;
				//str_builder_body = '';
			}
			
			str_builder += '<tr>'
					+'<td class="text-center">'+ (++running_seq) +'</td>'
					+'<td>'+ v.item_line_no +'</td>'
					+'<td>'+ v.item_name +'</td>'
					+'<td class="text-right">'+ number_format(v.item_weight, 0, 2) +' <item-unit><?php echo $GLOBALS['_lng375'];?></item-unit></td>'
					//+'<td class="text-right">'+ number_format(v.item_qty, 0, 0) +' <item-unit>'+ v.item_unit +'</item-unit></td>'
				+'</tr>';
			
			if (i + 1 == data.length) {
				str_builder += '</table>';
			}
			/*if ((v.order_no != ptr_order_no) && str_builder_body.length) {
				var btnDelete = '<button class="button btn btn-danger btn-xs" onclick="deletePlanOrder(\''+ data[0].delivery_id +'\',\''+ data[0].order_id +'\')"><i class="fa fa-close"></i> <?php echo $GLOBALS['_lng39'];?></button>';
				
				if (+v.plan_status != 0) {
					btnDelete = '<button class="button btn btn-danger btn-xs" disabled><i class="fa fa-close"></i> <?php echo $GLOBALS['_lng39'];?></button>';
				}
				
				str_builder += '<table class="table table-bordered" style="margin-top:10px;border:0">'
					+'<tbody>'
						+'<tr>'
							+'<td colspan="6" class="" style="border:0">'
								+'<table class="no-border" style="width:auto;table-layout:fixed">'
									+'<tr>'
										+'<td colspan="2"><b><?php echo $GLOBALS['_lng76']; ?></b>'+ v.order_no +'</td>'
										+'<td><b><?php echo $GLOBALS['_lng65']; ?></b>&emsp;</td>'
										+'<td>'+ v.request_date +'</td>'
									+'</tr>'
									+'<tr>'
										+'<td><b><?php echo $GLOBALS['_lng12']; ?></b>&emsp;</td>'
										+'<td style="width:140px">'+ v.contractor_name +'</td>'
										+'<td><b><?php echo $GLOBALS['_lng7']; ?></b>&emsp;</td>'
										+'<td>'+ v.customer_name +'</td>'
									+'</tr>'
									+'<tr>'
										+'<td colspan="3"></td>'
										+'<td><i>'+ v.des_address +'</i></td>'
									+'</tr>'
								+'</table>'
								+'<div class="pull-right">'+ btnDelete +'</div>'
							+'</td>'
						+'</tr>'
					+'</tbody>'
					+'<tbody>'
						+'<tr>'
							+'<th style="width:8%"><?php echo $GLOBALS['_lng149']; ?></th>'
							+'<th style="width:13%"><?php echo $GLOBALS['_lng77']; ?></th>'
							+'<th style="width:30%"><?php echo $GLOBALS['_lng73']; ?></th>'
							+'<th style="width:15%"><?php echo getWeightWord_WithoutKG($GLOBALS['_lng78']); ?></th>'
							//+'<th style="width:15%"><?php echo $GLOBALS['_lng74']; ?></th>'
						+'</tr>'
					+'</tbody>'
					+'<tbody>'+ str_builder_body +'</tbody></table>';
				
				
				
				ptr_order_no = v.order_no;
				running_seq = 0;
				//str_builder = '';
				str_builder_body = '';
			}*/
		});
		
		$('#modal_view_plan .modal-body').append(str_builder);
	}
}

function createVehicleLoadStatusBox() {
    if ($('input:radio[name="rad_plan_type"]:checked').val() == "auto") {
        $('#lbl_vehicle_load_status').html('<div id="lbl_vehicle_load_status"><span>xxx</span></div>').removeClass('visible');
        return false;
    }
    
    var vehicle = $('input:checkbox[name="cb_vehicle_fleet"]:checked');
    if (!vehicle.length) {
        $('#lbl_vehicle_load_status').html('<div id="lbl_vehicle_load_status"><span>xxx</span></div>').removeClass('visible');
        return false;
    }
    
    var _parent; // element node
    var vehiclePlate, vehicleType; // vehicle detail
    var maxLoad = 0, curLoad = 0, curPct = 0, adjLoad = 0, hasItem = false; // summary
    
    ///////////////// Keep => Max. Loading /////////////////
    _parent = vehicle.closest('tr');
    vehiclePlate = _parent.data('vehicle-name');
    vehicleType = _parent.data('vehicle-type');
    maxLoad = Number(_parent.data('max-load'));
    
    ////////////////////// Delivery //////////////////////
    var delivery = $('input:checkbox[name="cb_delivery_fleet"]:checked');
    if (delivery.length) {
        ///////////////// Keep => Current Loading /////////////////
        _parent = delivery.closest('tr');
        curLoad = Number(_parent.data('current-load')) || 0;
    }
    delete delivery;
    
    ////////////////////// Order //////////////////////
    var order = $('input:checkbox[name="cb_order_id"]:checked');
    if (order.length) {
        hasItem = true;
        
        order.each(function(i,v){
            adjLoad += Number($(v).closest('li.item-group.order_no').data('total-item-weight')) || 0;
        });
    }
    delete order;
    
    ////////////////////// Order Item //////////////////////
    var order_item = $('input:checkbox[name="cb_order_id"]:not(:checked)').closest('li.item-group.order_no').find('input:checkbox[name="cb_order_item"]:checked');
    if (order_item.length) {
        hasItem = true;
        
        order_item.each(function(i,v){
            adjLoad += (Number($(v).closest('tr').data('item-weight')) || 0)
                       * (Number($(v).closest('tr').find('input[name="txt_order_item_qty"]').val()) || 0);
        });
    }
    delete order_item;
    
    //////////////////////////////// Build Status ////////////////////////////////
    var str_builder = '<span><b><?php echo $GLOBALS['_lng80'];?>: </b>'+ vehiclePlate +'&emsp;<b><?php echo $GLOBALS['_lng120'];?>: </b>'+ vehicleType;
    
    curPct = (!!maxLoad)? (100 * curLoad) / maxLoad: curLoad;
    str_builder += '&emsp;<b><?php echo getWeightWord_WithoutKG($GLOBALS['_lng78']);?>: </b>'+ number_format(curLoad, 0, 2)
                    +' / '+ number_format(maxLoad, 0, 2)
                    +' <item-unit><?php echo $GLOBALS['_lng375'];?></item-unit> ('+ number_format(curPct, 0, 2) +'%)'
                    +'</span>';
    
    if (hasItem) {
        curLoad += adjLoad;
        curPct = (!!maxLoad)? (100 * curLoad) / maxLoad: curLoad;
        str_builder += '<span><b>&emsp;=>&emsp;<i class="text-orange">'+ number_format(curLoad, 0, 2)
                        +' / '+ number_format(maxLoad, 0, 2)
                        +' <item-unit><?php echo $GLOBALS['_lng375'];?></item-unit> ('+ number_format(curPct, 0, 2) +'%)</i></b></span>';
    }
    
    $('#lbl_vehicle_load_status').html(str_builder).addClass('visible');
}

function createVehicleSuggest(data) {
    if ($('input:radio[name="rad_plan_type"]:checked').val() == "auto") {
        $('#lbl_vehicle_suggest').html('<span>XXX</span>').removeClass('visible');
        return false;
    }
    
    var pickupLabel = '';
    for (var i=0; i < data.length; i++) {
        var pickup_name = $('#delivery_vehicle_type > option[value="'+ data[i].trim() +'"]').text().trim();
        
        if (data[i].trim().length && pickupLabel.indexOf(pickup_name) === -1) {
            pickupLabel += ' / '+ pickup_name;
        }
    }
    pickupLabel = pickupLabel.substring(3, pickupLabel.length);
    
    if (!pickupLabel.length) {
        $('#lbl_vehicle_suggest').html('<span>XXX</span>').removeClass('visible');
        return false;
    }
    
    var str_builder = '<span><b><?php echo $GLOBALS['_lng442'];?>: </b>'+ pickupLabel +'</span>';
    $('#lbl_vehicle_suggest').html(str_builder).addClass('visible');
}

function createRouting(data) {
    var directionPoint = [];
    var optionRouteSeq = '<select name="sl_route_seq" class="">'
            + $.map(data, function(o,i){
                return '<option value="'+ o.delivery_route_id +'">'+ (i+1) +'</option>';
            }).join('') +'</select>';
    
    var str_builder = '';
    
    str_builder = '<tr>';
    str_builder += '<td><center>'+ optionRouteSeq +'</center></td>';
    str_builder += '<td>'+ data['0'].station_name +'</td>';
    str_builder += '<td class="text-center">-</td>';
    str_builder += '<td class="text-center">-</td>';
    str_builder += '<td class="text-center"><input type="text" name="txt_plan_out_time" class="form-control text-center" value="'+ data['0'].plan_out_time +'" style="padding:initial" /></td>';
    str_builder += '</tr>';
    
    directionPoint.push({shipto_seq: data['0'].route_seq, name: data['0'].station_code, latitude: data['0'].station_latitude, longtitude: data['0'].station_longtitude});
//    var pos = new google.maps.LatLng(data['0'].station_latitude, data['0'].station_longtitude);
//    addMarker(map, pos, '1', '1');
//    map.panTo(pos);
//    map.setZoom(12);
    
    var elemTr = $(str_builder);
        elemTr.data({'delivery-id': data['0'].delivery_id, 'delivery-route-id': data['0'].delivery_route_id})
            .find('select[name="sl_route_seq"] option[value="'+ data['0'].delivery_route_id +'"]').prop('selected', true);
        
    $('#tb_edit_route > tbody').html(elemTr);
    bindRouting(elemTr);
    
    var plan_in_time, plan_out_time;
    $.each(data.slice(1, data.length), function (i,v){
        directionPoint.push({shipto_seq: v.route_seq, name: v.station_code, latitude: v.station_latitude, longtitude: v.station_longtitude});
//        pos = new google.maps.LatLng(v.station_latitude, v.station_longtitude);
//        addMarker(map, pos, (i+2).toString(), (i+2).toString());
        
        plan_in_time = v.plan_in_time;
        plan_out_time = v.plan_out_time;
        
        str_builder = '<tr>';
        str_builder += '<td><center>'+ optionRouteSeq +'</center></td>';
        str_builder += '<td>'+ v.station_name +'</td>';
        str_builder += '<td class="text-right">'+ number_format(v.plan_distance, 0, 2) +'&emsp;</td>';
        str_builder += '<td class="text-center"><input type="text" name="txt_plan_in_time" class="form-control text-center" value="'+ plan_in_time +'" style="padding:initial" /></td>';
        str_builder += '<td class="text-center"><input type="text" name="txt_plan_out_time" class="form-control text-center" value="'+ plan_out_time +'" style="padding:initial" /></td>';
        str_builder += '</tr>';
        
        elemTr = $(str_builder);
        elemTr.data({'delivery-id': v.delivery_id, 'delivery-route-id': v.delivery_route_id})
            .find('select[name="sl_route_seq"] option[value="'+ v.delivery_route_id +'"]').prop('selected', true);
    
        $('#tb_edit_route > tbody').append(elemTr);
        bindRouting(elemTr);
    });
    
    drawGoogleRoute(directionPoint, map);
    //$('#tb_edit_route > tbody').find('.btn_route_move_up:first,.btn_route_move_down:last').prop('disabled', true);
}

function bindRouting(parent) {
    parent.find('select[name="sl_route_seq"]').on('change', function(){
        swapRouting($(this));
    });
    
    parent.find('input[name="txt_plan_in_time"]').datetimepicker({
        lang: 'en',
        step: 60,
        format: 'Y-m-d H:i',
        closeOnDateSelect: false,
        closeOnTimeSelect: false,
        closeOnInputClick: false,
        onChangeDateTime: function(ct, $input){
            $input.datetimepicker('hide');
            updateRouting($input);
        }
    });

    parent.find('input[name="txt_plan_out_time"]').datetimepicker({
        lang: 'en',
        step: 60,
        format: 'Y-m-d H:i',
        closeOnDateSelect: false,
        closeOnTimeSelect: false,
        closeOnInputClick: false,
        onChangeDateTime: function(ct, $input){
            $input.datetimepicker('hide');
            updateRouting($input);
        }
    });
	
	$('#modal_edit_route').off('hidden.bs.modal').on('hidden.bs.modal', function () {
		getDeliveryFleetRoute()
	});
}

////////////// Manual LoadingUI //////////////
$.fn.loadingUI = function (mode) {
    var _this = $(this);

    if (mode == 'show') {
        if (_this.find('.loadingUI').length) {
            return;
        }
        
        _this.loadingUI('hide');

        var offset = _this.offset();
        var outWidth = _this.outerWidth(), outHeight = _this.outerHeight()
            , inWidth = _this.innerWidth(), inHeight = _this.innerHeight();
        var margLt = (outWidth - inWidth)/2, padTop = (outHeight - inHeight)/2;
        var setTop = offset.top + padTop, setLeft = offset.left + margLt;

        var cssStyle = 'top:'+ setTop +'px;left:'+ setLeft +'px;width:'+ inWidth +'px;height:'+ inHeight +'px';
        _this.prepend('<div class="loadingUI" style="'+ cssStyle +'"><div class="inner"><span class="fa fa-spinner fa-spin"></span></div></div>');
    } else if (mode == 'hide') {
        _this.find('.loadingUI').remove();
    }
};
</script>