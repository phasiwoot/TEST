<?php
session_start();
header('Content-Type: text/pain; charset=utf-8');
date_default_timezone_set('Asia/Bangkok');
//error_reporting(E_ERROR | E_PARSE);
require_once("../../../config/db_connection.php");
require_once("../../../resources/function/php/sqlsrv_connect.php");
require_once("../../../config/languages/language.php");
    
initial();
function initial(){
    if(isset($_REQUEST['func'])){
        $func = $_REQUEST['func'];
        if(function_exists($func)){
            $func();
        }else{
            echo json_encode([["status"=>"N", "message"=>$GLOBALS['_lng104'] ."!"]]);
        }
    }
}

function getStartPage(){
    //header('Content-Type: text/pain');

    // Contractor 1
    $result = dbConnection("select mc.contractor_code as [id], mc.contractor_code as [text]
        from [dbo].[master_contractor] mc
        where mc.trash = 0 and mc.inactive = 0
        group by mc.id, mc.contractor_code
        order by [text]");
    echo json_encode($result);
    echo "@@@";
    
    // Shipmode 2
    $result = dbConnection("select [ship_mode] as id, [ship_mode] as [text]
        from [dbo].[transaction_order]
        group by [ship_mode]");
    echo json_encode($result);
    echo "@@@";
    
    // VehicleType 3
    $result = dbConnection("select mvp.pickup_id as id, mvp.pickup_name as [text]
        from [dbo].[master_vehicle_pickup] mvp
        where mvp.trash = 0 and mvp.inactive = 0
        group by mvp.pickup_id, mvp.pickup_name
        order by [text]");
    echo json_encode($result);
    echo "@@@";
    
    // Province 4
    $orderDateType = $_POST['orderDateType'];
    $orderDateSt = $_POST['orderDateSt'];
    $orderDateEn = $_POST['orderDateEn'];
    
    $cond = " and (cast(toh.request_date as datetime) between cast('$orderDateSt' as datetime) and cast('$orderDateEn' as datetime)) ";
    if ($orderDateType == "REQ") {
        $cond = " and (cast(toh.request_date as datetime) between cast('$orderDateSt' as datetime) and cast('$orderDateEn' as datetime))";
    } else if ($orderDateType == "ORD") {
        $cond = " and (cast(toh.order_date as datetime) between cast('$orderDateSt' as datetime) and cast('$orderDateEn' as datetime))";
    }
    
    $result = dbConnection("select isnull(abb_prov, '-') as prov
            from [dbo].[transaction_order] toh where toh.trash = 0 $cond
            group by abb_prov order by abb_prov");
    echo json_encode($result);
    echo "@@@";
    
    // VehicleGroup 5
    $result = dbConnection("select mg.group_id as id, mg.group_name as text
                , isnull(stuff((select ', ' + gp.ABB_PROV from [dbo].[group_province] gp where gp.trash = 0 and gp.group_id = mg.group_id for xml path('') ), 1, 2, ''), '') as title
            from [dbo].[master_group] mg where mg.trash = 0 and mg.group_id in (select gp.group_id from [dbo].[group_province] gp where gp.trash = 0)
            order by text");
    echo json_encode($result);
}

function getOptionContractor() {
    $cmd = "select mc.contractor_code as [id], mc.contractor_code as [text]
        from [dbo].[master_contractor] mc
        where mc.trash = 0 and mc.inactive = 0
        group by mc.id, mc.contractor_code
        order by [text]";
    $result = dbConnection( $cmd );
    
    echo json_encode($result);
}

function getOptionShipmode() {
    $cmd = "select [ship_mode] as id, [ship_mode] as [text]
        from [dbo].[transaction_order]
        group by [ship_mode]";
    $result = dbConnection( $cmd );
    
    echo json_encode($result);
}

function getOptionVehicleType() {
    $cmd = "select mvp.pickup_id as id, mvp.pickup_name as [text]
        from [dbo].[master_vehicle_pickup] mvp
        where mvp.trash = 0 and mvp.inactive = 0
        group by mvp.pickup_id, mvp.pickup_name
        order by [text]";
    $result = dbConnection( $cmd );
    echo json_encode($result);
}

function getOrderGroup() {
//    $hduser = $_POST['hduser'];
//    $hdcontractor = $_POST['hdcontractor'];
    $orderDateType = $_POST['orderDateType'];
    $orderDateSt = $_POST['orderDateSt'];
    $orderDateEn = $_POST['orderDateEn'];
    $orderSource = $_POST['orderSource'];
    $orderContractor = $_POST['orderContractor'];
    $orderSearch = trim($_POST['orderSearch']);
    $orderStatus = $_POST['orderStatus'];
    $orderType = $_POST['orderType'];
    $pickOrderGroup = $_POST['pickOrderGroup'];
    $proviceName = $_POST['proviceName'];
    
    $orderDateSt .= ' 00:00:00';
    $orderDateEn .= ' 23:59:59';
    
    $cond = " and (cast(toh.request_date as datetime) between cast('$orderDateSt' as datetime) and cast('$orderDateEn' as datetime)) ";
    
    if ($orderDateType == "ORD") {
        $cond = " and (cast(toh.order_date as datetime) between cast('$orderDateSt' as datetime) and cast('$orderDateEn' as datetime)) ";
    } else if ($orderDateType == "REQ") {
        $cond = " and (cast(toh.request_date as datetime) between cast('$orderDateSt' as datetime) and cast('$orderDateEn' as datetime)) ";
    }
	
    if (!empty($orderSource)) {
        $cond .= " and (toh.ship_mode = N'$orderSource') ";
    }
        
    if (!empty($orderContractor)) {
        $cond .= " and toh.contractor_code = N'$orderContractor' ";
    }
    
    if (!empty($orderSearch)) {
        $cond .= " and (toh.order_no like N'%$orderSearch%' or toh.consignment_no like N'%$orderSearch%'";
        $cond .= " or toh.customer_name like N'%$orderSearch%' or toh.src_name like N'%$orderSearch%' or toh.des_name like N'%$orderSearch%') ";
    }
    
    if (!empty($orderStatus)) {
        $condOrderStatus = "";
        $splitOrderStatus = explode(',', $orderStatus);
        foreach($splitOrderStatus as $sts) {
            if ($sts == "wait") {
                $condOrderStatus .= ",1";
            } else if ($sts == "planned") {
                $condOrderStatus .= ",2,3";
            }
        }
        
        $condOrderStatus = substr($condOrderStatus, 1, strlen($condOrderStatus));
        $cond .= " and toh.order_status in ($condOrderStatus) ";
    } else {
        $cond .= " and toh.order_status in (1,2,3) ";
    }
	
	if (!empty($orderType)) {
		$cond .= " and order_type = '$orderType' ";
	}
    
    $splitPickOrderGroup = explode(',', $pickOrderGroup);
    $orderBy = join(',', array_map(function($f){ return $f; }, $splitPickOrderGroup));
    
    if(!empty($proviceName)){
        //$cond .= " and toh.abb_prov in (select items from [dbo].[Splitfn] ('$proviceName', ',') )";
		$cond .= " and toh.cm_prov in (select items from [dbo].[Splitfn] ('$proviceName', ',') )";
    }
    
    $cmd = "select toh.order_id, toh.order_no, toh.consignment_no

            , toh.customer_id, concat('['+ toh.customer_code +'] ', toh.customer_name) as customer_name
            , toh.contractor_code
            , isnull((select top 1 concat('['+ mc.contractor_code +'] ', mc.contractor_name)
                from [dbo].[master_contractor] mc
                where mc.trash = 0 and mc.contractor_code = toh.contractor_code), '-') as contractor_name

            , toh.src_id, concat('['+ toh.src_code +'] ', toh.src_name) as src_name
            , toh.des_id, concat('['+ toh.des_code +'] ', toh.des_name) as des_name

            , isnull(toh.abb_prov, '') as abb_prov
            , concat(toh.des_area, ' / ', toh.abb_amp, ' / ', toh.abb_tam) as address_pat
            , concat(toh.des_area, ' / ', toh.abb_amp) as address_pa

            , sum(isnull(toi.item_qty, 0)) as total_item_qty
            , sum(isnull(toi.item_qty_planned, 0)) as total_item_qty_planned

            , sum(isnull(toi.item_qty, 0) * isnull(toi.item_weight, 0)) as total_item_weight
            , sum(isnull(toi.item_qty_planned, 0) * isnull(toi.item_weight, 0)) as total_item_weight_planned

            , isnull(stuff((select ','+ cast(mvp.pickup_id as varchar(10))
                from [dbo].[config_customer_pickup] ccp
                inner join [dbo].[master_vehicle_pickup] mvp on mvp.pickup_id = ccp.pickup_id
                where ccp.trash = 0 and mvp.trash = 0 and mvp.inactive = 0
                    and ccp.customer_id = toh.customer_id and ccp.address_id = toh.des_id
                group by mvp.pickup_id for xml path('')), 1, 1, ''), '') as config_pickup
			, toh.order_status
			
        from [dbo].[transaction_order] toh
        inner join [dbo].[transaction_order_item] toi on toi.order_id = toh.order_id
        where toh.trash = 0 $cond
            and toh.src_latitude <> 0.0 and toh.src_longtitude <> 0.0
            and toh.des_latitude <> 0.0 and toh.des_longtitude <> 0.0
        group by toh.order_id, toh.order_no, toh.consignment_no, toh.owner_name, toh.contractor_code
            , toh.customer_id, toh.customer_code, toh.customer_name
            , toh.src_id, toh.src_code, toh.src_name
            , toh.des_id, toh.des_code, toh.des_name
            , toh.des_area, toh.abb_prov, toh.abb_amp, toh.abb_tam
			, toh.order_status
        order by $orderBy";
    //echo $cmd;exit();
    //and exists(select toi.order_item_id from [dbo].[transaction_order_item] toi where toi.order_id = toh.order_id)
    $result = dbConnection( $cmd );
    echo json_encode($result);
}

function getOrderDetail() {
    $orderNo = $_POST['orderNo'];
    
    $cmd = "select toh.order_id, toh.order_no, convert(varchar(10), toh.request_date_end, 21) as request_date
            , toh.owner_name
            , concat(toh.customer_code, ' '+ toh.customer_name) as customer_name
            , isnull(concat('['+ toh.src_code +']', toh.src_name), '-') as src_name
            , isnull(concat('['+ toh.des_code +']', toh.des_name), '-') as des_name
            , toi.order_item_id, toi.item_line_no as item_line_no, isnull(toi.item_type_name, '-') as item_type_name
            , isnull(toi.item_name, '-') as item_name, toi.item_qty, toi.item_qty_planned, toi.item_unit_name
            , isnull(toi.item_weight, 0) as item_weight, isnull(toi.item_qty_planned, 0) * isnull(toi.item_weight, 0) as total_weight
            
            , isnull((select top 1 tdi.delivery_id from [dbo].[transaction_delivery_item] tdi
                where tdi.trash = 0 and tdi.order_id = toh.order_id), 0) as delivery_id
            , isnull((select top 1 tdo.plan_status from [dbo].[transaction_delivery] tdo
                inner join [dbo].[transaction_delivery_item] tdi on tdi.delivery_id = tdo.delivery_id
                where tdi.trash = 0 and tdi.order_id = toh.order_id), 0) as plan_status
			, isnull((select top 1 concat(tdo.delivery_no, '@', tdo.delivery_date, '@', tdo.vehicle_name)
				from [dbo].[transaction_delivery] tdo
				inner join [dbo].[transaction_delivery_item] tdi on tdi.delivery_id = tdo.delivery_id
				where tdo.trash = 0 and tdi.trash = 0 and tdi.order_id = toh.order_id
			), '') as plan_detail
        from [dbo].[transaction_order] toh
        inner join [dbo].[transaction_order_item] toi on toi.order_id = toh.order_id
        where toh.order_no = N'$orderNo'
        group by toh.order_id, toh.order_no, toh.request_date_end, toh.owner_name, toh.customer_code, toh.customer_name
            , toh.src_code, toh.src_name, toh.des_code, toh.des_name
            , toi.order_item_id, toi.item_line_no, toi.item_type_name, toi.item_name, toi.item_qty, toi.item_qty_planned, toi.item_unit_name, toi.item_weight
        order by item_line_no";
    $result = dbConnection( $cmd );
    echo json_encode($result);
}

function getVehicleFleet() {
//    $hduser = $_POST['hduser'];
//    $hdcontractor = $_POST['hdcontractor'];
    $deliveryDate = $_POST['deliveryDate'];
    $deliveryContractor = $_POST['deliveryContractor'];
    $deliveryVehicleType = $_POST['deliveryVehicleType'];
    $vehicleGroup = $_POST['vehicleGroup'];
    
    $cond = "";
    if (!empty($deliveryVehicleType)) {
        $cond .= " and mv.pickup_id_default = '$deliveryVehicleType' ";
    }
    
    if (!empty($deliveryContractor)) {
        //$cond .= " and isnull(mv.contractor_id, 0) = '$deliveryContractor' ";
        $cond .= " and exists(select mc.id from [dbo].[master_contractor] mc where mc.trash = 0 and mc.id = mv.contractor_id and mc.contractor_code = N'$deliveryContractor') ";
        
    }
    
    if(!empty($vehicleGroup)){
        $cond .= " and mv.vehicle_id in (select gv.vehicle_id
                                        from [dbo].[master_group] mg
                                        inner join [dbo].[group_vehicle] gv on gv.group_id = mg.group_id
                                        where mg.trash = 0 and gv.trash = 0 
                                        and mg.group_name in (select items from [dbo].[Splitfn] ('$vehicleGroup', ',')) )";
    }
    
    $cmd = "select mv.vehicle_id, mv.vehicle_name
            , isnull(mvp.pickup_name, '-') as vehicle_type_name
            , isnull((select top 1 mc.contractor_code from [dbo].[master_contractor] mc
                where mc.trash = 0 and mc.id = mv.contractor_id), '-') as contractor_name
            , isnull(mvp.pickup_max_weight, 0) as max_loading_weight
            , isnull((select top 1 convert(varchar(16), tdr.plan_out_time, 21)
		from [dbo].[transaction_delivery] tdo
                inner join [dbo].[transaction_delivery_route] tdr on tdr.delivery_id = tdo.delivery_id
		where tdo.trash = 0 and tdr.trash = 0 and tdo.vehicle_id = mv.vehicle_id and tdo.delivery_date = cast('$deliveryDate' as date)
		order by tdr.plan_out_time desc, tdr.route_seq), '-') as last_plan_time
            , isnull((select top 1 concat('['+ tdr.station_code +'] ', tdr.station_name) as station_name
                from [dbo].[transaction_delivery] tdo
                inner join [dbo].[transaction_delivery_route] tdr on tdr.delivery_id = tdo.delivery_id
                where tdo.trash = 0 and tdr.trash = 0 and tdo.vehicle_id = mv.vehicle_id and tdo.delivery_date = cast('$deliveryDate' as date)
                order by tdr.plan_out_time desc, tdr.route_seq), '-') as last_plan_route
            , isnull((select top 1 concat(md.driver_fname, ' '+ md.driver_lname) from [dbo].[master_driver] md
                where md.trash = 0 and md.inactive = 0 and md.driver_id = mv.driver01), '-') as driver_name
        from [dbo].[master_vehicle] mv
        left join [dbo].[master_vehicle_pickup] mvp on mvp.pickup_id = mv.pickup_id_default
        where mv.trash = 0 and mv.inactive = 0 and isnull(mvp.trash, 0) = 0 and isnull(mvp.inactive, 0) = 0
            $cond
        group by mv.vehicle_id, mv.vehicle_name, mv.contractor_id
            , mvp.pickup_name, mvp.pickup_max_weight, mv.driver01
        order by pickup_name, vehicle_name";
    
    $result = dbConnection( $cmd );
    echo json_encode($result);
}

function getDeliveryFleet() {
    $deliveryDate = $_POST['deliveryDate'];
    $vehicleId = $_POST['vehicleId'];
    
    $cmd = "select tdo.delivery_id, tdo.vehicle_id, tdo.delivery_no
            , isnull((select count(distinct tdi.order_id) from [dbo].[transaction_delivery_item] tdi
                where tdi.trash = 0 and tdi.delivery_id = tdo.delivery_id), 0) as order_qty
            , tdo.current_load_weight
            , [dbo].[GET_TRUCK_PERCENT_LOAD](tdo.vehicle_id, tdo.current_load_weight, 'weight') as percent_loading_weight
            , isnull((select top 1 convert(varchar(16), tdr.plan_out_time, 21) from [dbo].[transaction_delivery_route] tdr
                where tdr.trash = 0 and tdr.delivery_id = tdo.delivery_id order by tdr.plan_out_time desc, tdr.route_seq desc), '-') as last_plan_time
            , isnull((select top 1 tdr.station_name from [dbo].[transaction_delivery_route] tdr
                where tdr.trash = 0 and tdr.delivery_id = tdo.delivery_id order by tdr.plan_out_time desc, tdr.route_seq desc), '-') as last_plan_route
            , isnull((select sum(tdr.plan_distance) from [dbo].[transaction_delivery_route] tdr
                where tdr.trash = 0 and tdr.delivery_id = tdo.delivery_id), 0) as total_plan_distance
            , isnull(tdo.plan_status, 0) as plan_status
        from [dbo].[transaction_delivery] tdo
        where tdo.trash = 0 and tdo.vehicle_id = '$vehicleId' and tdo.delivery_date = cast('$deliveryDate' as date)
        group by tdo.delivery_id, tdo.vehicle_id, tdo.delivery_no, tdo.delivery_date, tdo.current_load_weight, tdo.plan_status
        order by last_plan_time desc, tdo.delivery_id desc";
    $result = dbConnection( $cmd );
    echo json_encode($result);
}

function getDeliveryFleetRoute() {
    $deliveryId = $_POST['deliveryId'];
    $dataOrder = $_POST['dataOrder'];
    
    $cmd = "exec [dbo].[process_routing_simulation] '$deliveryId', '$dataOrder'";
    $result = dbConnection( $cmd );
    echo json_encode($result);
}

function addPlan() {
    $hduser = $_POST['hduser'];
    $vehicleId = $_POST['vehicleId'];
    $deliveryDate = $_POST['deliveryDate'];
    $departureTime = $_POST['departureTime'];
    $dataOrder = $_POST['dataOrder'];
    $deliveryId = $_POST['deliveryId'];
    
	/*$dataOrder1 = '';
	$dataOrder2 = '';
	$dataOrder3 = '';
	$dataOrder4 = '';*/
	//select [status], [message], (select top 1 delivery_no from [dbo].[transaction_delivery] where delivery_id = @delivery_id) as delivery_no
    $cmd = "exec [dbo].[process_3_delivery_2_build] '$hduser', '$vehicleId', '$deliveryDate', '$departureTime', '$dataOrder', '$deliveryId'";
    $result = dbConnection( $cmd );
	
	if ($result['0']['status'] == 'Y') {
		$delivery_no = '';
		if (+$deliveryId == 0) {
			$cmd = "select top 1 delivery_no from [dbo].[transaction_delivery] where delivery_id = IDENT_CURRENT('dbo.transaction_delivery');  ";
			$result2 = dbConnection( $cmd );
			
			$delivery_no = $result2['0']['delivery_no'];
		} else {
			$cmd = "select top 1 delivery_no from [dbo].[transaction_delivery] where delivery_id = '$deliveryId';  ";
			$result2 = dbConnection( $cmd );
			
			$delivery_no = $result2['0']['delivery_no'];
		}
				
		$result['0']['message'] = $delivery_no .', '. $GLOBALS[$result['0']['message']] ."!";
	} else {
		$result['0']['message'] = $GLOBALS[$result['0']['message']] ."!";
	}
	
    echo json_encode($result);
}

function deletePlan() {
    $hduser = $_POST['hduser'];
    $deliveryId = $_POST['deliveryId'];
    
    $cmd = "exec [dbo].[process_4_after_delivery_2_delete] N'PLAN', '$hduser', '$deliveryId'";
    $result = dbConnection( $cmd );
    $result['0']['message'] = $GLOBALS[$result['0']['message']] ."!";
    echo json_encode($result);
}

function deletePlanOrder() {
    $hduser = $_POST['hduser'];
    $deliveryId = $_POST['deliveryId'];
    $orderId = $_POST['orderId'];
    
    $cmd = "exec [dbo].[process_4_after_delivery_2_delete] N'PLAN_ORDER', '$hduser', '$deliveryId', '$orderId'";
    $result = dbConnection( $cmd );
    $result['0']['message'] = $GLOBALS[$result['0']['message']] ."!";
    echo json_encode($result);
}

function viewPlan() {
    $deliveryId = $_POST['deliveryId'];
    
    $cmd = "select top 1 tdo.delivery_id, tdo.delivery_no, tdo.delivery_date
            , tdo.contractor_name, tdo.vehicle_type_name, tdo.vehicle_name
            , tdo.LICENSE_PLATE, tdo.BLACKBOX_ID
            , isnull(tdo.driver_name, '-') as driver_name
            , tdo.current_load_weight, tdo.current_load_volume
            , [dbo].[GET_TRUCK_PERCENT_LOAD](tdo.vehicle_id, tdo.current_load_weight, 'weight') as percent_loading_weight
            , isnull((select count(distinct tdi.order_id) from [dbo].[transaction_delivery_item] tdi
                where tdi.delivery_id = tdo.delivery_id), 0) as count_order
            , tdo.plan_status, convert(varchar(16), tdo.create_date, 21) as create_date
            , isnull(tdo.plan_status, 0) as plan_status
        from [dbo].[transaction_delivery] tdo
        where tdo.trash = 0 and tdo.delivery_id = '$deliveryId'
        group by tdo.delivery_id, tdo.vehicle_id, tdo.delivery_no, tdo.delivery_date
            , tdo.contractor_name, tdo.vehicle_type_name, tdo.vehicle_name
            , tdo.LICENSE_PLATE, tdo.BLACKBOX_ID, tdo.driver_name
            , tdo.current_load_weight, tdo.current_load_volume
            , tdo.plan_status, tdo.create_date";
    $result = dbConnection( $cmd );
    echo json_encode($result);
}

function viewPlanOrder() {
    $deliveryId = $_POST['deliveryId'];
    
    $cmd = "select tdi.delivery_id, tdi.delivery_item_id, toh.order_id
            , toh.order_no, convert(varchar(10), toh.request_date_end, 21) as request_date
            , toh.owner_name as contractor_name
            , concat('['+ toh.customer_code +'] ', toh.customer_name) as customer_name
            , isnull(toh.src_name, '') as src_name, isnull(toh.src_address, '') as src_address
            , isnull(toh.des_name, '') as des_name, isnull(toh.des_address, '') as des_address
            , tdi.item_line_no, tdi.item_name, tdi.item_qty, tdi.item_unit_name as item_unit
            , sum(isnull(tdi.item_weight, 0) * isnull(tdi.item_qty, 0)) as item_weight
            , isnull((select top 1 tdo.plan_status from [dbo].[transaction_delivery] tdo where tdo.delivery_id = tdi.delivery_id), 0) as plan_status
        from [dbo].[transaction_delivery_item] tdi
        inner join [dbo].[transaction_order] toh on toh.order_id = tdi.order_id
        where tdi.trash = 0 and tdi.delivery_id = '$deliveryId'
        group by tdi.delivery_id, tdi.delivery_item_id
            , toh.order_id, toh.order_no, toh.request_date_end
            , toh.owner_name
            , toh.customer_code, toh.customer_name
            , toh.src_name, toh.src_address
            , toh.des_name, toh.des_address
            , tdi.order_item_id, tdi.item_line_no, tdi.item_name, tdi.item_qty, tdi.item_unit_name
        order by toh.order_id, tdi.order_item_id";
    $result = dbConnection( $cmd );
    echo json_encode($result);
}


//function getCustomerConfigPickup() {
//    $orderList = $_POST['orderList'];
//    
//    $array_order_uniq = join(',', array_unique(explode(',', $orderList)));
//    
//    $cmd = "select mvp.pickup_id, mvp.pickup_name
//        from [dbo].[config_customer_pickup] ccp
//        inner join [dbo].[master_vehicle_pickup] mvp on mvp.pickup_id = ccp.pickup_id
//        where ccp.trash = 0 and mvp.trash = 0 and mvp.inactive = 0
//            and exists(
//                    select toh.order_id from [dbo].[transaction_order] toh
//                    where toh.customer_id = ccp.customer_id and toh.des_id = ccp.address_id
//                        and toh.order_id in ($array_order_uniq)
//                )
//        group by mvp.pickup_id, mvp.pickup_name
//        order by mvp.pickup_name";
//    //echo $cmd;exit();
//    $result = dbConnection( $cmd );
//    echo json_encode($result);
//}

function swapRouting() {
    $hduser = $_POST['hduser'];
    $deliveryId = $_POST['deliveryId'];
    $deliveryRouteId = $_POST['deliveryRouteId'];
    $swapDeliveryRouteId = $_POST['swapDeliveryRouteId'];
    
    $cmd = "exec [dbo].[process_routing_swap] '$hduser', '$deliveryId', '$deliveryRouteId', '$swapDeliveryRouteId'";
    //echo $cmd;exit();
    $result = dbConnection( $cmd );
    $result['0']['message'] = $GLOBALS[$result['0']['message']] ."!";
    echo json_encode($result);
    
}

function updateRouting() {
    $hduser = $_POST['hduser'];
    $deliveryId = $_POST['deliveryId'];
    $deliveryRouteId = $_POST['deliveryRouteId'];
    $planInTime = $_POST['planInTime'];
    $planOutTime = $_POST['planOutTime'];
    
    $cmd = "exec [dbo].[process_routing_update] '$hduser', '$deliveryId', '$deliveryRouteId', '$planInTime', '$planOutTime'";
    //echo $cmd;exit();
    $result = dbConnection( $cmd );
    $result['0']['message'] = $GLOBALS[$result['0']['message']] ."!";
    echo json_encode($result);
}

function resetRouting() {
    $hduser = $_POST['hduser'];
    $deliveryId = $_POST['deliveryId'];
    
    $cmd = "exec [dbo].[process_routing_reset] '$hduser', '$deliveryId'";
    //echo $cmd;exit();
    $result = dbConnection( $cmd );
    $result['0']['message'] = $GLOBALS[$result['0']['message']] ."!";
    echo json_encode($result);
}

function reRouting() {
    $hduser = $_POST['hduser'];
    $deliveryId = $_POST['deliveryId'];
    
    $cmd = "exec [dbo].[process_routing_reroute] '$hduser', '$deliveryId'";
    //echo $cmd;exit();
    $result = dbConnection( $cmd );
    $result['0']['message'] = $GLOBALS[$result['0']['message']] ."!";
    echo json_encode($result);
}

function reRoutingAPI() {
    $hduser = $_POST['hduser'];
    $deliveryId = $_POST['deliveryId'];
    
    $cmd = "exec [dbo].[process_routing_reroute_api] '$hduser', '$deliveryId'";
    //echo $cmd;exit();
    $result = dbConnection( $cmd );
    $result['0']['message'] = $GLOBALS[$result['0']['message']] ."!";
    echo json_encode($result);
}

function getProvince(){
    $orderDateType = $_POST['orderDateType'];
    $orderDateSt = $_POST['orderDateSt'];
    $orderDateEn = $_POST['orderDateEn'];
    
    $cond = " and (cast(toh.request_date as datetime) between cast('$orderDateSt' as datetime) and cast('$orderDateEn' as datetime)) ";
	
    if ($orderDateType == "REQ") {
        $cond = " and (cast(toh.request_date as datetime) between cast('$orderDateSt' as datetime) and cast('$orderDateEn' as datetime))";
    } else if ($orderDateType == "ORD") {
        $cond = " and (cast(toh.order_date as datetime) between cast('$orderDateSt' as datetime) and cast('$orderDateEn' as datetime))";
    }
        
    $cmd = "select isnull(abb_prov, '-') as prov
            from [dbo].[transaction_order] toh
            where toh.trash = 0 $cond
            group by abb_prov
            order by abb_prov";
    
//    $cmd = "select ap.ABB_PROV as prov
//                , isnull(stuff((select ', ' + mg.group_name from [dbo].[group_province] gp
//				inner join [dbo].[master_group] mg on mg.group_id = gp.group_id
//				where gp.trash = 0 and mg.trash = 0 and gp.ABB_PROV = ap.ABB_PROV for xml path('') ), 1, 2, ''), '') as title
//            from ADMIN_POINT ap 
//            group by ABB_PROV,PROV_ENAME 
//            order by ap.ABB_PROV asc";
    $result = dbConnection( $cmd );
    echo json_encode($result);
}

function getVehicleGroup(){
    $proviceName = $_POST['proviceName'];
    $cond = "";
    if(!empty($proviceName)){
        $cond .= " and gp.ABB_PROV in (select items from [dbo].[Splitfn] ('$proviceName', ',') )";
    }
    
    $cmd = "select mg.group_id as id, mg.group_name as text
                , isnull(stuff((select ', ' + gp.ABB_PROV from [dbo].[group_province] gp where gp.trash = 0 and gp.group_id = mg.group_id for xml path('') ), 1, 2, ''), '') as title
            from [dbo].[master_group] mg
            where mg.trash = 0 and mg.group_id in (select gp.group_id from [dbo].[group_province] gp where gp.trash = 0 $cond)
            order by text";
    $result = dbConnection( $cmd );
    echo json_encode($result);
}

?>