<link rel="stylesheet" href="application/pages/planning_report/style.css">

<?php
//$path = "\\\\10.192.32.246\\SharedDocument\\";
//$file_path = "\\TH-NMT-IFS10.nmt.local\SharedDocument\PRSPEC\FILE_NAME.pdf";
//$remote_file = substr($file_path, strpos($file_path, "SharedDocument") + strlen("SharedDocument\\"));
//echo $remote_file;
//exit();
?>
<section class="content-header">
    <h1>
        <?php echo $GLOBALS['_lng68']; ?>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> <?php echo $GLOBALS['_lng1']; ?></a></li>
        <li class="active"><?php echo $GLOBALS['_lng68']; ?></li>
    </ol>
</section>
<?php
    include_once( "application/pages/planning_report/script_ui_plan.php" );
    include_once( "application/pages/planning_report/script.php" );
?>

<script src="https://maps.googleapis.com/maps/api/js?libraries=places&key=AIzaSyBGzZVvQEOp0awPxmDLZTOPBcOQAOoh75w&callback=initMap" async defer></script>


<section class="content">
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header">
                    <div class="col-sm-12">
                        <b><?php echo $GLOBALS['_lng64'];?></b>
                        &nbsp;<input type="text" id="plan_filter_start_date" class="form-control form-inline" style="width:110px" onchange="func_st(this.value);" />
                        &nbsp;<b><?php echo strtolower($GLOBALS['_lng63']); ?></b>&nbsp;&nbsp;<input type="text" id="plan_filter_end_date" class="form-control form-inline" style="width:110px" onchange="func_en(this.value);"/>
                        &nbsp;<button type="button" class="btn btn-primary btn-xm btn-inline" onclick="reloadPlan(true)"><i class="fa fa-fw fa-search"></i></button>
                        &nbsp;<label class="block-inline"><input type="checkbox" class="flat-blue cb-inv-checklist" value="verify" checked> <?php echo $GLOBALS['_lng111']; ?></label>
                        

                        <!--&nbsp;&nbsp;<label class="lbl-display-header"><small>( Displaying result <span id="lbl-display-plan">0</span> items )</small></label>-->

                        <div class="pull-right">
                            <?php echo $GLOBALS['_lng50']; ?>&nbsp;&nbsp;<input type="text" class="form-control form-inline" id="inp-plan-magic-search" placeholder="<?php echo $GLOBALS['_lng50']; ?>" style="width: 200px;" />
                        </div>
                    </div>
                </div>
                
                <div class="box-header">    
                    <div class="col-sm-12">
                        <button type="button" class="btn btn-sm" onclick="allSelectionPlan( 'Y' )"><i class="fa fa-fw  fa-check-square-o"></i> <?php echo $GLOBALS['_lng114']; ?></button>
                        <button type="button" class="btn btn-sm" onclick="allSelectionPlan( 'N' )"><i class="fa fa-fw fa-square-o"></i> <?php echo $GLOBALS['_lng115']; ?></button>

                        <div class="pull-right">
                            <button type="button" class="btn btn-sm btn-warning" onclick="verifySelectionPlan()"><i class="fa fa-fw fa-check"></i> <?php echo $GLOBALS['_lng151']; ?></button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="unverifySelectionPlan()"><i class="fa fa-fw fa-close"></i> <?php echo $GLOBALS['_lng152']; ?></button>
                            &emsp;
                            <button type="button" class="btn btn-sm bg-purple" onclick="confirmSelectionPlan()" style="display:none;"><i class="fa fa-fw fa-check"></i> <?php echo $GLOBALS['_lng153']; ?></button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="unconfirmSelectionPlan()" style="display:none;"><i class="fa fa-fw fa-close"></i> <?php echo $GLOBALS['_lng154']; ?></button>
                        </div>
                    </div>
                </div>
                
                <div class="box-body" >
                    <table id="list-plan" class="table table-bordered table-hover"></table>
                </div>
            </div>
        </div>                   
    </div>
</section>

<div class="modal fade" id="modal_plan_item" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" style="width:96%;max-width:1400px;height:82%">
        <div class="modal-content" style="height:100%">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h3 class="modal-title"><?php echo $GLOBALS['_lng244'];?></h3>  
            </div>
            <div class="modal-body" style="height:calc(100% - 130px);overflow-y:auto">
                <table id="tb_plan_item" class="table table-bordered dataTable no-footer">
                    <thead>
                        <tr>
                            <th style="width:6%"><?php echo $GLOBALS['_lng149'];?></th>
                            <th style="width:10%"><?php echo $GLOBALS['_lng65'];?></th>
                            <th style="width:12%"><?php echo $GLOBALS['_lng76'];?></th>
                            <th style="width:8%"><?php echo $GLOBALS['_lng42'];?></th>
                            <th style="width:8%"><?php echo $GLOBALS['_lng77'];?></th>
                            <th style="width:8%"><?php echo $GLOBALS['_lng120'];?></th>
                            <th style="width:26%"><?php echo $GLOBALS['_lng121'];?></th>
                            <th style="width:12%"><?php echo $GLOBALS['_lng78'];?></th>
                            <th style="width:10%"><?php echo $GLOBALS['_lng74'];?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="modal-footer" >
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $GLOBALS['_lng41']; ?></button>
                <button type="button" id="btn_edit_plan_item" class="btn btn-success" onclick="saveEditPlanItem()"><?php echo $GLOBALS['_lng40']; ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal_view_map" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" style="width:60%;height:82%">
        <div class="modal-content" style="height:100%">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
				
                <h3 class="modal-title-map"></h3>  
            </div>
            <div class="modal-body" style="height:calc(98% - 130px);overflow-y:auto">
                <div id="map" style="height:100%;width:100%"></div>
            </div>
            <div class="modal-footer" >
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $GLOBALS['_lng41']; ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal_edit_vehicle" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" style="width:800px;height:82%">
        <div class="modal-content" style="height:100%">
            <div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				
				<h3 class="modal-title"><?php echo $GLOBALS['_lng363'];?></h3>
            </div>
            <div class="modal-body" style="height:calc(98% - 130px);overflow-y:auto">
                <table id="tb_edit_vehicle" class="table table-bordered">
                    <thead>
                        <tr>
                            <th></th>
                            <th><?php echo $GLOBALS['_lng10'];?></th>
							<th><?php echo $GLOBALS['_lng11'];?></th>
                            <th><?php echo $GLOBALS['_lng506'];?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="modal-footer" >
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $GLOBALS['_lng41']; ?></button>
                <button type="button" id="btn_save_edit_vehicle" class="btn btn-success" onclick=""><?php echo $GLOBALS['_lng40']; ?></button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="modal_edit_delivery_date" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" style="width:600px;margin-top:10%;">
        <div class="modal-content">
            <div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				
				<h3 class="modal-title"><?php echo $GLOBALS['_lng244'];?></h3>
            </div>
            <div class="modal-body">
				<div class="row">
					<div class="col-sm-3 text-right"><?php echo $GLOBALS['_lng64'];?></div>
					<div class="col-sm-7"><input type="text" id="txt_delivery_date" class="form-control" /></div>
				</div>
            </div>
            <div class="modal-footer" >
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $GLOBALS['_lng39']; ?></button>
                <button type="button" id="btn_save_edit_delivery_date" class="btn btn-success" onclick=""><?php echo $GLOBALS['_lng40']; ?></button>
            </div>
        </div>
    </div>
</div>