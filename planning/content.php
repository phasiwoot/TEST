<link rel="stylesheet" href="application/pages/planning/style.css">
<link href="resources/plugins/datetimepicker-master/jquery.datetimepicker.css" rel="stylesheet" type="text/css"/>
<script src="resources/plugins/datetimepicker-master/jquery.datetimepicker.js" type="text/javascript"></script>
<script src="resources/plugins/datetimepicker-master/build/jquery.datetimepicker.full.js" type="text/javascript"></script>

<section class="content-header">
    <h1>
        <?php echo $GLOBALS['_lng5']; ?>
        
        <small>
            <input type="radio" name="rad_plan_type" value="normal" checked /> แผนปกติ
            &emsp;<input type="radio" name="rad_plan_type" value="auto" /> อัตโนมัติ
            <button id="btnAutoPlan" class="btn btn-xs bg-yellow" style="display:none" onclick="autoPlan()">Manage</button>
        </small>
    </h1>
    
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> <?php echo $GLOBALS['_lng1']; ?></a></li>
        <li class="active"><?php echo $GLOBALS['_lng5']; ?></li>
    </ol>
</section>
<?php
    include ( "application/pages/planning/variable.php" );
    include ( "application/pages/planning/PlanUI.php" );
    include ( "application/pages/planning/script.php" );
?>
<!--<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDeqaIskoqf8Yogxf1shcngDB4miYx9fDQ&libraries=places"></script>-->
<!--<script src="resources/function/js/google_map.js"></script>-->

<section class="content">
    <div class="row">
        
        <div id="planUI" class="planUI col-xs-12" style="height:600px">
            <div class="planUI-wrapper">
                
                <div class="planUI-area" style="height:110px">
                    <div class="planUI-content" id="order_filter" style="width:65%">
                        <div class="planUI-box">
                            <table style="margin-top:3px" class="col-xl-12 col-lg-12">
								<tr>
									<th style="width:15%"></th>
									<th style="width:10%"></th>
									<th style="width:2%"></th>
									<th style="width:10%"></th>
									<th style="width:15%"></th>
									<th style="width:15%"></th>
									<th style="width:15%"></th>
									<th style="width:5%"></th>
									<!--<th style="width:160px"></th>
									<th style="width:90px"></th>
									<th style="width:18px"></th>
									<th style="width:90px"></th>
									<th style="width:140px"></th>
									<th style="width:130px"></th>
									<th style="width:160px"></th>
									<th style="width:80px"></th>-->
								</tr>
                                <tr>
                                    <td>
                                        <b><select id="sl_order_date_type" class="form-control" style="width:100%">
                                            <option value="ORD"><?php echo $GLOBALS['_lng487'];?></option>
                                            <option value="REQ"><?php echo $GLOBALS['_lng65'];?></option>
                                        </select></b>
                                        <!--<b>&emsp;<?php echo $GLOBALS['_lng487'];?>&nbsp;</b>-->
                                    </td>
                                    <td><input type="text" id="txt_order_date_start" class="form-control" onchange="func_st(this.value);" /></td>

                                    <td><b>&nbsp;-&nbsp;</b></td>
                                    <td><input type="text" id="txt_order_date_end" class="form-control" onchange="func_en(this.value);" /></td>

                                    <td><b>&emsp;<?php echo $GLOBALS['_lng476']; ?>&nbsp;</b></td>
                                    <td>
										<select id="sl_order_type" class="form-control" style="width:100%">
											<option value="0" selected><?php echo $GLOBALS['_lng372'];?></option>
											<option value="1"><?php echo $GLOBALS['_lng488'];?></option>
											<option value="2"><?php echo $GLOBALS['_lng489'];?></option>
											<option value="3"><?php echo $GLOBALS['_lng490'];?></option>
										</select>
										<!--<?php echo $GLOBALS['_lng50'];?>-->
									</td>
									<td>
										<input type="text" id="txt_order_search" class="form-control" placeholder="<?php echo strtolower($GLOBALS['_lng50']);?>.." />
									</td>
                                    <!--<td rowspan="3">
                                        <select class="form-control select2" multiple="multiple" id="txt_provice" data-placeholder="<?php echo $GLOBALS['_lng267'];?>" style="width:180px;height:92px"></select>
                                    </td>-->
                                    <td rowspan="3" style="">
                                        <button type="button" class="btn btn-primary" onclick="reloadOrderGroup()"><i class="fa fa-search"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><b>&emsp;<?php echo $GLOBALS['_lng12'];?></b></td>
                                    <td colspan="3">
                                        <select id="sl_order_contractor" class="form-control" style="width:100%">
                                            <option value=""><?php echo $GLOBALS['_lng114'];?></option>
                                        </select>
                                    </td>
                                    
                                    <td><b>&emsp;<?php echo $GLOBALS['_lng42'];?>&nbsp;</b></td>
                                    <td>
                                        <input type="checkbox" name="cb_order_status" class="iCheck" value="wait" checked />&nbsp;<?php echo $GLOBALS['_lng164'];?>
                                    </td>
									<td rowspan="2">
                                        <select class="form-control select2" multiple="multiple" id="txt_provice" data-placeholder="<?php echo $GLOBALS['_lng267'];?>" style="width:100%;height:32px"></select>
                                    </td>
                                </tr>
                                <tr>
                                    <td><b>&emsp;<?php echo $GLOBALS['_lng491'];?></b></td>
                                    <td colspan="3">
                                        <select id="sl_order_source" class="form-control" style="width:100%">
                                            <option value=""><?php echo $GLOBALS['_lng114'];?></option>
                                        </select>
                                    </td>   
                                    
                                    <td><b>&emsp;</b></td>
                                    <td>
                                        <input type="checkbox" name="cb_order_status" class="iCheck" value="planned" />&nbsp;<?php echo $GLOBALS['_lng167'];?>
                                    </td>
									
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="planUI-content" id="order_format" style="width:35%">
                        <div class="planUI-box-header">
                            <span class="box-title"><?php echo $GLOBALS['_lng460'];?></span>
                        </div>
                        
                        <div class="planUI-box-header" style="left:auto;right:2px">
                            <button id="btn_reset_format" class="btn btn-sm bg-grey" onclick="resetOrderGroupList()"><i class="fa fa-rotate-left"></i> Reset</button>
                        </div>
                        
                        <div class="planUI-box" style="overflow-x: auto">
                            <div id="order_format_panel" style="display: flex">
<!--                                <span class="format-box format-sort selected" data-sort="customer_name" onclick="openOrderGroupList(this, 'customer_name')"><?php echo $GLOBALS['_lng7'];?></span>
                                <span class="format-box"><i class="fa fa-arrow-right"></i></span>
                                
                                <span class="format-box format-sort selected" data-sort="consignment_no" onclick="openOrderGroupList(this, 'consignment_no')"><?php echo $GLOBALS['_lng418'];?></span>
                                <span class="format-box"><i class="fa fa-arrow-right"></i></span>
                                
                                <span class="format-box format-sort empty" data-sort="empty" onclick="openOrderGroupList(this, 'empty')"></span>
                                <span class="format-box"><i class="fa fa-arrow-right"></i></span>
                                
                                <span class="format-box format-sort selected disabled" data-sort="order_no"><?php echo $GLOBALS['_lng4'];?></span>-->
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="planUI-area" style="height:calc(55% - 110px)">
                    <div class="planUI-content" id="order_list">
                        <div class="planUI-box">
                            <div id="checkAllBarOrder">
                                <button id="btnCheckAllOrder" class="btn btn-sm btn-gray" onclick="checkAllOrder(this)"><i class="fa fa-square-o"></i> <?php echo $GLOBALS['_lng372'];?></button>
                            </div>
                            
                            <div class="planUI-item-list">
                                
                            </div>
                        </div>
                        
                        <div class="planUI-box-bar">
                            <div id="lbl_vehicle_load_status" class="planUI-box-status"><span>XXX</span></div>
                            <div id="lbl_vehicle_suggest" class="planUI-box-status"><span>XXX</span></div>
                        </div>
                    </div>
                </div>
                
                <div class="planUI-area" style="width:50%;height:45%">
                    <div class="planUI-content" id="vehicle_filter" style="height:76px">
                        <div class="planUI-box">
							<table style="margin-top:3px" class="col-xl-12 col-lg-12">
								<tr>
									<th style="width:14%"></th>
									<th style="width:14%"></th>
									<th style="width:15%"></th>
									<th style="width:20%"></th>
									<th style="width:20%"></th>
									<th style="width:7%"></th>
									<th style="width:10%"></th>
								</tr>
                            <!--<table style="width:auto;max-width:99%;margin-top:4px">-->
                                <tr>
                                    <td><b>&emsp;<?php echo $GLOBALS['_lng64'];?>&nbsp;</b></td>
                                    <td><input type="text" id="txt_delivery_date" class="form-control" /></td>
                                    
                                    <td><b>&emsp;<?php echo $GLOBALS['_lng12'];?>&nbsp;</b></td>
                                    <td>
                                        <select id="sl_delivery_vehicle_contractor" class="form-control" style="width:100%">
                                            <option value=""><?php echo $GLOBALS['_lng114'];?></option>
                                        </select>
                                    </td>
                                    
                                    <td rowspan="2">
                                        <select class="form-control select2" multiple="multiple" id="txt_vehicle_group" data-placeholder="<?php echo $GLOBALS['_lng469'];?>" style="width:100%;"></select>
                                    </td>   
                                    
                                    <td rowspan="2">
                                        <button type="button" class="btn btn-primary" onclick="reloadVehicleFleet()"><i class="fa fa-search"></i></button>
                                    </td>

                                    <td rowspan="2" style="">
                                        <button type="button" id="btn_add_plan" class="btn btn-warning btn-sm" onclick="checkPlan()"><?php echo $GLOBALS['_lng143'];?></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    
                                    <td><b>&emsp;<?php echo $GLOBALS['_lng22'];?>&emsp;</b></td>
                                    <td>
                                        <select id="sl_delivery_vehicle_type" class="form-control" style="width:100%">
                                            <option value=""><?php echo $GLOBALS['_lng114'];?></option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="planUI-content" id="vehicle_list" style="height:calc(100% - 76px)">
                        <div class="planUI-box">
                            <div class="planUI-item-list"></div>
                        </div>
                        
<!--                        <div class="planUI-box-bar" style="left:50%;width:auto;min-width:1px">
                            <button type="button" id="btn_add_plan" class="btn btn-warning btn-sm planUI-box-status" onclick="checkPlan()"><?php echo $GLOBALS['_lng143'];?></button>
                        </div>-->
                    </div>
                </div>
                
                <div class="planUI-area" style="width:50%;height:45%">
                    <div class="planUI-content" id="route_example">
                        <div class="planUI-box-header"><span class="box-title"><?php echo $GLOBALS['_lng398'];?></span></div>
                        
                        <div class="planUI-box">
                            <div class="planUI-item-list">
                                <div id="route_example_flowchart"></div>
                            </div>
                        </div>
                        
                        <div class="planUI-box-bar" style="left:50%;width:auto;min-width:1px">
                            <button id="btn_edit_plan" class="btn btn-warning btn-sm planUI-box-status" onclick="getRouting()"><?php echo $GLOBALS['_lng244'];?></button>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
    </div>
</section>

<!-------- Modal Request Date ---------->
<div class="modal fade" id="modal_add_delivery_time" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" style="width:400px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><b><?php echo $GLOBALS['_lng143'];?></b></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal">
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group">
                                <label class="col-sm-5 control-label"><b><?php echo $GLOBALS['_lng450'];?>: </b></label>
                                <div class="col-xs-6"><input type="text" id="txt_departure_time" class="form-control" value="" /></div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $GLOBALS['_lng41'];?></button>
                <button type="button" class="btn btn-success" onclick="addPlan()"><?php echo $GLOBALS['_lng272'];?></button>
            </div>
        </div>
    </div>
</div>

<!-------- Modal Order Format ---------->
<div class="modal fade" id="modal_pick_order_group" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" style="width:400px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><b>Order Format</b></h4>
            </div>
            <div class="modal-body" style="padding-left:60px">
                
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $GLOBALS['_lng41'];?></button>
                <button type="button" class="btn btn-success" onclick="pickOrderGroupList()"><?php echo $GLOBALS['_lng207'];?></button>
            </div>
        </div>
    </div>
</div>

<!-------- Modal Delivery Route ---------->
<div class="modal fade" id="modal_add_route_point" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" style="width:800px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title2"><b>Route Point</b></h4>
            </div>
            <div class="modal-body">
                Coming soon!
            </div>
            <div class="modal-footer" >
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $GLOBALS['_lng41'];?></button>
                <button type="button" class="btn btn-success"><?php echo $GLOBALS['_lng207'];?></button>
            </div>
        </div>
    </div>
</div>

<!-------- Modal Delivery Route ---------->
<div class="modal fade" id="modal_edit_route" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog full-height" style="width:96%">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
					<!-- onclick="getDeliveryFleetRoute()" -->
                </button>
                <h4 class="modal-title"><b><?php echo $GLOBALS['_lng386'];?></b></h4>
            </div>
            <div class="modal-body no-padding">
                <div style="padding:15px">
                    <div class="row">
                        <div class="col-xs-7">
                            <table id="tb_edit_route" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width:80px"><?php echo $GLOBALS['_lng149'];?></th>
                                        <th><?php echo $GLOBALS['_lng9'];?></th>
                                        <th style="width:150px"><?php echo $GLOBALS['_lng388'];?></th>
                                        <th style="width:150px"><?php echo $GLOBALS['_lng259'];?></th>
                                        <th style="width:150px"><?php echo $GLOBALS['_lng260'];?></th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>

                        <div class="col-xs-5">
                            <div id="deliveryMap" style="width:100%;min-height:600px"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
				<!-- onclick="getDeliveryFleetRoute()" -->
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $GLOBALS['_lng41'];?></button>
				<button type="button" class="btn btn-warning" onclick="reRoutingAPI()"><i class="fa fa-road"></i> <?php echo $GLOBALS['_lng514'];?></button>
                <button type="button" class="btn btn-warning" onclick="reRouting()"><i class="fa fa-road"></i> <?php echo $GLOBALS['_lng452'];?></button>
                <button type="button" class="btn bg-gray" onclick="resetRouting()"><i class="fa fa-refresh"></i> <?php echo $GLOBALS['_lng494'];?></button>
                <!--<button type="button" class="btn btn-success"><?php echo $GLOBALS['_lng272'];?></button>-->
            </div>
        </div>
    </div>
</div>

<!-------- Modal View Plan ---------->
<div class="modal fade" id="modal_view_plan" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog full-height" style="width:90%;max-width:1300px">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><b><?php echo $GLOBALS['_lng148'];?></b></h4>
            </div>
            <div class="modal-body">
                
            </div>
            <div class="modal-footer" >
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $GLOBALS['_lng41'];?></button>
            </div>
        </div>
    </div>
</div>

<script src="https://maps.googleapis.com/maps/api/js?libraries=places&key=AIzaSyBGzZVvQEOp0awPxmDLZTOPBcOQAOoh75w&callback=initMap" async defer></script>