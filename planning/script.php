<?php session_start(); ?>
<script id="scriptControl">   
var $path = "application/pages/planning/service.php";
var hduser = <?php echo $_SESSION["USER_ID"]; ?>;
var hdcontractor = '<?php echo join(',', $_SESSION["USER_CONTRACTOR"]); ?>';

function initial(){
    $(window).on('resize', function(){
        var topBar = 50, header = 41, footBar = 0;//51;
        $('#planUI').css('height', (window.innerHeight - topBar - header - footBar - 27) +'px');
    }).resize();
    
    orderDateType = 'ORD';
    orderDateSt = new Date().yyyymmdd();
    orderDateEn = orderDateSt;
    orderContractorType = 'shipmode';
    orderContractor = '';
    orderSearch = '';
    orderStatus = 'wait';
	orderType = '';
    
    deliveryDate = orderDateEn;
    deliveryContractor = '';
    deliveryVehicleType = '';
    proviceName = '';
    vehicleGroup = '';

    /*orderDateSt = '2019-10-01';
    orderDateEn = '2019-10-04';*/
    
    $('#txt_order_date_start').val(orderDateSt).datepicker({minDate: new Date('2019-12-01')});
    $('#txt_order_date_end').val(orderDateEn).datepicker();
    $('#txt_delivery_date').val(deliveryDate).datepicker();
    //$('#order_date_end').datepicker('setStartDate', new Date(orderDateSt));
    $('#txt_order_date_end').datepicker('setStartDate', new Date(orderDateSt));
    
    $('#txt_departure_time').timepicker({
        defaultTime: '08:00'
        , minTime: '08:00'
        , maxTime: '22:00'
        , timeFormat: 'HH:mm'
        , interval: 60
        , zindex: 1050
    });
    
    $('#sl_order_date_type').on('change', function(){
        getProvince();
    });
    
    $('#txt_order_date_start, #txt_order_date_end').on('change', function(){
        getProvince();
    });
    
//    $('input:checkbox[name="cb_order_status"]').iCheck({
//        checkboxClass: 'icheckbox_flat-blue',
//        radioClass   : 'iradio_flat-blue'
//    });
    
    $('input:radio[name="rad_plan_type"]').on('change', function(){
        if ($('input:radio[name="rad_plan_type"]:checked').val() == "auto") {
            $('#lbl_vehicle_suggest').html('<span>XXX</span>').removeClass('visible');
            $('#lbl_vehicle_load_status').html('<div id="lbl_vehicle_load_status"><span>xxx</span></div>').removeClass('visible');
            $('#route_example_flowchart').empty();
            $('#btn_edit_plan').removeClass('visible');
            $('#tr_delivery_list').remove();
            
            $('#tb_vehicle_list > thead > tr > th:eq(0)').html('<span id="btnCheckAllVehicle" class="fa fa-square-o" onclick="checkAllVehicle(this)" style="font-size: 17px"></span>');
            
            $('#btnAutoPlan').show();
            return false;
        }
        
        $('#tb_vehicle_list #btnCheckAllVehicle').remove();
        
        $('input:checkbox[name="cb_vehicle_fleet"]:checked').prop('checked', false);
        $('#btnAutoPlan').hide();;
    });
    
    var localDb = window.localStorage;
    if (localDb.getItem('cache-pickOrderGroup') === null) {
        pickOrderGroup = ['consignment_no', 'order_id'];
        localDb.setItem('cache-pickOrderGroup', pickOrderGroup.join(','));
        resetOrderGroupList();
    } else {
        pickOrderGroup = localDb.getItem('cache-pickOrderGroup').split(',');
        defaultOrderGroupList();
    }
    
//    getOptionContractor();
//    getOptionShipmode();
//    getOptionVehicleType();
//    getProvince();
//    getVehicleGroup();
    getStartPage();
    
    getVehicleFleet();
    getOrderGroup();
}

function func_st(st){
    $('#txt_order_date_end').datepicker('setStartDate', new Date(st));
    $('#txt_order_date_start').datepicker("hide");
    var en = $('#txt_order_date_end').datepicker().val();
    if(st > en){
        $('#txt_order_date_end').datepicker('setDate', new Date(st));
    }
    $('#txt_order_date_end').datepicker("show");
}

function func_en(en){
    $('#txt_order_date_end').datepicker("hide");
}

function abortRequest(promise) {
    if (promise == null || promise == '' || typeof promise == 'undefined' || !promise) {
        return false;
    }
    promise.abort();
    promise = null;
}

function getStartPage(){
    $.ajax({
        url: $path,
        type: 'post',
        data: function(){
                return {
                    func: 'getStartPage'
                    , orderDateType: $('#sl_order_date_type').val()
                    , orderDateSt: $('#txt_order_date_start').val()
                    , orderDateEn: $('#txt_order_date_end').val()
                };
            }(),
        success: function ( respTxt ) {
            var sp_data = respTxt.split("@@@");
            var data1 = eval( sp_data['0'] );
            var data2 = eval( sp_data['1'] );
            var data3 = eval( sp_data['2'] );
            var data4 = eval( sp_data['3'] );
            var data5 = eval( sp_data['4'] );
            
            createOption('#sl_order_contractor', data1);
            createOption('#sl_delivery_vehicle_contractor', data1);

            createOption('#sl_order_source', data2);

            createOption('#sl_delivery_vehicle_type', data3);

            var option = $.map(data4, function (o) { return '<option value="' + o.prov + '" title="'+ o.prov + ': ' + o.title +'">' + o.prov + '</option>'; }).join('');
            $('#txt_provice').html(option).select2();

            option = $.map(data5, function (o) { return '<option value="' + o.id + '" title="'+ o.title +'">' + o.text + '</option>'; }).join('');
            $('#txt_vehicle_group').html(option).select2();
        }
    });
}

function getOptionContractor() {
    $.ajax({
        url: $path,
        type: 'post',
        data: {func: 'getOptionContractor'},
        success: function ( respTxt ) {
            var response = eval( respTxt );
            
            createOption('#sl_order_contractor', response);
            createOption('#sl_delivery_vehicle_contractor', response);
        }
    });
}

function getOptionShipmode() {
    $.ajax({
        url: $path,
        type: 'post',
        data: function(){
                return {
                    func: 'getOptionShipmode'
                    , orderDateSt: orderDateSt, orderDateEn: orderDateEn
                }
            }(),
        success: function ( respTxt ) {
            var response = eval( respTxt );
            
            createOption('#sl_order_source', response);
        }
    });
}

function getOptionVehicleType() {
    $.ajax({
        url: $path,
        type: 'post',
        data: {func: 'getOptionVehicleType'},
        success: function ( respTxt ) {
            var response = eval( respTxt );
            
            createOption('#sl_delivery_vehicle_type', response);
        }
    });
}

function getOrderGroup() {
    window.clearTimeout(delayGetDeliveryFleetRoute);
    abortRequest(ajaxReqDeliveryFleetRoute);
    $('#route_example > .planUI-box').loadingUI('hide');
    
    abortRequest(ajaxReqOrderGroup);
    ////////////////////////////////
    
//    $('#order_list > .planUI-box').loadingUI('show');
    
    ajaxReqOrderGroup = $.ajax({
        url: $path,
        type : 'post',
        data: function(){
                return {
                    func: 'getOrderGroup', hduser: hduser, hdcontractor: hdcontractor
                    , orderDateType: orderDateType
                    , orderDateSt: orderDateSt, orderDateEn: orderDateEn
                    , orderSource: orderSource, orderContractor: orderContractor
                    , orderSearch: orderSearch, orderStatus: orderStatus
					, orderType: orderType
                    , pickOrderGroup: pickOrderGroup.join(','), proviceName: proviceName
                };
            }(),
//        async: false,
        beforeSend: function() {
            $('#order_list > .planUI-box').loadingUI('show');
        },
        success: function ( respTxt ) {
            var response = eval( respTxt );
            
            if (!response.length) {
                $('#order_list .planUI-item-list').empty();
                $('#route_example_flowchart').empty();
                $('#btn_edit_plan').removeClass('visible');
                return;
            }
            
            $('#order_list .planUI-item-list').empty();
            $('#route_example_flowchart').empty();
            $('#btn_edit_plan').removeClass('visible');

            try {
                createOrderGroup($('#order_list .planUI-item-list'), 0, response);
            } catch(ex) {
                console.log(ex);
            }
            /////////////////////////////////////////////////////////////////
            createVehicleLoadStatusBox();
            getCustomerConfigPickup();
        },
        complete: function(){
            $('#order_list > .planUI-box').loadingUI('hide');
        }
    });
}

function getOrderDetail(parent, orderNo) {
    $('#order_list > .planUI-box').loadingUI('show');
    
    $.ajax({
        url: $path,
        type: 'post',
        data: {func: 'getOrderDetail', orderNo: orderNo},
        //async: false,
        success: function ( respTxt ) {
            var response = eval( respTxt );
            
            if (!response.length) {
                //
            }
            
            createOrderDetail(parent, response);
        },
        complete: function(){
            $('#order_list > .planUI-box').loadingUI('hide');
        }
    });
}

function getVehicleFleet() {
    abortRequest(ajaxReqVehicleFleet);
    //////////////////////////////////
    
    //$('#vehicle_list > .planUI-box').loadingUI('show');
    
    ajaxReqVehicleFleet = $.ajax({
        url: $path,
        type: 'post',
        data: function(){
                return {
                    func: 'getVehicleFleet', hduser: hduser, hdcontractor: hdcontractor
                    , deliveryDate: deliveryDate
                    , deliveryContractor: deliveryContractor, deliveryVehicleType: deliveryVehicleType
                    , vehicleGroup: vehicleGroup
                };
            }(),
//        async: false,
        beforeSend: function() {
            $('#vehicle_list > .planUI-box').loadingUI('show');
        },
        success: function ( respTxt ) {
            var response = eval( respTxt );
            
            if (!response.length) {
                $('#vehicle_list .planUI-item-list').empty();
                $('#route_example_flowchart').empty();
                $('#btn_edit_plan').removeClass('visible');
                return false;
            }
            
            $('#vehicle_list .planUI-item-list').empty();
            $('#route_example_flowchart').empty();
            $('#btn_edit_plan').removeClass('visible');
            createVehicleFleet($('#vehicle_list .planUI-item-list'), response);

            /////////////////////////////////////////////////////////////////
            createVehicleLoadStatusBox();
            getCustomerConfigPickup();
        },
        complete: function(){
            $('#vehicle_list > .planUI-box').loadingUI('hide');
        }
    });
}

function reloadOrderGroup() {
    orderDateType = $('#sl_order_date_type').val();
    orderDateSt = $('#txt_order_date_start').val();
    orderDateEn = $('#txt_order_date_end').val();
    orderSource = $('#sl_order_source').val();
    orderContractor = $('#sl_order_contractor').val();
    orderSearch = $('#txt_order_search').val().trim();
    orderStatus = $('input:checkbox[name="cb_order_status"]:checked').toArray().map(function(o){ return o.value; }).join(',');
	orderType = $('#sl_order_type').val();
    //proviceName = $.map($('#txt_provice > select.select2-selection__choice').toArray(), function (o){return o.title;}).join(',');
    proviceName = $.map($($('#txt_provice')).select2('data'), function (o){return o.text;}).join(',');
    
    getVehicleGroup();
    getOrderGroup();
}

function reloadVehicleFleet() {
    deliveryDate = $('#txt_delivery_date').val();
    deliveryContractor = $('#sl_delivery_vehicle_contractor').val();
    deliveryVehicleType = $('#sl_delivery_vehicle_type').val();
    vehicleGroup = $.map($($('#txt_vehicle_group')).select2('data'), function (o){return o.text;}).join(',');
    getVehicleFleet();
}

function getDeliveryFleet(parent, vehicleId) {
    abortRequest(ajaxReqDeliveryFleet);
    //////////////////////////////////
    
    //$('#vehicle_list > .planUI-box').loadingUI('show');
    
    ajaxReqDeliveryFleet = $.ajax({
        url: $path,
        type: 'post',
        data: function(){
                return {func: 'getDeliveryFleet', deliveryDate: deliveryDate, vehicleId: vehicleId};
            }(),
        //async: false,
        beforeSend: function() {
            $('#vehicle_list > .planUI-box').loadingUI('show');
        },
        success: function ( respTxt ) {
            var response = eval( respTxt );
            
            if (!response.length) {
                parent.find('td:eq(5)').html('-');
                parent.find('td:eq(6)').html('-');
                
                $('#tr_delivery_list').remove();
                $('#route_example_flowchart').empty();
                $('#btn_edit_plan').removeClass('visible');
                
                // Call Delivery Routing Simulation
                getDeliveryFleetRoute();
                return;
            }
            
            $('#tr_delivery_list').remove();
            $('#route_example_flowchart').empty();
            $('#btn_edit_plan').removeClass('visible');
            createDeliveryFleet(parent, response);
            getDeliveryFleetRoute(response['0'].delivery_id);
        },
        complete: function(){
            $('#vehicle_list > .planUI-box').loadingUI('hide');
        }
    });
}

var delayGetDeliveryFleetRoute = null;
function getDeliveryFleetRoute(devId) {
    abortRequest(ajaxReqDeliveryFleetRoute);
    ////////////////////////////////////////
    
    $('#route_example > .planUI-box').loadingUI('show');
    
    function ajaxQuery() {
        ///////////////////////// Keep Order /////////////////////////
        var dataOrder = '';
        $('#order_list .planUI-item-list .item-group.order_no input:checkbox[name="cb_order_id"]:checked').each(function(){
            var _checkboxOrder = $(this).val();
            dataOrder += ',O#'+ _checkboxOrder +'#1';
        });
        dataOrder = dataOrder.substring(1, dataOrder.length);
        ///////////////////////////////////////////////////////////////

        var deliveryId = devId || $('input:checkbox[name="cb_delivery_fleet"]:checked').val() || '';
        if (!deliveryId && !dataOrder.length) {
            $('#route_example_flowchart').empty();
            $('#route_example > .planUI-box').loadingUI('hide');
            return;
        }

        ajaxReqDeliveryFleetRoute = $.ajax({
            url: $path,
            type: 'post',
            data: function(){
                    return {func: 'getDeliveryFleetRoute', deliveryId: deliveryId, dataOrder: dataOrder};
                }(),
    //        async: false,
            success: function ( respTxt ) {
                var response = eval( respTxt );

                if (!response.length) {
                    $('#route_example_flowchart').empty();
                    return false;
                }

                $('#route_example_flowchart').empty();
                createDeliveryFleetRoute(response);
            },
            complete: function(){
                $('#route_example > .planUI-box').loadingUI('hide');
            }
        });
    }
    
    window.clearTimeout(delayGetDeliveryFleetRoute);
    
    delayGetDeliveryFleetRoute = window.setTimeout(function(){
        window.clearTimeout(delayGetDeliveryFleetRoute);
        ajaxQuery();
    }, 1500);
}

function viewPlan(deliveryId) {
    $.ajax({
        url: $path,
        type: 'post',
        data: {func: 'viewPlan', deliveryId: deliveryId},
        async: false,
        success: function ( respTxt ) {
            var response = eval( respTxt );
            
            if (response.length) {
                createViewPlan(response);
            } else {
                $('#modal_view_plan .modal-body').empty();
                $('#modal_view_plan').modal('hide');
            }
        }
    });
    
    $.ajax({
        url: $path,
        type: 'post',
        data: {func: 'viewPlanOrder', deliveryId: deliveryId},
        async: false,
        success: function ( respTxt ) {
            var response = eval( respTxt );
            
            if ( response.length ) {
                createViewPlanOrder(response);
            } else {
                $('#modal_view_plan .modal-body').empty();
                $('#modal_view_plan').modal('hide');
            }
        }
    });
    $('#modal_view_plan').modal('show');
}

function validDate(dateStr) {
    if (!( new RegExp("^(20\\d{2})-([0]\\d|1[0-2])-([0-2]\\d|3[0-1])$") ).test(dateStr)) {
        return false;
    }
    
    /// Other check
    
    return true;
}

function validTime(timeStr) {
    return ( new RegExp("^([0-1]\\d{1}|2[0-3]):([0-5]\\d)$") ).test(timeStr);
}

function validDateTime(DateTimeStr) {
    var splitDateTime = DateTimeStr.split(/ /);
    var dateStr = splitDateTime['0'];
    var timeStr = splitDateTime['1'];

    return validDate(dateStr) && validTime(timeStr);
}

function checkPlan() {
    if (!$('input:checkbox[name="cb_vehicle_fleet"]:checked').length) {
        alert('<?php echo $GLOBALS['_lng144']; ?>!');
        return false;
    }
    
    if (!$('input:checkbox[name="cb_order_id"]:checked').length && !$('input:checkbox[name="cb_order_item"]:checked').length) {
        alert('<?php echo $GLOBALS['_lng416']; ?>!');
        return false;
    }
    
    if(!$('input:checkbox[name="cb_delivery_fleet"]:checked').length) {
        $('#txt_departure_time').val('08:00');
        $('#modal_add_delivery_time').modal('show');
        return false;
    }
    
    addPlan();
}

function addPlan() {
    var conf = confirm('<?php echo $GLOBALS['_lng145']; ?>');
    if (!conf) {
        return false;
    }
    
    var vehicleId = $('input:checkbox[name="cb_vehicle_fleet"]:checked').val();
    var deliveryId = $('input:checkbox[name="cb_delivery_fleet"]:checked').val() || 0;
    var departureTime = $('#txt_departure_time').val();
    
    if (!+deliveryId) {
        if (!validDate(deliveryDate) && !validTime(departureTime)) {
            alert('Invalid Date Or Time!');
            return false;
        }
    }
    
    var dataOrder = '';
    $('#order_list .planUI-item-list .item-group.order_no input:checkbox[name="cb_order_id"]:checked').each(function(){
        var _checkboxOrder = $(this).val();
        dataOrder += ',O#'+ _checkboxOrder +'#1';
    });
    dataOrder = dataOrder.substring(1, dataOrder.length);
    
    $.ajax({
        url: $path,
        type: 'post',
        data: function(){
                return {
                    func: 'addPlan', hduser: hduser, vehicleId: vehicleId, deliveryDate: deliveryDate
                    , departureTime: departureTime, dataOrder: dataOrder, deliveryId: deliveryId
                };
            }(),
        async: false,
        beforeSend: function(){
            console.log(555555);
            BlockPage(0);
            $('#modal_add_delivery_time').modal('hide');
        },
        success: function ( respTxt ) {
            var response = eval( respTxt )['0'];

            if ( response.status === 'Y' ) {
                alert( response.message );
                
                $('input:checkbox[name="cb_vehicle_fleet"]:checked').trigger('change');
                getOrderGroup();
                /*
                $('li.item-group.order_no.collapsed').removeClass('collapsed').find('> .item-group-label').trigger('click');
                $('input:checkbox[name="cb_order_id"]:checked').iCheck('uncheck').iCheck('disable').closest('.item-group.order_no').addClass('planned');
                $('input:checkbox[name="cb_order_item"]:checked').iCheck('uncheck').iCheck('disable');
                $('input:checkbox[name="cb_vehicle_fleet"]:checked').trigger('ifChanged');*/
                
                //$('#modal_add_delivery_time').modal('hide');
            } else {
                alert( response.message );
            }
        },
        complete: function() {
            BlockPage(1);
        }
    });
}

function deletePlan(deliveryId) {
    var conf = confirm('<?php echo $GLOBALS['_lng466']; ?>');
    if (!conf) {
        return false;
    }
    
    $.ajax({
        url: $path,
        type: 'post',
        data: {func: 'deletePlan', hduser: hduser, deliveryId: deliveryId},
        async: false,
        beforeSend: function(){
            BlockPage(0);
        },
        success: function ( respTxt ) {
            var response = eval( respTxt )['0'];
            
            if ( response.status === 'Y' ) {
                alert( response.message );
                
                //$('.item-group.order_no.collapsed').removeClass('collapsed').find('> .item-group-label').trigger('click');
                $('input:checkbox[name="cb_vehicle_fleet"]:checked').trigger('change');
                getOrderGroup();
                
                /////////////////////////////////////////////////////////////////
                //createVehicleLoadStatusBox();
                //getCustomerConfigPickup();
            } else {
                alert( response.message );
            }
        },
        complete: function() {
            BlockPage(1);
        }
    });
}

function deletePlanOrder(deliveryId, orderId) {
    var conf = confirm('<?php echo $GLOBALS['_lng146']; ?>');
    if (!conf) {
        return false;
    }
    
    $.ajax({
        url: $path,
        type: 'post',
        data: {func: 'deletePlanOrder', hduser: hduser, deliveryId: deliveryId, orderId: orderId},
        async: false,
        beforeSend: function(){
            BlockPage(0);
        },
        success: function ( respTxt ) {
            var response = eval( respTxt )['0'];
            
            if ( response.status === 'Y' ) {
                alert( response.message );
                
                if ($('#modal_view_plan').hasClass('in')) {
                    viewPlan(deliveryId);
                }
                
                $('input:checkbox[name="cb_vehicle_fleet"]:checked').trigger('change');
                getOrderGroup();
            } else {
                alert( response.message );
            }
        },
        complete: function() {
            BlockPage(1);
        }
    });
}

function getCustomerConfigPickup() {
    if ($('input:radio[name="rad_plan_type"]:checked').val() == "auto") {
        $('#lbl_vehicle_suggest').html('<span>XXX</span>').removeClass('visible');
        return false;
    }
    
    var pickupList = [];
    
    $('input:checkbox[name="cb_order_id"]:checked,input:checkbox[name="cb_order_item"]:checked').each(function(){
        var config_pickup = $(this).closest('.item-group.order_no').data('config-pickup').trim();
        pickupList = pickupList.concat(config_pickup.split(','));
    });
    
    if (!pickupList.length) {
        $('#lbl_vehicle_suggest').html('<span>XXX</span>').removeClass('visible');
        return false;
    }
    
    createVehicleSuggest(pickupList);
}

function getRouting(deliveryId) {
    //var deliveryId = $('input:checkbox[name="cb_delivery_fleet"]:checked').val();
    if (!deliveryId) {
        deliveryId = $('input:checkbox[name="cb_delivery_fleet"]:checked').val();
        //return false;
    }
    
    $('#tb_edit_route > tbody').empty();
    
    $('#modal_edit_route').modal('show');
    //$('#modal_edit_route .modal-body').loadingUI('show');
    
    $.ajax({
        url: $path,
        type: 'post',
        data: function(){
                return {
                    func: 'getDeliveryFleetRoute', deliveryId: deliveryId
                };
            }(),
        async: false,
        success: function ( respTxt ) {
            var response = eval( respTxt );
            
            deleteMarkers();
            
            //window.setTimeout(function(){
                createRouting(response);
                //$('#modal_edit_route .modal-body').loadingUI('hide');
            //}, delayAfterAjaxReq);
        }
    });
}

function swapRouting(elem) {
    var deliveryId = $('input:checkbox[name="cb_delivery_fleet"]:checked').val();
    var deliveryRouteId = +elem.closest('tr').data('delivery-route-id');
    var swapDeliveryRouteId = +elem.val();
    
    if (!deliveryId) {
        return;
    }
    
    $.ajax({
        url: $path,
        type: 'post',
        data: function(){
                return {
                    func: 'swapRouting', hduser: hduser
                    , deliveryId: deliveryId, deliveryRouteId: deliveryRouteId, swapDeliveryRouteId: swapDeliveryRouteId
                };
            }(),
        //async: false,
        success: function ( respTxt ) {
            var response = eval( respTxt )['0'];
            
            if ( response.status === 'Y' ) {
                getRouting();
                //getDeliveryFleetRoute();
            } else {
                alert( response.message );
            }
        }
    });
}

function updateRouting(elem) {
    var deliveryId = $('input:checkbox[name="cb_delivery_fleet"]:checked').val();
    var elemTr = elem.closest('tr');
    var deliveryRouteId = +elemTr.data('delivery-route-id');
    var planInTime = elemTr.find('input[name="txt_plan_in_time"]').val() || '';
    var planOutTime = elemTr.find('input[name="txt_plan_out_time"]').val();
    
    $.ajax({
        url: $path,
        type: 'post',
        data: function(){
                return {
                    func: 'updateRouting', hduser: hduser
                    , deliveryId: deliveryId, deliveryRouteId: deliveryRouteId
                    , planInTime: planInTime, planOutTime: planOutTime
                };
            }(),
        async: false,
        success: function ( respTxt ) {
            var response = eval( respTxt )['0'];
            
            if ( response.status === 'Y' ) {                
                getRouting();
                //getDeliveryFleetRoute();
            } else {
                getRouting();
                //getDeliveryFleetRoute();
            }
        }
    });
}

function resetRouting() {
    var deliveryId = $('input:checkbox[name="cb_delivery_fleet"]:checked').val();
    
    $.ajax({
        url: $path,
        type: 'post',
        data: function(){
                return {
                    func: 'resetRouting', hduser: hduser, deliveryId: deliveryId
                };
            }(),
        async: false,
        success: function ( respTxt ) {
            var response = eval( respTxt )['0'];
            
            if ( response.status === 'Y' ) {                
                getRouting();
                //getDeliveryFleetRoute();
            } else {
                getRouting();
                //getDeliveryFleetRoute();
            }
        }
    });
}

function reRouting() {
    var deliveryId = $('input:checkbox[name="cb_delivery_fleet"]:checked').val();
    
    $.ajax({
        url: $path,
        type: 'post',
        data: function(){
                return {
                    func: 'reRouting', hduser: hduser, deliveryId: deliveryId
                };
            }(),
        async: false,
        success: function ( respTxt ) {
            var response = eval( respTxt )['0'];
            
            if ( response.status === 'Y' ) {                
                getRouting();
                //getDeliveryFleetRoute();
            } else {
                getRouting();
                //getDeliveryFleetRoute();
            }
        }
    });
}


function reRoutingAPI() {
    var deliveryId = $('input:checkbox[name="cb_delivery_fleet"]:checked').val();
    
    $.ajax({
        url: $path,
        type: 'post',
        data: function(){
                return {
                    func: 'reRoutingAPI', hduser: hduser, deliveryId: deliveryId
                };
            }(),
        async: false,
        success: function ( respTxt ) {
            var response = eval( respTxt )['0'];
            
            if ( response.status === 'Y' ) {                
                getRouting();
                //getDeliveryFleetRoute();
            } else {
                getRouting();
                //getDeliveryFleetRoute();
            }
        }
    });
}


function initMap() {
    var position = new google.maps.LatLng(13.673335518350793,100.60689544677734);
    var options = {
        zoom: 8,
        center: position,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    }
    
    map = new google.maps.Map(document.getElementById('deliveryMap'), options);
}

function addMarker(map, location, title, label) {
    markers.push(
        new google.maps.Marker({
            position: location
            , draggable: true
            , visible: true
            , animation: false
            , label: {title: title, text:label, color: '#fff'}
        })
    );
    
    markers[markers.length - 1].setMap(map);
}

// Sets the map on all markers in the array.
function setMapOnAll(map) {
    for (var i = 0; i < markers.length; i++) {
        markers[i].setMap(map);
    }
}

// Removes the markers from the map, but keeps them in the array.
function clearMarkers() {
    setMapOnAll(null);
    if(directionsDisplay !=null){
        directionsDisplay.setMap(null);	
    }
}

// Shows any markers currently in the array.
function showMarkers(map) {
    setMapOnAll(map);
}

// Deletes all markers in the array by removing references to them.
function deleteMarkers() {
    clearMarkers();
    markers = [];
    directionsDisplay=null;
    directionsService=null;
}

function drawGoogleRoute(data ,map_){
    
    var oData = eval(data);
    directionsService = new google.maps.DirectionsService;
    directionsDisplay = new google.maps.DirectionsRenderer;
    title_drop = [];
    
    var origins  = null, destinations = null, waypoints = new Array(), groupMarkerFromWaypoints = [];
    if(oData.length){
        origins = new google.maps.LatLng(Number(oData['0'].latitude), Number(oData['0'].latitude));

        if(oData.length > 1){
            destinations = new google.maps.LatLng(Number(oData[oData.length-1].latitude), Number(oData[oData.length-1].latitude));
        }

        for(var cntPlace = 0; cntPlace < oData.length; cntPlace++){
            var aWaypoint = {}, aWaypointLat, aWaypointLon;
            aWaypointLat = Number(oData[cntPlace].latitude);
            aWaypointLon = Number(oData[cntPlace].longtitude);
            aWaypoint.location = new google.maps.LatLng(aWaypointLat, aWaypointLon);
            aWaypoint.stopover = true;
            waypoints.push(aWaypoint);

            var findExistPlace = groupMarkerFromWaypoints.filter(function (o){
                    return o.latitude == aWaypointLat && o.longtitude == aWaypointLon;
            });

            if(!findExistPlace.length){
                var GroupWaypointTitle = oData.filter(function (o){
                        return Number(o.latitude) === aWaypointLat && Number(oData[cntPlace].longtitude) === aWaypointLon;
                }).map(function (o){ return o.shipto_seq; }).sort().join(',');
                
                var GroupStationName = oData.filter(function (o){
                        return Number(o.latitude) === aWaypointLat && Number(oData[cntPlace].longtitude) === aWaypointLon;
                }).map(function (o){ return o.shipto_seq + '. ' +o.name; }).sort().join('<br>');
                
                title_drop.push({name:GroupStationName});

                var WaypointMarker = new google.maps.Marker({
                        position: aWaypoint.location
                        , map: map_
                        , draggable: false
                        , animation: false
                        , label: {text:GroupWaypointTitle, color:'#fff', fontSize:"14px"}
                                    //, icon: path+ 'img/truck_32.png'
                                    , title: GroupStationName
                                });
                markers.push(WaypointMarker);
                groupMarkerFromWaypoints.push({latitude: aWaypointLat, longtitude: aWaypointLon});
                

                delete GroupWaypointTitle;
                delete findExistPlace;
            }
        }

        directionsService.route({
                origin: waypoints['0'].location
                , destination: waypoints[waypoints.length-1].location
                , waypoints: waypoints
                , optimizeWaypoints: true
                , travelMode: 'DRIVING'
                , avoidTolls: true
        }
        , function(response, status){
            if (status === 'OK'){
                directionsDisplay.setDirections(response);
            }else{
                window.alert('Directions request failed due to ' + status);
            }
        });

        directionsDisplay.setMap(map_);
        directionsDisplay.setOptions({suppressMarkers: true});
    }
    
}

function getProvince(){
    $.ajax({
        url: $path,
        type: 'post',
        data: function(){
                return {
                    func: 'getProvince'
                    , orderDateType: $('#sl_order_date_type').val()
                    , orderDateSt: $('#txt_order_date_start').val()
                    , orderDateEn: $('#txt_order_date_end').val()
                };
            }(),
        success: function ( respTxt ) {
            var data = eval( respTxt );
            var option = $.map(data, function (o) { return '<option value="' + o.prov + '" title="'+ o.prov + ': ' + o.title +'">' + o.prov + '</option>'; }).join('');
            $('#txt_provice').html(option).select2();
        }
    });
}

function getVehicleGroup(){
    $.ajax({
        url: $path,
        type: 'post',
        data: function(){
                return {
                    func: 'getVehicleGroup'
                    , proviceName: proviceName
                };
            }(),
        success: function ( respTxt ) {
            var data = eval( respTxt );
            var option = $.map(data, function (o) { return '<option value="' + o.id + '" title="'+ o.title +'">' + o.text + '</option>'; }).join('');
            $('#txt_vehicle_group').html(option).select2();
        }
    });
}

function checkAllVehicle(elem) {
    if (!$(elem).hasClass('checked')) {
        $(elem).addClass('fa-check-square-o checked').removeClass('fa-square-o');
        $('input:checkbox[name="cb_vehicle_fleet"]').prop('checked', true);
        return;
    }
    
    $(elem).addClass('fa-square-o').removeClass('fa-check-square-o checked');
    $('input:checkbox[name="cb_vehicle_fleet"]').prop('checked', false);
}

function checkAllOrder(elem) {
    if (!$(elem).hasClass('checked')) {
        $(elem).addClass('checked').find('.fa').addClass('fa-check-square-o checked').removeClass('fa-square-o');
        $('#order_list input:checkbox.iCheck:not(:disabled)').prop('checked', true);
        $('input:checkbox[name="cb_order_id"]:checked:not(:disabled):last').trigger('change');
        return;
    }
    
    $(elem).removeClass('checked').find('.fa').addClass('fa-square-o').removeClass('fa-check-square-o');
    $('#order_list input:checkbox.iCheck:not(:disabled)').prop('checked', false);
    $('input:checkbox[name="cb_order_id"]:checked:not(:disabled):last').trigger('change');
}

var autoPlanWindow = null;
function autoPlan() {
    if (autoPlanWindow) {
        autoPlanWindow.close();
    }
    
    var w = window.outerWidth * 0.96;
    var h = window.outerHeight * 0.9;
    var mart = window.outerWidth * 0.015;//(window.outerHeight - h) / 2;
    var marl = (window.outerWidth - w) / 2;
    var url = 'application/pages/planning_auto/content.php';
    var options = ',width='+ w.toString() +'px,height='+ h.toString() +'px,top='+ mart.toString() +'px,left='+ marl.toString() +'px';
    autoPlanWindow = window.open(url, '_blank', 'titlebar=no,menubar=no,toolbar=no,resizable=yes,status=no,location=no'+ options);
    
    $(window).off('unload').on('unload', function(){
        autoPlanWindow.close();
    });
}

document.getElementById('scriptControl').remove();
document.getElementById('scriptPlanUI').remove();
</script>