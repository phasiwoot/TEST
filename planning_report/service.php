<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Bangkok');
error_reporting(E_ERROR | E_PARSE);
require_once("../../../config/db_connection.php");
require_once("../../../resources/function/php/sqlsrv_connect.php");
require_once("../../../config/languages/language.php");
require_once("delivery_write_log.php");
    
initial();
function initial(){
    if(isset($_REQUEST['func'])){
        $func = $_REQUEST['func'];
        if(function_exists($func)){
            $func();
        }else{
            echo json_encode([['status'=>'N', 'message'=>$GLOBALS['_lng104'] ."!"]]);
        }
    }
        
}

function getPlan() {
    $draw = intval($_POST['draw']);
    $start = $_POST['start'];
    $length = $_POST['length'];
    $search = trim($_POST['search']);
    $sort = $_POST['order'][0]['column']+1;
    $sortType = $_POST['order'][0][dir];
    
    $hduser = $_POST['hduser'];
    $hdcontractor = $_POST['hdcontractor'];
    //$dateType = $_POST['dateType'];
    $dateStart = $_POST['dateStart'];
    $dateEnd = $_POST['dateEnd'];
    $status = $_POST['status'];
    
    $cond = "";
    $dateStart .= " 00:00";
    $dateEnd .= " 23:59:59";

    $cond = " and (cast(tdp.delivery_date as datetime) between cast('$dateStart' as datetime) and cast('$dateEnd' as datetime)) ";
    
    if (empty($search) || !empty($search)) {
        if (!empty($status)) {
            $status = explode(',', $status);
            foreach ($status as $key => $value) {
                if ($value == 'verify') {
                    $cond .= " and tdp.plan_status = 0 ";
                } 
            }
        }
        
    } if(!empty($search)){
        $cond .= " and (tdp.delivery_no like N'%$search%' 
                        or tdp.contractor_name like N'%$search%'
                        or tdp.vehicle_type_name like N'%$search%'
                        or tdp.vehicle_name like N'%$search%'
                        or tdp.driver_name like N'%$search%'
                        or exists(select toh.order_no
                                    from [dbo].[transaction_delivery_item] tdi
                                    inner join [dbo].[transaction_order] toh on toh.order_id = tdi.order_id
                                    where tdi.trash = 0 and toh.trash = 0 and tdi.delivery_id = tdp.delivery_id and toh.order_no like N'%$search%')
                        or exists(select toh.consignment_no
                                    from [dbo].[transaction_delivery_item] tdi
                                    inner join [dbo].[transaction_order] toh on toh.order_id = tdi.order_id
                                    where tdi.trash = 0 and toh.trash = 0 and tdi.delivery_id = tdp.delivery_id and toh.consignment_no like N'%$search%')
                        or exists(select toh.customer_name
                                    from [dbo].[transaction_delivery_item] tdi
                                    inner join [dbo].[transaction_order] toh on toh.order_id = tdi.order_id
                                    where tdi.trash = 0 and toh.trash = 0 and tdi.delivery_id = tdp.delivery_id and toh.customer_name like N'%$search%')
                        or exists(select tdi.item_name
                                    from [dbo].[transaction_delivery_item] tdi
                                    where tdi.trash = 0 and tdi.delivery_id = tdp.delivery_id and tdi.item_name like N'%$search%')
                ) ";
    }
    
    if ($hduser != '1') {
        $cond .= " and tdp.contractor_id in ($hdcontractor) ";
    }
    
    $cmd = "select row_number() over (order by tdp.delivery_date) as row_number
                , tdp.delivery_no
                , tdp.delivery_date
                , tdp.contractor_name
                , isnull(tdp.driver_name, '') as driver_name
                , tdp.vehicle_type_name
                , tdp.vehicle_name
                , tdp.plan_status
                , tdp.delivery_id
                , tdp.vehicle_id
            from [transaction_delivery] tdp
            where tdp.trash = 0 $cond
            group by tdp.delivery_id, tdp.delivery_no, tdp.delivery_date, tdp.contractor_name, tdp.plan_status
                , tdp.vehicle_id, tdp.vehicle_type_name, tdp.vehicle_name, tdp.driver_name
            order by $sort $sortType ";
			
				if($length != -1){
				$cmd .= " offset $start rows fetch next $length rows only";
			}
    
    $result1 = dbConnection( $cmd );
    
    foreach($result1 as $irs11=>$rs1) {
        $delivery_id = $rs1['delivery_id'];
        
        $cmd = "select x1.*
                        , cast(x1.line_no as nvarchar(20)) + ' / ' +  cast(isnull((select top 1 max(fb.total_box) from [dbo].[master_final_way_bill] fb
                                    inner join [dbo].[transaction_order] toh on toh.consignment_no = fb.consignment_no
                                    where toh.order_id = x1.order_id and toh.trash = 0), 0) as nvarchar(20)) as item_line_no
                from(
                    select (select toh.order_no from [dbo].[transaction_order] toh where toh.trash = 0 and toh.order_id = tdi.order_id) as order_no
                        , tdi.item_line_no as line_no
                        , (select toh.consignment_no from [dbo].[transaction_order] toh where toh.trash = 0 and toh.order_id = tdi.order_id) as consignment_no
                        , isnull(tdi.item_type_name, '') as item_type_name
                        , isnull(tdi.item_unit_name, '') as item_unit_name
                        , isnull(tdi.item_name, '') as item_name
                        , isnull(tdi.item_qty, 0) as item_qty
                        , isnull((tdi.item_weight * tdi.item_qty), 0) as item_weight
                        , isnull(tdi.item_height, 0) as item_height
                        , isnull(tdi.item_length, 0) as item_length
                        , isnull(tdi.item_width, 0) as item_width
                        , isnull((select top 1  toh.customer_name from [dbo].[transaction_order] toh where toh.trash = 0 and toh.order_id = tdi.order_id), '') as customer_name
                        , isnull(convert(nvarchar(10),(select toh.request_date_end from [dbo].[transaction_order] toh where toh.trash = 0 and toh.order_id = tdi.order_id), 21), '') as request_date
                        , (case when td.plan_status <> 0 then isnull(convert(nvarchar(16), td.modified_date, 21), '') else '' end ) as modified_date
                        , (case when td.plan_status <> 0 then isnull((select su.name from [dbo].[sys_user] su where su.id = td.modified_by), '') else '' end ) as modified_by
                        , tdi.order_id
                from [dbo].[transaction_delivery_item] tdi
                inner join [transaction_delivery] td on td.delivery_id = tdi.delivery_id
                where tdi.trash = 0 and td.trash = 0 and tdi.delivery_id = $delivery_id
                ) x1
                order by x1.consignment_no, x1.order_no, x1.line_no
                ";
        $result2 = dbConnection( $cmd );
        
        $result1[$irs11]['item_list'] = $result2;
    }
    
    $cmd = "select count(distinct tdp.delivery_no) as total_row 
            from [transaction_delivery] tdp
            where tdp.trash = 0 $cond ";
    $result3 = dbConnection( $cmd );
    
    $recordsTotal = count($result1);
    $recordsFiltered = intval($result3['0']['total_row']);
    
    $json_data = array(
        'draw' => $draw,
        'data' => $result1,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered
    );

    echo json_encode($json_data);
}

function getPlan2() {
    $hduser = $_POST['hduser'];
    $hdcontractor = $_POST['hdcontractor'];
    $dateStart = $_POST['dateStart'];
    $dateEnd = $_POST['dateEnd'];
    
    $dateStart .= " 00:00";
    $dateEnd .= " 23:59:59";
    
    $cond = " (cast(tdp.delivery_date as datetime) between cast('$dateStart' as datetime) and cast('$dateEnd' as datetime)) ";
    if ($hduser != '1') {
        $cond .= " and tdp.contractor_id in ($hdcontractor) ";
    }
    
    $cmd = "select concat(tdp.delivery_no, tdp.contractor_name) as keyId
            , tdp.delivery_no, tdp.delivery_date, tdp.round_seq, tdp.contractor_name
            , tdp.vehicle_type_name, tdp.vehicle_name, max(tdp.vehicle_configured) as vehicle_configured
            , isnull(tdp.blackbox_no, '-') as blackbox_no, isnull(tdp.driver_name, '-') as driver_name
            , sum(isnull(round(tdp.item_weight, 0), 0)*item_qty) as item_weight
            , [dbo].[GET_TRUCK_PERCENT_LOAD](tdp.vehicle_id, sum(tdp.item_weight*item_qty), 'weight') as load_weight
            , min(tdp.create_date) as create_date
            , count(distinct tdp.order_no) as count_order_planned
            , max(tdp.plan_status) as plan_status
        from [transaction_delivery_plan] tdp
        where $cond
        group by tdp.delivery_no, tdp.delivery_date, tdp.round_seq, tdp.contractor_name
            , tdp.vehicle_id, tdp.vehicle_type_name, tdp.vehicle_name, tdp.blackbox_no, tdp.driver_name
        order by tdp.delivery_date, tdp.round_seq";
    $result1 = dbConnection( $cmd );
    
    $cmd = "select tdp.delivery_id, concat(tdp.delivery_no, tdp.contractor_name) as keyId, convert(nvarchar(10), tdp.request_date, 21) as request_date
            , tdp.delivery_no, tdp.order_no, tdp.item_line_no as line_no, isnull(tdp.item_type_name, '') as item_type_name
            , concat('['+ tdp.item_code +'] ', tdp.item_name) as item_name, tdp.item_unit_code as item_unit
            , isnull(tdp.item_qty, 0) as item_qty, isnull(round(tdp.item_weight, 0), 0)*tdp.item_qty as item_weight
            , isnull((select top 1 convert(nvarchar(16), tlde.create_date, 21) from [transaction_log_delivery_edit] tlde
                where tlde.delivery_no = tdp.delivery_no and tlde.order_no = tdp.order_no and tlde.item_line_no = tdp.item_line_no
                order by tlde.create_date desc), '') as modified_date
            , isnull((select top 1 su.[name] from [transaction_log_delivery_edit] tlde
                inner join [sys_user] su on su.id = tlde.create_by
                where tlde.delivery_no = tdp.delivery_no and tlde.order_no = tdp.order_no and tlde.item_line_no = tdp.item_line_no
                order by tlde.create_date desc), '') as modified_by
        from [transaction_delivery_plan] tdp
        group by tdp.delivery_id, tdp.delivery_no, tdp.contractor_name, tdp.request_date
            , tdp.order_no, tdp.item_line_no
            , tdp.item_type_name, tdp.item_unit_code, tdp.item_code, tdp.item_name
            , tdp.item_qty, tdp.item_weight, tdp.item_width, tdp.item_length, tdp.item_height
        order by tdp.request_date, tdp.order_no, cast(tdp.item_line_no as int)";
    $result2 = dbConnection( $cmd );
    echo json_encode( [['status'=>'Y', 'message'=>$GLOBALS['_lng108'], 'data1'=>$result1, 'data2'=>$result2]] );
}

function verifyPlan() {
    $hduser = $_POST['hduser'];
    $deliveryNo = $_POST['deliveryNo'];
    $deliveryNoList = preg_split( "/,/", $deliveryNo );
    
    $messages = array();
    
    try {
    
        foreach($deliveryNoList as $delNo) {
            $result = proccessTransactionDeliveryVerify( $hduser, $delNo );
            $detail = proccessTransactionDeliveryDetail( $delNo );
            $result['0']['message'] = $delNo. ", ". $GLOBALS[$result['0']['message']] ."!";
            //$result['0']['return'] = $detail['0']['return_status'];
            array_push( $messages, $result['0'] );
        }
    } catch (exception $e) {
        $messages = [array("status" => "N", "message" => $e)];
    }
    
    //dbConnection("exec [dbo].[_RUN_DELIVERY_DETAIL]");
    echo json_encode( $messages );
}

function unverifyPlan() {
    $hduser = $_POST['hduser'];
    $deliveryNo = $_POST['deliveryNo'];
    $deliveryNoList = preg_split( "/,/", $deliveryNo );
    
    $messages = array();
    foreach($deliveryNoList as $delNo) {
        $result = proccessTransactionDeliveryUnverify( $hduser, $delNo );
        $result['0']['message'] = $delNo. ", ". $GLOBALS[$result['0']['message']] ."!";
        array_push( $messages, $result['0'] );
    }
    
    echo json_encode( $messages );
}

function checkBeforeUnverifyPlan() {
    $hduser = $_POST['hduser'];
    $deliveryNo = $_POST['deliveryNo'];
    $deliveryNoList = preg_split( "/,/", $deliveryNo );
    
    $messages = array();
    foreach($deliveryNoList as $delNo) {
        $cmd = "select count(*) as count_data from [dbo].[transaction_delivery_plan_detail] where delivery_no = '$delNo'";
        $result = dbConnection( $cmd );
        
        if ($result['0']['count_data'] > 0) {
            array_push( $messages, array("status" => "N", "message" => $delNo . " has been sync to Mobile!") );
        } else {
            array_push( $messages, array("status" => "Y", "message" => "OK!") );
        }
    }
    
    echo json_encode( $messages );
}

function confirmPlan() {
    $hduser = $_POST['hduser'];
    $deliveryNo = $_POST['deliveryNo'];
    $deliveryNoList = preg_split( "/,/", $deliveryNo );
    
    $messages = array();
    foreach($deliveryNoList as $delNo) {
        $result = proccessTransactionDeliveryConfirm( $hduser, $delNo );
        $result['0']['message'] = $delNo. ", ". $GLOBALS[$result['0']['message']] ."!";
        array_push( $messages, $result['0'] );
    }
    
    
    echo json_encode( $messages );
}

function unconfirmPlan() {
    $hduser = $_POST['hduser'];
    $deliveryNo = $_POST['deliveryNo'];
    $deliveryNoList = preg_split( "/,/", $deliveryNo );
    
    $messages = array();
    foreach($deliveryNoList as $delNo) {
        $result = proccessTransactionDeliveryUnconfirm( $hduser, $delNo );
        $result['0']['message'] = $delNo. ", ". $GLOBALS[$result['0']['message']] ."!";
        array_push( $messages, $result['0'] );
    }
    
    echo json_encode( $messages );
}

function proccessTransactionDeliveryVerify( $userId, $delNoList ) {
    $cmd = "exec [process_4_after_delivery_2_verify] N'$userId', N'$delNoList'";
    $result = dbConnection( $cmd );
    return $result;
}

function proccessTransactionDeliveryUnverify( $userId, $delNoList ) {
    $cmd = "exec [process_4_after_delivery_2_unverify] N'$userId', N'$delNoList'";
    $result = dbConnection( $cmd );
    return $result;
}

function proccessTransactionDeliveryConfirm( $userId, $delNoList ) {
    $cmd = "exec [process_4_after_delivery_2_confirm] N'$userId', N'$delNoList'";
    $result = dbConnection( $cmd );
    return $result;
}

function proccessTransactionDeliveryUnconfirm( $userId, $delNoList ) {
    $cmd = "exec [process_4_after_delivery_2_unconfirm] N'$userId', N'$delNoList'";
    $result = dbConnection( $cmd );
    return $result;
}

function proccessTransactionDeliveryDetail( $delNoList ) {
    $cmd = "exec [_RUN_DELIVERY_DETAIL] N'$delNoList'";
    $result = dbConnection( $cmd );
    return $result;
}

function getPlanItem() {
    $hduser = $_POST['hduser'];
    $hdcontractor = $_POST['hdcontractor'];
    /*$deliveryNo = $_POST['deliveryNo'];
    $orderNo = $_POST['orderNo'];
    $lineNo = $_POST['lineNo'];*/
    $keyId = $_POST['keyId'];
    
    //where tdp.delivery_no = N'$deliveryNo' and tdp.order_no = N'$orderNo' and tdp.item_line_no = N'$lineNo'
    $cmd = "select tdp.delivery_id, concat(tdp.delivery_no, tdp.contractor_name) as keyId, convert(nvarchar(10), tdp.request_date, 21) as request_date
            , tdp.delivery_no, tdp.order_no, tdp.item_line_no as line_no, isnull(tdp.item_type_name, '') as item_type_name
            , concat('['+ tdp.item_code +'] ', tdp.item_name) as item_name, tdp.item_unit_code as item_unit
            , isnull(tdp.item_weight, 0) * isnull(tdp.item_qty, 0) as item_weight, isnull(tdp.item_qty, 0) as item_qty
            , isnull((select top 1 toh.order_status from [transaction_order_head] toh where toh.order_id = tdp.order_id), 0) as order_status
        from [transaction_delivery_plan] tdp
        where concat(tdp.delivery_no, tdp.contractor_name) = N'$keyId'
        group by tdp.delivery_id, tdp.delivery_no, tdp.contractor_name, tdp.request_date
            , tdp.order_id, tdp.order_no, tdp.item_line_no
            , tdp.item_type_name, tdp.item_unit_code, tdp.item_code, tdp.item_name
            , tdp.item_qty, tdp.item_weight, tdp.item_width, tdp.item_length, tdp.item_height
        order by tdp.request_date, tdp.order_no, cast(tdp.item_line_no as int)";
    $result = dbConnection( $cmd );
    echo json_encode($result);
}

function saveEditPlanItem() {
    $hduser = $_POST['hduser'];
    $hdcontractor = $_POST['hdcontractor'];
    $deliveryNo = $_POST['deliveryNo'];
    $orderItemData = $_POST['orderItemData'];
    
    $itemData = preg_split('/\s*,\s*/', $orderItemData);
    
    foreach ($itemData as $item) {
        $isplit = explode('###', $item);
        $orderNo = $isplit['0'];
        $lineNo = $isplit['1'];
        $newItemQty = intval($isplit['2']);
        
        
        $cmd = "select top 1 count(order_id) as check_condition from [transaction_order_head] where order_status in (1,2,3) and order_no = N'$orderNo'";
        $result = dbConnection( $cmd );
        if (!intval($result['0']['check_condition'])) {
            continue;
        }
        
        $cmd1 = "select isnull(toi.item_qty, 0) as order_item_qty, isnull(toi.item_qty_planned, 0) as order_item_qty_planned
                , isnull((select top 1 sum(tdp.item_qty) from [transaction_delivery_plan] tdp
                    where tdp.order_id = toh.order_id and tdp.item_line_no = toi.item_line_no), 0) as plan_item_qty
            from [transaction_order_item] toi
            inner join [transaction_order_head] toh on toh.order_id = toi.order_id
            where toh.order_no = N'$orderNo' and toi.item_line_no = N'$lineNo'";
        $result1 = dbConnection( $cmd1 );
        $itemQty = intval($result1['0']['order_item_qty']);
        $itemQtyPlanned = intval($result1['0']['order_item_qty_planned']);
        $planItemQty = intval($result1['0']['plan_item_qty']);
        $itemQtyPlannedWithoutThisPlan = ($itemQtyPlanned - $planItemQty > 0)? $itemQtyPlanned - $planItemQty: 0;
        
        
        // If break statement when qty is more than order qty
        if ($itemQty < $itemQtyPlannedWithoutThisPlan + $newItemQty) {
            continue;
        }
                
        $cmd2 = "update [transaction_delivery_plan] set item_qty = '$newItemQty', modified_by = '$hduser', modified_date = getdate()
            where delivery_no = N'$deliveryNo' and order_no = N'$orderNo' and item_line_no = N'$lineNo'";
        dbConnection( $cmd2 );
        
        $cmd3 = "update toi
                set toi.item_qty_planned = (case when toi.item_qty < ". ($itemQtyPlannedWithoutThisPlan + $newItemQty) ." then toi.item_qty else ". ($itemQtyPlannedWithoutThisPlan + $newItemQty) ." end)
            from [transaction_order_item] toi
            inner join [transaction_delivery_plan] tdp on tdp.order_id = toi.order_id and tdp.item_line_no = toi.item_line_no
            where tdp.delivery_no = N'$deliveryNo' and tdp.order_no = N'$orderNo' and tdp.item_line_no = N'$lineNo'";
        dbConnection( $cmd3 );
        
        $cmd4 = "select isnull((select sum(toi.item_qty) from [transaction_order_item] toi
                inner join [transaction_order_head] toh on toh.order_id = toi.order_id
                where toh.order_no = N'$orderNo'), 0) as order_item_qty
            , isnull((select top 1 sum(tdp.item_qty) from [transaction_delivery_plan] tdp
                where tdp.order_no = N'$orderNo'), 0) as plan_order_item_qty";
        $result4 = dbConnection( $cmd4 );
        $orderItemQty = intval($result4['0']['order_item_qty']);
        $planOrderItemQty = intval($result4['0']['plan_order_item_qty']);
        
        if ($orderItemQty > $planOrderItemQty) {
            $cmd = "update [transaction_order_head] set order_status = 2, modified_by = '$hduser', modified_date = getdate() where order_no = N'$orderNo'";
            dbConnection( $cmd );
        } else {
            $cmd = "update toh set toh.order_status = 3, toh.modified_by = '$hduser', toh.modified_date = getdate()
                from [transaction_order_head] toh
                where exists(select top 1 tdp.delivery_id from [transaction_delivery_plan] tdp
                            where tdp.order_id = toh.order_id and tdp.delivery_no = N'$deliveryNo')
                    and toh.order_no = N'$orderNo'";
            dbConnection( $cmd );
        }
        
        $result_message = $deliveryNo .", Update Item's quantity from ". $planItemQty ." to ". $newItemQty;
        new Delivery_Write_Log($hduser, $deliveryNo, $orderNo, $lineNo, "Y", $result_message);
    }
    echo json_encode(array(['status'=>'Y', 'message'=> $GLOBALS['_lng108'] ."!"]));
}

function getRoute(){
    $deliveryNo = $_POST['deliveryNo'];
    $cmd = "select td.delivery_no, tdr.station_latitude, tdr.station_longtitude, tdr.route_seq, tdr.station_name,tdr.station_code
            from [dbo].[transaction_delivery_route] tdr
            inner join [dbo].[transaction_delivery] td on td.delivery_id = tdr.delivery_id
            where tdr.trash = 0 and td.delivery_no = '$deliveryNo'
            order by tdr.route_seq";
    
    $result = dbConnection( $cmd );
    echo json_encode($result);
    
}

function getEditVehicle() {
    $dateStart = $_POST['dateStart'] ." 00:00:00";
    $dateEnd = $_POST['dateEnd'] ." 23:59:59";
    $deliveryId = $_POST['deliveryId'];
    $vehicleId = $_POST['vehicleId'];
    
    $cmd = " select vehicle_id, vehicle_name, isnull((select top 1 md.driver_fname + ' ' + nullif(md.driver_lname, '') from [dbo].[master_driver] md where md.trash = 0 and md.driver_id = mv.driver01), '') as driver_name
            , isnull((select convert(varchar(16), max(tdr.plan_out_time), 21) from [dbo].[transaction_delivery_route] tdr
		inner join [dbo].[transaction_delivery] tdo on tdo.delivery_id = tdr.delivery_id
		where tdo.vehicle_id = mv.vehicle_id and tdo.delivery_id = '$deliveryId'), '') as last_plan_time
            , 1 checked
        from [dbo].[master_vehicle] mv where trash = 0 and inactive = 0 and mv.vehicle_id = '$vehicleId'
        group by vehicle_id, vehicle_name, driver01
            union
        select vehicle_id, vehicle_name, isnull((select top 1 md.driver_fname + ' ' + nullif(md.driver_lname, '') from [dbo].[master_driver] md where md.trash = 0 and md.driver_id = mv.driver01), '') as driver_name
            , isnull((select convert(varchar(16), max(tdr.plan_out_time), 21) from [dbo].[transaction_delivery_route] tdr
		inner join [dbo].[transaction_delivery] tdo on tdo.delivery_id = tdr.delivery_id
		where tdo.trash = 0 and tdr.trash = 0 and (tdo.delivery_date between cast('$dateStart' as datetime) and cast('$dateEnd' as datetime))
                    and tdo.vehicle_id = mv.vehicle_id
                    and exists(select tdo2.delivery_id from [dbo].[transaction_delivery] tdo2
                            where tdo2.trash = 0 and tdo2.delivery_date = tdo.delivery_date
                                and tdo2.delivery_id = '$deliveryId')), '') as last_plan_time
            , 0 checked
        from [dbo].[master_vehicle] mv
        where trash = 0 and inactive = 0
            and vehicle_id <> (select top 1 vehicle_id from [dbo].[transaction_delivery]
                               where delivery_id = '$deliveryId')
        group by vehicle_id, vehicle_name, driver01
        order by checked desc, vehicle_name";
    $result = dbConnection( $cmd );
    echo json_encode($result);
}

function saveEditVehicle() {
    $hduser = $_POST['hduser'];
    $deliveryId = $_POST['deliveryId'];
    $vehicleId = $_POST['vehicleId'];
    
    $cmd = "select delivery_id from [dbo].[transaction_delivery] where trash = 1 and delivery_id = '$deliveryId'";
    $result = dbConnection( $cmd );
    if (count($result) > 0) {
        echo json_encode(array(['status'=>'N', 'message'=> $GLOBALS['_lng174'] ."!"]));
        exit();
    }
    
    $cmd = "select delivery_id from [dbo].[transaction_delivery] where trash = 0 and plan_status <> 0 and delivery_id = '$deliveryId'";
    $result = dbConnection( $cmd );
    if (count($result) > 0) {
        echo json_encode(array(['status'=>'N', 'message'=> $GLOBALS['_lng169'] .", ". $GLOBALS['_lng366'] ."!"]));
        exit();
    }
    
    $cmd = "update tdo
		set vehicle_id = mv.vehicle_id
			, vehicle_name = mv.vehicle_name
			, LICENSE_PLATE = mv.vehicle_name
			, BLACKBOX_ID = mv.BLACKBOX_ID
			, vehicle_type_id = mv.pickup_id
			, vehicle_type_name = mv.pickup_name
			, max_load_weight = mv.pickup_max_weight
			, max_load_width = mv.pickup_max_width
			, max_load_length = mv.pickup_max_length
			, max_load_height = mv.pickup_max_height
			, max_load_volume = mv.pickup_max_width * mv.pickup_max_length * mv.pickup_max_height
			, driver_id = mv.driver_id
			, driver_name = mv.driver_name
			, modified_by = '$hduser'
			, modified_date = getdate()
		from [dbo].[transaction_delivery] tdo
		cross join (
			select mv.vehicle_id
				, mv.vehicle_name
				, mv.BLACKBOX_ID
				, mvp.pickup_id
				, mvp.pickup_name
				, isnull(mvp.pickup_max_weight, 0) as pickup_max_weight
				, isnull(mvp.pickup_max_width, 0) as pickup_max_width
				, isnull(mvp.pickup_max_length, 0) as pickup_max_length
				, isnull(mvp.pickup_max_height, 0) as pickup_max_height
				, md.driver_id
				, nullif(ltrim(rtrim(concat(md.driver_fname, ' ', md.driver_lname))), '') as driver_name
			from [dbo].[master_vehicle] mv
			left join [dbo].[master_driver] md on md.driver_id = mv.driver01
			left join [dbo].[master_vehicle_pickup] mvp on mvp.pickup_id = mv.pickup_id_default
			where mv.vehicle_id = '$vehicleId'
		) mv
		where tdo.delivery_id = '$deliveryId'";
	//update [dbo].[transaction_delivery] set vehicle_id = '$vehicleId', modified_by = '$hduser', modified_date = getdate() where delivery_id = '$deliveryId'";
    $result = dbConnection( $cmd );
    
    try {
        $array_delivery = dbConnection("select delivery_id, delivery_no from [dbo].[transaction_delivery] where delivery_id = '$deliveryId'");
        $deliveryNo = $array_delivery['0']['delivery_no'];

        dbConnection("update [dbo].[transaction_delivery_plan_detail] set trash = 1, modified_by = '$hduser', modified_date = getdate() where trash = 0 and delivery_no = '$deliveryNo' ");
        //dbConnection("exec [dbo].[_RUN_DELIVERY_DETAIL] '$deliveryNo' ");
    } catch (Exception $ex) {
        
    }
    echo json_encode(array(['status'=>'Y', 'message'=> $GLOBALS['_lng108'] ."!"]));
}

function saveEditDeliveryDate() {
	$hduser = $_POST['hduser'];
    $deliveryId = $_POST['deliveryId'];
    $deliveryDate = $_POST['deliveryDate'];
    
    /*$cmd = "select delivery_id from [dbo].[transaction_delivery] where trash = 1 and delivery_id = '$deliveryId'";
    $result = dbConnection( $cmd );
    if (count($result) > 0) {
        echo json_encode(array(['status'=>'N', 'message'=> $GLOBALS['_lng174'] ."!"]));
        exit();
    }
    
    $cmd = "select delivery_id from [dbo].[transaction_delivery] where trash = 0 and plan_status <> 0 and delivery_id = '$deliveryId'";
    $result = dbConnection( $cmd );
    if (count($result) > 0) {
        echo json_encode(array(['status'=>'N', 'message'=> $GLOBALS['_lng169'] .", ". $GLOBALS['_lng366'] ."!"]));
        exit();
    }*/
    
    $cmd = " exec [dbo].[process_4_after_delivery_3_edit] '$hduser', '$deliveryId', '$deliveryDate' ";
    $result = dbConnection( $cmd );
	$result['0']['message'] = $GLOBALS[$result['0']['message']] ."!";
	
	echo json_encode($result);
    
    try {
        $array_delivery = dbConnection("select delivery_id, delivery_no from [dbo].[transaction_delivery] where delivery_id = '$deliveryId'");
        $deliveryNo = $array_delivery['0']['delivery_no'];

        dbConnection("update [dbo].[transaction_delivery_plan_detail] set trash = 1, modified_by = '$hduser', modified_date = getdate() where trash = 0 and delivery_no = '$deliveryNo' ");
        //dbConnection("exec [dbo].[_RUN_DELIVERY_DETAIL] '$deliveryNo' ");
    } catch (Exception $ex) {
        
    }
}
?>