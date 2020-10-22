<?php session_start();
error_reporting(0);
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>TMS | Transport Managmentment System</title>
        <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, minimum-scale=1, maximum-scale=1"/>
        <meta http-equiv="cache-control" content="no-cache">
        <script src="https://maps.googleapis.com/maps/api/js?libraries=places&key=AIzaSyBGzZVvQEOp0awPxmDLZTOPBcOQAOoh75w" async defer></script>
        
        <link rel="shortcut icon" href="../../../resources/images/favicon.png">
        <link rel="stylesheet" href="./delivery_doc_style.css">
        <script src="../../../resources/bower_components/jquery/dist/jquery.min.js"></script>
        <script src="JsBarcode.all.min.js" type="text/javascript"></script>
    </head>
    <body>
<?php
    include("../../../config/db_connection.php");
    require_once("../../../resources/function/php/sqlsrv_connect.php");
    
    $delivery_no = $_GET['delivery_no'];
    $lang = $_GET['lang'];
    $user_id = $_SESSION['USER_ID'];
    
    $result_lang = dbConnection("select id, isnull([$lang], [th]) as word from [dbo].[sys_language_word] lng
        where lng.id in (4,7,10,11,22,24,25,31,42,47,62,64,65,67,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,120,122,123,150,163,210,373,375,376,377,387,388,417,419,420,421,426)");

    foreach ($result_lang as $lng) {
        eval("\$_lng". $lng['id'] ." = \"". $lng['word'] ."\";");
    }
    
    class deliveryDocument {
        var $deliveryNo;
        
        var $fixHeight;
        var $nowHeight;
        var $nowPage;
        
        var $pageTitleHeight;
        var $deliveryTitleHeight;
        var $orderContentHeight;
        var $shiptoTitleHeight;
        var $shiptoEmptyHeight;
        var $orderItemRowHeight;
        var $orderItemHeaderHeight;
        var $orderItemSummary;
        var $summaryHeight;
        var $remarkHeight;
        var $creatorSign;
        var $approverHeight;
        
        var $strShiptoRow;
        var $strOrderItemRow;
        var $strOrderItemHeader;
        var $strOrderItemFooter;
        
        var $objUser;
        var $objOffice;
        var $objDelivery;
        var $objListShipto;
        var $objRoutePlan;
        
        public function __construct() {
            global $delivery_no;
            
            $this->fixHeight = 1062;
            $this->nowHeight = 0;
            $this->nowPage = 0;
            
            $this->pageTitleHeight = 53; //54
            $this->deliveryTitleHeight = 58; //71
            $this->orderContentHeight = 5;
            $this->shiptoTitleHeight = 51;
            $this->shiptoEmptyHeight = 12;
            $this->orderItemHeaderHeight = 22;
            $this->orderItemRowHeight = 21;
            $this->orderItemSummary = 22;
            $this->summaryHeight = 64;
            $this->remarkHeight = 94;
            $this->creatorSign = 101;
            $this->approverHeight = 92;
            
            $this->strShiptoRow = '';
            $this->strOrderItemRow = '';
            $this->strOrderItemHeader = '';
            $this->strOrderItemFooter = '';
            
            $arrUser = $this->getUser();
            $arrOffice = $this->getOffice();
            $arrShipto = $this->getShipto($delivery_no);
            $arrDelivery = $this->getDelivery($delivery_no);
            $arrRoutePlan = $this->getRoutePlan($delivery_no);
            
            if (!count($arrDelivery)) {
                die( print("ไม่พบข้อมูลใบงาน") );
            }
            
            $this->objUser = $arrUser[0];
            $this->objOffice = $arrOffice[0];
            $this->objListShipto = $arrShipto;
            $this->objDelivery = $arrDelivery[0];
            $this->objRoutePlan = $arrRoutePlan;
            
            unset($arrUser);
            unset($arrOffice);
            unset($arrShipto);
            unset($arrDelivery);
            unset($arrRoutePlan);
        }
        
        public function getUser() {
            global $user_id;
            
            $result = dbConnection("select top 1 [name] as full_name from [sys_user] where id = N'$user_id'");
            return $result;
        }
        
        public function getOffice() {
            $result = dbConnection("select top 1 ms.station_name as station_name
                from [master_station] ms
                inner join [master_station_type] mst on mst.id = ms.station_type
                where ms.trash = 0 and mst.code = 'WH' and ms.id = '4'");
            return $result;
        }
        
        public function getWeightWord_WithoutKG($weightWord) {
            return trim(substr($weightWord, 0, strpos($weightWord, '(')));
        }
        
        public function getDelivery($delivery_no) {
            $result = dbConnection("select top 1 isnull(tdp.delivery_no, '') as delivery_no
                                        , -1 as round_seq
                                        , isnull(convert(nvarchar(10), tdp.delivery_date, 21), '') as delivery_date
                                        , isnull(tdp.vehicle_name, '') as vehicle_name
                                        , isnull(tdp.vehicle_type_name, '') as vehicle_type_name
                                        , isnull(nullif(rtrim(ltrim(tdp.driver_name)), ''), '-') as driver_name
                                        , isnull((select top 1 round(mv.weight, 0) from [master_vehicle] mv where mv.vehicle_id = tdp.vehicle_id), 0) as vehicle_weight
                                        , isnull((select sum(isnull(tdi.item_weight, 0) * tdi.item_qty) from [transaction_delivery_item] tdi where tdi.trash = 0 and tdi.delivery_id = tdp.delivery_id), 0) as loading_weight
                                        , isnull((select count(distinct tdi.order_id) from [transaction_delivery_item] tdi where tdi.trash = 0 and tdi.delivery_id = tdp.delivery_id), 0) as number_of_order
                                        , isnull(( select top 1 ms.station_name
                                            from [master_station] ms
                                            inner join [master_station_type] mst on mst.id = ms.station_type
                                            where ms.trash = 0 and mst.code = 'WH' and ms.id = '4' ), '') as origin_name
                                    from [transaction_delivery] tdp
                                    where tdp.trash = 0 and tdp.delivery_no = N'$delivery_no'
                                    group by tdp.delivery_no, tdp.delivery_date, tdp.delivery_id
                                        , tdp.vehicle_id, tdp.vehicle_name, tdp.vehicle_type_name, tdp.driver_name
                                    order by tdp.delivery_no");
            return $result;
        }
        
        public function getShipto($delivery_no) {
            $result = dbConnection("select row_number() over (order by tdr.route_seq) as nrow
                                        , tdr.delivery_route_id
                                        , isnull(tdr.station_name, '-') as station_name
                                        , concat(tdr.station_name, ' '+ tdr.station_address, (select top 1 isnull(' ' + mca.province,'') + isnull(' ' + mca.district,'') from [dbo].[master_customer] mc left join [dbo].[master_customer_address] mca on mca.customer_code = mc.customer_code where mc.id = tdr.station_id)) as station_address
                                        , tdp.delivery_id as keyId
                                    from [transaction_delivery_route] tdr
                                    inner join [transaction_delivery] tdp on tdp.delivery_id = tdr.delivery_id
                                    
                                    where tdr.trash = 0 and tdp.trash = 0 and tdp.delivery_no = N'$delivery_no'
                                    group by tdp.delivery_id, tdr.delivery_route_id, tdp.delivery_no
                                        , tdr.station_id, tdr.station_code, tdr.station_name, tdr.station_address, tdr.route_seq
                                        --, tdr.plan_in, tdr.plan_out, tdr.route_id
                                    order by tdr.route_seq");
            return $result;
        }
        
        public function getOrderItem($keyId, $routeId) {
            $result = dbConnection("select (select toh.order_no from [dbo].[transaction_order] toh where toh.trash = 0 and toh.order_id = tdi.order_id) as order_no
                                        , tdi.item_line_no as line_no
                                        , isnull(convert(nvarchar(10),(select toh.request_date_end from [dbo].[transaction_order] toh where toh.trash = 0 and toh.order_id = tdi.order_id), 21), '') as request_date
                                        , isnull(convert(nvarchar(5),(select toh.request_date_end from [dbo].[transaction_order] toh where toh.trash = 0 and toh.order_id = tdi.order_id), 8), '') as request_time
                                        , tdi.item_name
                                        , sum(tdi.item_qty) as item_qty
                                        , isnull(tdi.item_unit_name, '') as item_unit
                                        --, round(sum(cast(isnull(tdi.item_weight, 0) as decimal(18,2))), 0) as item_weight
                                        , isnull(cast(tdi.item_weight as decimal(18,2)), 0) as item_weight
                                            , (case when tdi.src_load_type = 'LOAD' then 'Pickup' else 'Delivery' end) as load_type
                                    from [transaction_delivery] tdp
                                    inner join [transaction_delivery_item] tdi on tdi.delivery_id = tdp.delivery_id
                                    inner join [dbo].[transaction_delivery_route] tdr on tdr.delivery_route_id = tdi.src_route_id 
                                    where tdp.trash = 0 and tdr.trash = 0 and tdi.trash = 0 and tdp.delivery_id = N'$keyId' and tdr.delivery_route_id = N'$routeId'
                                    group by tdi.order_id, tdi.item_line_no, tdi.item_name, tdi.item_unit_name, tdi.src_load_type, tdi.item_weight

                                    union

                                    select (select toh.order_no from [dbo].[transaction_order] toh where toh.trash = 0 and toh.order_id = tdi.order_id) as order_no
                                        , tdi.item_line_no as line_no
                                        , isnull(convert(nvarchar(10),(select toh.request_date_end from [dbo].[transaction_order] toh where toh.trash = 0 and toh.order_id = tdi.order_id), 21), '') as request_date
                                        , isnull(convert(nvarchar(5),(select toh.request_date_end from [dbo].[transaction_order] toh where toh.trash = 0 and toh.order_id = tdi.order_id), 8), '') as request_time
                                        , tdi.item_name
                                        , sum(tdi.item_qty) as item_qty
                                        , isnull(tdi.item_unit_name, '') as item_unit
                                        --, round(sum(cast(isnull(tdi.item_weight, 0) as decimal(18,2))), 0) as item_weight
                                        , isnull(cast(tdi.item_weight as decimal(18,2)), 0) as item_weight
                                            , (case when tdi.des_load_type = 'LOAD' then 'Pickup' else 'Delivery' end) as load_type
                                    from [transaction_delivery] tdp
                                    inner join [transaction_delivery_item] tdi on tdi.delivery_id = tdp.delivery_id
                                    inner join [dbo].[transaction_delivery_route] tdr on tdr.delivery_route_id = tdi.des_route_id 
                                    where tdp.trash = 0 and tdr.trash = 0 and tdi.trash = 0 and tdp.delivery_id = N'$keyId' and tdr.delivery_route_id = N'$routeId'
                                    group by tdi.order_id, tdi.item_line_no, tdi.item_name, tdi.item_unit_name, tdi.des_load_type, tdi.item_weight
                                    order by load_type,order_no, request_date, request_time");
            return $result;
        }
        
        public function getRoutePlan($delivery_no) {
            $result = dbConnection("select td.delivery_no
                                        , tdr.station_latitude
                                        , tdr.station_longtitude
                                        , tdr.route_seq
                                        , isnull(nullif(tdr.station_code + ': ','') + nullif(tdr.station_name,''), '') as station_name
                                        , isnull(convert(nvarchar(10), td.delivery_date, 21), '') as delivery_date
                                        , isnull(convert(nvarchar(16), tdr.plan_in_time, 21), '') as plan_in_time
                                        , isnull(convert(nvarchar(16), tdr.plan_out_time, 21), '') as plan_out_time
                                        , isnull((select sum(tdi.item_weight) from [dbo].[transaction_delivery_item] tdi where tdi.trash = 0 and tdi.des_route_id = tdr.delivery_route_id), 0) as item_weight
                                        , isnull(cast(tdr.plan_distance as decimal(18,2)), 0.00) as plan_distance
                                    from [dbo].[transaction_delivery_route] tdr
                                    inner join [dbo].[transaction_delivery] td on td.delivery_id = tdr.delivery_id
                                    where tdr.trash = 0 and td.trash = 0 and td.delivery_no = '$delivery_no'
                                    order by tdr.route_seq");
            return $result;
        }
        
        public function checkNowHeightIsOver() {
            return ($this->fixHeight > $this->nowHeight)? false: true;
        }
        
        public function createSplitter() {
            return $this->createOrderItemFooter() . $this->createPageFooter() . $this->createPageHeader();
        }
        
        public function createPageHeader() {
            global $_lng47;
            global $_lng81; //provider
            global $_lng83;
            global $_lng122;
            global $_lng210;
            
            $this->nowPage++;
            $this->nowHeight += $this->pageTitleHeight;
            //81 83 210
            return '<div class="page">
                <div class="box-row clear">
                    <div class="box-col">
                        <table class="header">
                            <tbody>
                                <tr>
                                    <td style="width:143px;padding-top:0" rowspan="2">
                                        <img class="barcode"></img>
                                    </td>
                                    <td class="center" style="padding-top:0;vertical-align:top"><b>'. $this->objOffice['station_name'] .'</b></td>
                                    <td class="right" style="width:120px;padding-top:0">'. /*$_lng47 .' '.*/ $this->nowPage .'/#MAX_PAGE</td>
                                </tr>
                                <tr>
                                    <td class="right" style="vertical-align:bottom" colspan="2">
                                        <small><b>'. $_lng122 .'</b> '. $this->objUser['full_name'] .'&emsp;<b>'. $_lng210 .'</b> '. (date('Y-m-d')) .'</small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <hr/>';
        }
        
        public function createPageFooter() {
            return '</div>';
        }
        
        public function createDeliveryTitle() {
            global $_lng72;
            global $_lng11;
            global $_lng22;
            //global $_lng31;
            global $_lng64;
            global $_lng67;
            global $_lng75;
            global $_lng80;
            //global $_lng81;
            //global $_lng83;
            //global $_lng210;
            global $_lng375;
            
            global $_lng150;
            global $_lng376;
            global $_lng373;
            
            $this->nowHeight += $this->deliveryTitleHeight;
            
            return '<div class="box-row clear" style="padding-top:4px">
                <div style="float:left;width:38%">
                    <table class="header" style="float:left;width:auto">
                        <tbody>
                            <tr>
                                <td><b>'. $_lng72 .'</b>&emsp;</td>
                                <td>'. $this->objDelivery['delivery_no'] .'</td>
                            </tr>
                            <tr>
                                <td><b>'. $_lng64 .'</b>&emsp;</td>
                                <td>'. $this->objDelivery['delivery_date'] .'</td>
                            </tr>
                            <tr>
                                <td><b>'. $_lng75 .'</b>&emsp;</td>
                                <td>'. $this->objDelivery['number_of_order'] .'</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div style="float:left;width:40%">
                    <table class="header" style="width:auto;margin:auto">
                        <tbody>
                            <tr>
                                <td style="width:30%"><b>'. $_lng80 .'</b>&emsp;</td>
                                <td>'. $this->objDelivery['vehicle_name'] .'</td>
                            </tr>
                            <tr>
                                <td style="width:auto"><b>'. $_lng22 .'</b>&emsp;</td>
                                <td>'. $this->objDelivery['vehicle_type_name'] .'</td>
                            </tr>
                            <tr>
                                <td><b>'. $_lng11 .'</b>&emsp;</td>
                                <td>'. $this->objDelivery['driver_name'] .'</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div style="float:left;width:20%">
                    <table class="header" style="float:right;width:auto">
                        <tbody>
                            <tr>
                                <td><b>'. $this->getWeightWord_WithoutKG($_lng376) .'</b>&emsp;</td>
                                <td class="center">'. number_format(floatval($this->objDelivery['loading_weight']), 0, '.', ',') .' '. $_lng375 .'</td>
                            </tr>
                            <tr>
                                <td><b>'. $this->getWeightWord_WithoutKG($_lng150) .'</b>&emsp;</td>
                                <td class="right">'. number_format(floatval($this->objDelivery['vehicle_weight']), 0, '.', ',') .' '. $_lng375 .'</td>
                            </tr>
                            <tr>
                                <td><b>'. $this->getWeightWord_WithoutKG($_lng373) .'</b>&emsp;</td>
                                <td class="center">'. number_format(floatval($this->objDelivery['vehicle_weight']) + floatval($this->objDelivery['loading_weight']), 0, '.', ',') .' '. $_lng375 .'</td>
                            </tr>                                
                        </tbody>
                    </table>
                </div>
            </div>';
        }
        
        public function createOrderContent() {
            $this->nowHeight += $this->orderContentHeight;
            
            return '<div class="box-row clear" style="padding-top:4px">
                <table class="content">
                    <thead>
                        <tr>
                            <th style="width:15%"></th>
                            <th style="width:15%"></th>
                            <th style="width:35%"></th>
                            <th style="width:10%"></th>
                            <th style="width:12%"></th>
                            <th style="width:13%"></th>
                        </tr>
                    </thead>
                    <tbody>';
        }
        
        public function createOrderItemTitle($shipto) {
            global $_lng7;
            global $_lng42;
            global $_lng71;
            global $_lng76;
            global $_lng77;
            global $_lng73;
            global $_lng74;
            global $_lng78;
            global $_lng79;
            global $_lng123;
            global $_lng375;
            global $_lng426;
            
            $this->nowHeight += $this->shiptoEmptyHeight + $this->shiptoTitleHeight + $this->orderItemHeaderHeight;
            
            if (!$this->checkNowHeightIsOver()) {
                return '<tr><td class="empty" colspan="6" style=""></td></tr>
                    <tr>
                        <td class="group" colspan="6">
                            <table class="header" style="float:left;width:auto">
                                <tbody>
                                    <tr>
                                        <td><b>'. $shipto['nrow'] .'.&nbsp;</b></td>
                                        <td><b>'. $_lng71 .'/'. $_lng426 .'</b>&emsp;</td>
                                        <td>'. $shipto['station_name'] .'</td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td><b>'. $_lng79 .'</b>&emsp;</td>
                                        <td>'. $shipto['station_address'] .'</td>
                                    </tr>                                
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="th">'. $_lng76 /* Orde # */ .'</td>
                        <td class="th">'. $_lng77 /* Line # */ .'</td>
                        <td class="th">'. $_lng73 /* Item */ .'</td>
                        <td class="th">'. $_lng42 /* Status */ .'</td>
                        <td class="th">'. $_lng74 /* Qty */ .'</td>
                        <td class="th">'. $_lng78 /* Weight */ .'</td>
                    </tr>';
                
            } else {
                $this->nowHeight = $this->shiptoTitleHeight + $this->orderItemHeaderHeight;
                
                return $this->createSplitter() . $this->createOrderContent()
                    .'<tr>
                        <td class="group" colspan="6">
                            <b>'. $_lng7 .'</b>&nbsp;&nbsp;'. $shipto['customer_name'] .'
                            <br/><b>'. $_lng123 .'</b>&nbsp;&nbsp;'. $shipto['station_name'] .'
                            <br/><i><small>'. $shipto['station_address'] .'</small></i>
                        </td>
                    </tr>
                    <tr>
                        <td class="th">'. $_lng76 /* Orde # */ .'</td>
                        <td class="th">'. $_lng77 /* Line # */ .'</td>
                        <td class="th">'. $_lng73 /* Item */ .'</td>
                        <td class="th">'. $_lng42 /* Status */ .'</td>
                        <td class="th">'. $_lng74 /* Qty */ .'</td>
                        <td class="th">'. $_lng78 /* Weight */ .'</td>
                    </tr>';
            }
        }
        
        public function createOrderItemRow($shipto, $item) {
            global $_lng375;
            
            $this->nowHeight += $this->orderItemRowHeight + $this->orderItemSummary;
            
            if (!$this->checkNowHeightIsOver()) {
                $this->nowHeight -= $this->orderItemSummary;
                
                return '<tr>
                        <td class="center">'. $item['order_no'] .'</td>
                        <td class="center">'. $item['line_no'] .'</td>
                        <td class="">'. $item['item_name'] .'</td>
                        <td class="">'. $item['load_type'] .'</td>
                        <td class="right">'. number_format($item['item_qty'], 0, '.', ',') .' <small>'. $item['item_unit'] .'</small></td>
                        <td class="right">'. (float)($item['item_weight']) .'</td>
                    </tr>';
            } else {
                $this->nowHeight = $this->orderItemRowHeight;
                
                return $this->createSplitter() . $this->createOrderContent() . $this->createOrderItemTitle($shipto)
                    .'<tr>
                        <td class="center">'. $item['order_no'] .'</td>
                        <td class="center">'. $item['line_no'] .'</td>
                        <td class="">'. $item['item_name'] .'</td>
                        <td class="">'. $item['load_type'] .'</td>
                        <td class="right">'. number_format($item['item_qty'], 0, '.', ',') .' <small>'. $item['item_unit'] .'</small></td>
                        <td class="right">'.(float)($item['item_weight']) .'</td>
                    </tr>';
            }
        }
        
        public function createOrderItemSummary($item, $sum_item_qty, $sum_item_weight) {
            global $_lng375;
            global $_lng417;
            $this->nowHeight += $this->orderItemSummary;

            return '<tr>
                    <td class="center" colspan="4"><b>'. $_lng417 .'</b></td>
                    <td class="right">'. number_format($sum_item_qty, 0, '.', ',') .' <small>'. $item['item_unit'] .'</small></td>
                    <td class="right">'. (float)($sum_item_weight) .'</td>
                </tr>';
        }
        
        public function createOrderItemFooter() {
            return '</tbody>
                    </table>
                </div>';
        }
        
        public function createMileageAndTotalWeight() {
            global $_lng82;
            global $_lng84;
            global $_lng88;
            global $_lng120;
            global $_lng150;
            global $_lng373;
            global $_lng375;
            global $_lng376;
            global $_lng377;
            
            $this->nowHeight += $this->summaryHeight;
            
            return '<div class="box-row clear" style="padding-top:4px">
                <table class="footer" style="width:50%">
                    <thead>
                        <tr>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="center" style="border:1px solid #000"><b>'. $_lng120 .'</b></td>
                            <td class="center" style="border:1px solid #000"><b>'. $_lng377 .'</b></td>
                            <td class="center" style="border:1px solid #000"><b>'. $_lng82 .'</b></td>
                        </tr>
                        <tr>
                            <td class="center" style="border:1px solid #000">'. $_lng84 .'</td>
                            <td class="center" style="border:1px solid #000"></td>
                            <td class="center" style="border:1px solid #000"></td>
                        </tr>
                        <tr>
                            <td class="center" style="border:1px solid #000">'. $_lng88 .'</td>
                            <td class="center" style="border:1px solid #000"></td>
                            <td class="center" style="border:1px solid #000"></td>
                        </tr>
                    </tbody>
                </table>
            </div>';
        }
        
        public function createRemark() {
            global $_lng87;
            
            $this->nowHeight += $this->remarkHeight;
            
            return '<div class="box-row clear" style="padding-top:20px">
                    <table class="header">
                        <tbody>
                            <tr>
                                <td style="width:60px"><b>'. $_lng87 .'&nbsp;</b></td>
                                <td style="vertical-align:bottom;white-space:nowrap;overflow:hidden;border-bottom:1px dotted #000"></td>
                            </tr>
                            <tr>
                                <td colspan="2" style="vertical-align:bottom;white-space:nowrap;overflow:hidden;border-bottom:1px dotted #000;padding-top:10px">&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="vertical-align:bottom;white-space:nowrap;overflow:hidden;border-bottom:1px dotted #000;padding-top:10px">&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>
                </div>';
        }
        
        public function createCreatorSign() {
            global $_lng31;
            //global $_lng81; //provider
            global $_lng83;
            global $_lng122;
            global $_lng210;
            
            $this->nowHeight += $this->creatorSign;
            
            return '<div class="box-row clear" style="padding-top:30px">
                    <table class="header" style="float:right;width:auto">
                        <tbody>
                            <tr>
                                <td style="width:84px;vertical-align:bottom"><b>'. $_lng122 .'</b></td>
                                <td style="vertical-align:bottom">'. $_lng31 .'&nbsp;</td>
                                <td class="center" style="width:180px;word-wrap:break-word;white-space:nowrap;overflow:hidden;border-bottom:1px dotted #000">'. $this->objUser['full_name'] .'</td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td style="vertical-align:bottom">'. $_lng83 .'&nbsp;</td>
                                <td style="word-wrap:break-word;white-space:nowrap;overflow:hidden;border-bottom:1px dotted #000;padding-top:24px"></td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td style="vertical-align:bottom">'. $_lng210 .'&nbsp;</td>
                                <td style="word-wrap:break-word;white-space:nowrap;overflow:hidden;border-bottom:1px dotted #000;padding-top:24px"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>';
        }
        
        public function createApprover() {
            global $_lng7;
            global $_lng11;
            global $_lng85;
            global $_lng86;
            
            return '<div id="box-bottom">
                <div id="box-bottom-content" class="box-row">
                    <table class="footer">
                        <thead>
                            <tr>
                                <th style="width:159px"></th>
                                <th style="width:auto"></th>
                                <th style="width:159px"></th>
                                <th style="width:auto"></th>
                                <th style="width:159px"></th>
                                <th style="width:auto"></th>
                                <th style="width:159px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="height:70px;border:1px solid #000"></td>
                                <td></td>
                                <td style="height:70px;border:1px solid #000"></td>
                                <td></td>
                                <td style="height:70px;border:1px solid #000"></td>
                                <td></td>
                                <td style="height:70px;border:1px solid #000"></td>
                            </tr>
                            <tr>
                                <td class="center" style="border:1px solid #000"><b>'. $_lng85 .'</b></td>
                                <td></td>
                                <td class="center" style="border:1px solid #000"><b>'. $_lng11 .'</b></td>
                                <td></td>
                                <td class="center" style="border:1px solid #000"><b>'. $_lng7 .'</b></td>
                                <td></td>
                                <td class="center" style="border:1px solid #000"><b>'. $_lng86 .'</b></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>';
        }
        
        public function headMap(){
            global $_lng421;
            return '<div class="box-row clear" style="padding-top:4px;" ><p style="font-size: 15px;text-align: center; font-weight: bold;">'. $_lng421 .'</p></div>';
        }
        
        public function divMaps(){
            return '<div class="box-row clear" style="padding-top:4px">
                    <div class="col-md-5 col-sm-12" style="height:380px;width: 100%;" id="modal-map"></div>
                </div>
                <script>$(function () { mapRoute(); });</script>';
        }
        
        public function tableMap(){
            global $_lng64;
            global $_lng71;
            global $_lng78;
            global $_lng387;
            global $_lng388;
            global $_lng419;
            global $_lng420;
            global $_lng426;
            
            $tb = '<div class="box-row clear" style="padding-top:4px">
                    <table style="width: 100%;margin-top:10px" border="1">
                        <thead>
                            <tr style="border-bottom:2pt solid black;">
                                <th style="width: 5%;">'. $_lng387 .'</th>
                                <th style="width: 20%;">'. $_lng71 .'/'. $_lng426 .'</th>
                                <th style="width: 10%;">'. $_lng64 .'</th>
                                <th style="width: 10%;">'. $_lng419 .'</th>
                                <th style="width: 10%;">'. $_lng420 .'</th>
                                <th style="width: 10%;">'. $_lng78 .'</th>
                                <th style="width: 10%;">'. $_lng388 .'</th>
                            <tr>
                        </thead><tbody>';
            foreach ($this->objRoutePlan as $key=> $rp){
                $tb .= '<tr>
                            <td>'. ($key+1) .'</td>
                            <td>'. $rp['station_name'] .'</td>
                            <td>'. $rp['delivery_date'] .'</td>
                            <td>'. $rp['plan_in_time'] .'</td>
                            <td>'. $rp['plan_out_time'] .'</td>
                            <td>'. $rp['item_weight'] .'</td>
                            <td>'. (float)($rp['plan_distance']) .'</td>
                                        
                        </tr>';
            }
            $tb .= '</tbody></table></div>';
            return $tb;
        }
        
        public function createMap(){
            $pageWriter = $this->createPageFooter() . $this->createPageHeader();
            
            $pageWriter .= $this->headMap();
            $pageWriter .= $this->divMaps();
            $pageWriter .= $this->tableMap();
            
            $pageWriter = preg_replace('/#MAX_PAGE/', $this->nowPage, $pageWriter);
            echo $pageWriter;
        }

        public function createDocument() {
            $pageWriter = $this->createPageHeader() . $this->createDeliveryTitle();
            $pageWriter .= $this->createOrderContent();
            
            foreach ($this->objListShipto as $ishipto => $shipto) {
                $keyId = $shipto['keyId'];
                $routeId = $shipto['delivery_route_id'];
                
                $arrOrderItem = $this->getOrderItem($keyId, $routeId);
                
                $pageWriter .= $this->createOrderItemTitle($shipto);
                
                $sum_item_qty = 0;
                $sum_item_weight = 0;
                
                foreach ($arrOrderItem as $item) {
                    $sum_item_qty += intval($item['item_qty']);
                    $sum_item_weight += floatval($item['item_weight']);
                    //$sum_total_item_weight += floatval($item['item_weight']);
                    
                    $pageWriter .= $this->createOrderItemRow($shipto, $item);
                }
                
                $pageWriter .= $this->createOrderItemSummary($arrOrderItem[0], $sum_item_qty, $sum_item_weight);
                
            } // End loop
            
            $pageWriter .= $this->createOrderItemFooter();
            
            $this->nowHeight += $this->summaryHeight + $this->remarkHeight + $this->creatorSign + $this->approverHeight;
            
            if ($this->checkNowHeightIsOver()) {
                $pageWriter .= $this->createPageFooter() . $this->createPageHeader();
            }
            
            
            $pageWriter .= $this->createMileageAndTotalWeight() . $this->createRemark() . $this->createCreatorSign() . $this->createApprover() . $this->createPageFooter();
            
            $pageWriter = preg_replace('/#MAX_PAGE/', $this->nowPage, $pageWriter);
            echo $pageWriter;
        }
    }
    
?>
        <?php
             global $_lng24;
             global $_lng25;
             global $_lng163;
        ?>
        
        <div class="book">
            <div id="tb_config" class="box-row" style="padding-top:20px">
                <table class="header">
                    <tbody>
                        <tr>
                            <td class="center">
                                <select onchange="changeLanguage(this.value)">
                                    <option <?php echo ($lang=='eng')? 'selected': '';?> value="eng"><?php echo $_lng24; ?></option>
                                    <option <?php echo ($lang=='th')? 'selected': '';?> value="th"><?php echo $_lng25; ?></option>
                                    <!--<option <?php echo ($lang=='lao')? 'selected': '';?> value="lao">ลาว</option>-->
                                </select>

                                <button onclick="printDocument()"><?php echo $_lng163; ?></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <?php
                $deliveryDoc = new deliveryDocument();
                $deliveryDoc->createDocument();
                $deliveryDoc->createMap();
                
            ?>
            
        </div>
        
    </body>
</html>

<script type="text/javascript">
    $(function () {
        $('.barcode').JsBarcode("<?php echo $delivery_no;?>", {width: 1, height: 28, fontSize: 10, displayValue: true, textMargin: 0, marginTop: 2, marginBottom: 0, marginLeft: 0, marginRight: 0});
        //$('.barcode').show();
    });
        
    function changeLanguage(language) {
        window.location.href = window.location.origin + window.location.pathname +'?lang='+ language +'&delivery_no=<?php echo $delivery_no;?>';
    }
    
    function printDocument() {
        window.print();
    }
    
    function mapRoute(){
        var delivery_no = '<?php echo $delivery_no; ?>'; 
         
        $.ajax({
            url: "service.php",
            data: {"func": "getRoute" ,"deliveryNo":delivery_no},
            type: "post",
            success: function (respTxt) {
                if(respTxt !==null){
                    var data = eval(respTxt);
                    var drop=[];
                    $.each(data, function (i, v) {
                        drop.push({latitude:v.station_latitude,longitude:v.station_longtitude,shipto_seq:v.route_seq}); 
                    });
                    
                    initMadalMap();
                    drawGoogleRoute(drop,modal_map);
                }
            }
        });
    }
</script>

<script>
    var directionsService ;
    var directionsDisplay ;
    var modal_map = null;
    var markers = [];
    function initMadalMap() {
        modal_map = new google.maps.Map(document.getElementById('modal-map'), {
            center: {lat: 13.6767685, lng: 100.60134},
            zoom: 6
        });
        directionsService=null;
        directionsDisplay=null;
        directionsService=new google.maps.DirectionsService;
        directionsDisplay = new google.maps.DirectionsRenderer;
    }
    function drawGoogleRoute(data,map_){

        var oData = eval(data);
        
        directionsService = new google.maps.DirectionsService;
        directionsDisplay = new google.maps.DirectionsRenderer;
        
        var origins  = null, destinations = null, waypoints = new Array(), groupMarkerFromWaypoints = [];
        if(oData.length){
            origins = new google.maps.LatLng(Number(oData['0'].latitude), Number(oData['0'].latitude));
            
            if(oData.length > 1){
                destinations = new google.maps.LatLng(Number(oData[oData.length-1].latitude), Number(oData[oData.length-1].latitude));
            }
            
            for(var cntPlace = 0; cntPlace < oData.length; cntPlace++){
                var aWaypoint = {}, aWaypointLat, aWaypointLon;
                aWaypointLat = Number(oData[cntPlace].latitude);
                aWaypointLon = Number(oData[cntPlace].longitude);
                aWaypoint.location = new google.maps.LatLng(aWaypointLat, aWaypointLon);
                aWaypoint.stopover = true;
                waypoints.push(aWaypoint);
                
                var findExistPlace = groupMarkerFromWaypoints.filter(function (o){
                    return o.latitude == aWaypointLat && o.longitude == aWaypointLon;
                });
                
                if(!findExistPlace.length){
                    var GroupWaypointTitle = oData.filter(function (o){
                        return Number(o.latitude) === aWaypointLat && Number(oData[cntPlace].longitude) === aWaypointLon;
                    }).map(function (o){ return o.shipto_seq; }).sort().join(',');
                    
                    var WaypointMarker = new google.maps.Marker({
                        position: aWaypoint.location
                        , map: map_
                        , draggable: false
                        , animation: false
                        , label: {text:GroupWaypointTitle, color:'#fff', fontSize:"14px"}
                            //, icon: path+ 'img/truck_32.png'
                            , title: GroupWaypointTitle
                        });
                    markers.push(WaypointMarker);
                    groupMarkerFromWaypoints.push({latitude: aWaypointLat, longitude: aWaypointLon});
                    
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
                console.log(status);
                if (status === 'OK'){
                    directionsDisplay.setDirections(response);
                }else{
                    window.alert('Directions request failed due to ' + status);
                }
            }
            );
            
            directionsDisplay.setMap(map_);
            directionsDisplay.setOptions({suppressMarkers: true});
        }
    }
</script>