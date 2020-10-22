<?php session_start(); ?>
<script>
var $path = "application/pages/planning_report/service.php";
var hduser = <?php echo $_SESSION["USER_ID"]; ?>;
var hdcontractor = '<?php echo join(',', $_SESSION["USER_CONTRACTOR"]);?>';
var oTbPlan = null;
var viewPlan = null;
var filtDateStart = null;
var filtDateEnd = null;
var filtStatus = 'verify';
var map = null;
var markers = [];
var directionsService = null;
var directionsDisplay = null;
var title_drop = [];

function initial(){
    var newDate = new Date();
    var dateFormat = newDate.yyyymmdd();
    filtDateStart = dateFormat;
    filtDateEnd = dateFormat;
    $('#plan_filter_start_date').val(filtDateStart).datepicker();
    $('#plan_filter_end_date').val(filtDateEnd).datepicker();
    $('#plan_filter_end_date').datepicker('setStartDate', new Date(filtDateStart));
    
	createDatatable();
    
    $("input[type='checkbox'].flat-blue").iCheck( {
        checkboxClass: 'icheckbox_flat-blue',
        radioClass   : 'iradio_flat-blue'
    } );
    
    /*var countdown1 = null, countdown2 = null;
    $("#inp-plan-magic-search").on( "keyup", function ( e ) {
        var txtSearch = $.trim( this.value );
        var listInv = $("#tb-plan");
        if ( listInv.length ) {
            window.clearTimeout( countdown1 );
            $(".box-js-loading", listInv).remove();
            listInv.prepend( "<div class='box-js-loading'><img src='resources/images/spinner-1s-32px.gif' style='height:10px;' /> "+ "<?php echo $GLOBALS['_lng51']; ?>" +"</div>" ).find( "section.invoice" ).addClass( "hiddenInvoiceListSearch" );
            countdown1 = window.setTimeout( function () {
                if ( txtSearch.length ) {
                    $("section.invoice *:contains('"+ txtSearch +"')", listInv).closest( "section.invoice" ).removeClass( "hiddenInvoiceListSearch" );
                } else {
                    $("section.invoice", listInv).removeClass( "hiddenInvoiceListSearch" );
                }
                $("#lbl-display-plan").html( $("section.invoice:not(.hiddenInvoiceListSearch)", listInv).length.toLocaleString() );
                listInv.children( ":eq(0)" ).remove();
                window.clearTimeout( countdown1 );
                countdown1 = null;
            }, 1400 );
        } else {
            $("#lbl-display-verify-invoice").html( 0 );
        }
    } );*/
    
   
    defineAjaxToElementOrCallFunctionLibrary();
    getPlan();
    //initMap();
}

function defineAjaxToElementOrCallFunctionLibrary(){
    viewPlan = new PlanListView({
        elementId: "list-plan",
        dataHead: [],
        data: [],
        dataType: "PLAN",
        column: [
            { title: "<?php echo $GLOBALS['_lng149'];?>", width: "5%",
                render: function (nRow, rowData, nCol, colData) {
                    return nRow + 1;
                }
            },
            { title: "<?php echo $GLOBALS['_lng65'];?>", data: "request_date", width: "8%" },
            { title: "<?php echo $GLOBALS['_lng418'];?>", data: "consignment_no", width: "10%" },
            { title: "<?php echo $GLOBALS['_lng76'];?>", data: "order_no", width: "10%" },
            { title: "<?php echo $GLOBALS['_lng7'];?>", data: "customer_name", width: "10%" },
            { title: "<?php echo $GLOBALS['_lng77'];?>", data: "item_line_no", width: "7%" },
            { title: "<?php echo $GLOBALS['_lng121'];?>", data: "item_name", width: "15%" },
            { title: "<?php echo $GLOBALS['_lng78'];?>", data: "item_weight", type: "decimal", width: "9%",
                render: function (nRow, rowData, nCol, colData) {
                    return number_format(rowData.item_weight, 2, 2);
                }
            },
            /*{ title: "<?php echo $GLOBALS['_lng296'];?>", data: "item_width", type: "decimal", width: "9%",
                render: function (nRow, rowData, nCol, colData) {
                    return number_format(rowData.item_width, 2, 2);
                }
            },
            { title: "<?php echo $GLOBALS['_lng297'];?>", data: "item_length", type: "decimal", width: "9%",
                render: function (nRow, rowData, nCol, colData) {
                    return number_format(rowData.item_length, 2, 2);
                }
            },
            { title: "<?php echo $GLOBALS['_lng298'];?>", data: "item_height", type: "decimal", width: "9%",
                render: function (nRow, rowData, nCol, colData) {
                    return number_format(rowData.item_height, 2, 2);
                }
            },  */
            /*{ title: "<?php echo $GLOBALS['_lng74'];?>", data: "item_qty", type: "number", width: "8%",
                render: function (nRow, rowData, nCol, colData) {
                    return number_format(rowData.item_qty, 0, 0) +' <small>'+ rowData.item_unit_name +'</small>';
                }
            },*/
            { title: "<?php echo $GLOBALS['_lng439'];?>", data: "modified_date", width: "9%"},
            { title: "<?php echo $GLOBALS['_lng440'];?>", data: "modified_by", width: "9%"}
        ],
        afterSectionRender: function ( nRow, data, node ) {
            $("input[type='checkbox'].flat-blue", node).iCheck( {
                checkboxClass: 'icheckbox_flat-blue',
                radioClass   : 'iradio_flat-blue'
            } );
        }
    });
    
    var countdown2 = null;    
    $("#inp-plan-magic-search").on( "keyup", function ( e ) {
        //var txtSearch = $.trim( this.value );
        var listInv = $("#list-plan");
        if ( listInv.length ) {
            window.clearTimeout( countdown2 );
            countdown2 = window.setTimeout( function () {
                //reloadInvoice();
                filtDateStart = $('#plan_filter_start_date').val();
                filtDateEnd = $('#plan_filter_end_date').val();
                //filtStatus = $.map($('.cb-inv-checklist:checked').toArray(), function (o){return o.value;}).join(',');
                oTbPlan.ajax.reload(null, true);
                
                window.clearTimeout( countdown2 );
                countdown2 = null;
            }, 1400 );
        } else {
            $("#lbl-display-plan").html( 0 );
        }
    } );
}

function func_st(st){
	
	
	$('#plan_filter_end_date').datepicker('setStartDate', new Date(st));
	$('#plan_filter_start_date').datepicker("hide");
	var en = $('#plan_filter_end_date').datepicker().val();
	if(st>en){
		$('#plan_filter_end_date').datepicker('setDate', new Date(st));
	}
	$('#plan_filter_end_date').datepicker("show");
}

function func_en(en){
	$('#plan_filter_end_date').datepicker("hide");
}



function getPlan(){
    oTbPlan = $("#list-plan").DataTable({
        language: {
            "emptyTable": "<?php echo $GLOBALS['_lng61'];?>",
            "info": "<?php echo $GLOBALS['_lng53'];?>",
            "infoEmpty": "<?php echo $GLOBALS['_lng56'];?>",
            "infoFiltered": "<?php echo $GLOBALS['_lng55'];?>",
            "infoPostFix": "",
            "thousands": ",",
            "lengthMenu": "<?php echo $GLOBALS['_lng54'];?>",
            "processing": "<?php echo $GLOBALS['_lng51'];?>",
            "search": "<?php echo $GLOBALS['_lng50'];?>",
            "zeroRecords": "<?php echo $GLOBALS['_lng52'];?>",
            "paginate": {
                "first": "",
                "last": "",
                "next": "<?php echo $GLOBALS['_lng59'];?>",
                "previous": "<?php echo $GLOBALS['_lng60'];?>"
            }
        },
        pagingType: "full_numbers",
		pageLength: DTPageLength,
        lengthMenu: DTPageOption,
       scrollY: ($(".content-wrapper").outerHeight() - 380) + "px",
        scrollCollapse: false,
 //        scrollX: true,
        /*fixedColumns: {
            "iLeftColumns": 1
            //, "iRightColumns": 1
        },*/
        columnDefs: [
            {"targets": [7], "orderable": false}
            , {"targets": [7, 8], className: "dt-body-center"}
        ],
        columns: [
            {"title": "#", width: "4%", "data": "row_number"}
            , {"title": "<?php echo $GLOBALS['_lng423'];?>", width: "15%", "data": "delivery_no",
                render: function (nRow, nCol, rowData, colData) {
                    var plan_status = Number(rowData.plan_status);
                    var checkboxRow = "";
                    if (plan_status === 0) {
                        checkboxRow = "<label class='block-inline'><input type='checkbox' class='flat-blue cb_plan' value='"+ rowData.delivery_no +"'></label>&emsp;";
                    } else if (plan_status === 1) {
                        checkboxRow = "<div style='display:inline-block;position:relative;'><label><input type='checkbox' class='flat-blue cb_plan' value='"+ rowData.delivery_no +"'></label></div>&emsp;";

                    } else {
                        checkboxRow = "<div style='display:inline-block;position:relative;'><label><input type='checkbox' class='flat-blue' disabled></label></div>&emsp;";

                    } 
                    
                    return checkboxRow +  rowData.delivery_no;
                }
            }
            
            , {"title": "<?php echo $GLOBALS['_lng64']; ?>", width: "10%", "data": "delivery_date",
				render: function (nRow, nCol, rowData, colData) {
					var plan_status = Number(rowData.plan_status);
					if (plan_status === 0) {
						return "<a href='#' onclick='openEditDeliveryDate(\""+ rowData.delivery_id +"\")'><i class='fa fa-calendar'></i>&nbsp;&nbsp;"+ rowData.delivery_date +'</a>';
					}
					
                    return rowData.delivery_date;
                }
			}
            , {"title": "<?php echo $GLOBALS['_lng12']; ?>", width: "15%", "data": "contractor_name"}
            , {"title": "<?php echo $GLOBALS['_lng11']; ?>", width: "15%", "data": "driver_name"}
            , {"title": "<?php echo $GLOBALS['_lng22']; ?>", width: "12%", "data": "vehicle_type_name"}
            , {"title": "<?php echo $GLOBALS['_lng10']; ?>", width: "12%", "data": "vehicle_name",
                render: function (nRow, nCol, rowData, colData) {
                    var btn = "<a href='#' onclick='openEditVehicle(\""+ rowData.delivery_id +"\", \""+ rowData.vehicle_id +"\")'><i class='fa fa-truck'></i>&nbsp;&nbsp;"+ rowData.vehicle_name +'</a>';
                    return btn;
                }
            }
            , {"title": "<?php echo $GLOBALS['_lng422'];?>", width: "10%",
                render: function (nRow, nCol, rowData, colData) {
                    var btn = "<button class='btn btn-inline btn-info btn_delivery_document' onclick='printDeliveryDocument(\""+ rowData.delivery_no +"\")'><i class='fa fa-print'></i> "+ "<?php //echo $GLOBALS['_lng163'];?>" +"</button>";
                    btn += "&nbsp;&nbsp;<button class='btn btn-inline btn-info btn_view_map' onclick='viewMap(\""+ rowData.delivery_no +"\")'><i class='fa fa-map-o'></i> "+ "<?php //echo $GLOBALS['_lng163'];?>" +"</button>";
                    return btn;
                }
            }
            , {"title": "<?php echo $GLOBALS['_lng42'];?>", width: "7%",
                render: function (nRow, nCol, rowData, colData) {
                    var plan_status = Number(rowData.plan_status);
                    var plan_status_tag = "<?php echo $GLOBALS['_lng164']; ?>";               
                    if (plan_status === 1) {
                        plan_status_tag = "<?php echo $GLOBALS['_lng165']; ?>";
                    } else if (plan_status === 100) {
                        plan_status_tag = "<?php echo $GLOBALS['_lng101']; ?>";
                    }
                    return plan_status_tag;
                }
            }
            
        ],
//        searchDelay: 1200,
        processing: true,
        autoWidth: false,
        serverSide: true,
        deferRender: true,
        destroy : true,
        lengthChange: true,
        paging: true,
        ordering: true,
        info: true,
        searching: false,
        ajax: {
            url: $path,
            type: 'post',
            dataType: 'json',
            data: {
                'func': 'getPlan',
                'hduser': hduser,
                'hdcontractor': hdcontractor,
                'search': function(){return $("#inp-plan-magic-search").val();},
                //'dateType': function(){return filtDateType;},
                'dateStart': function(){return filtDateStart;},
                'dateEnd': function(){return filtDateEnd;},
                'status': function(){return filtStatus;}
            }
            
        },
        
        /*createdRow: function(row, data, dataIndex, cell) {
            //viewOrder.setData(data);
            
            //$(row).html(viewOrder.getView());
            
        },*/
        
        rowCallback: function(row, data, displayNum, displayIndex, dataIndex) {
            //var count_order_planned = Number(data.count_order_planned);
            
            /*if (count_order_planned !== 0) {
                $(row).addClass("planned");
                $("td:eq(0)", row).html(null);
                
                $(row).on("dblclick", function () {
                    open_plan_truck_detail('truck', dataIndex);
                });
            }*/
            var bgcolor = '';
            if (data.plan_status === 0) {
                bgcolor = "bg-blue";
            } else if (data.plan_status === 1) {   
                 bgcolor_row = "bg-orange-new";
				bgcolor = "bg-orange";
				$(row).addClass(bgcolor_row);
            } else if (data.plan_status === 100) {
				 bgcolor_row = "bg-green-new";
                bgcolor = "bg-green";
				$(row).addClass(bgcolor_row);
            }
			 $("td:eq(8)",row).addClass(bgcolor);
			 
        },
        drawCallback: function( settings ) {
        
            $("#"+ settings.sTableId +" input[type='checkbox'].flat-blue").iCheck( {
                checkboxClass: 'icheckbox_flat-blue',
                radioClass   : 'iradio_flat-blue'
            } );

            $("#tb_plan_truck input[type='checkbox'].cb_vehicle").on("ifClicked", function (e) {
                clear_tick_checkbox(this);
            });
            
            $('#list-plan>tbody>tr').off('click').on('click', 'td', function () {
                var tr = $(this).closest('tr');
                var row = oTbPlan.row( tr );
                
                if ($.inArray($(this).index(), [2,6,7]) > -1) {
                    return;
                }
                
                if ( row.child.isShown() ) {
                    // This row is already open - close it
                    row.child.hide();
                    tr.removeClass('shown');
                }
                else {
                    // Open this row
                    viewPlan.setData(row.data());
                    row.child( viewPlan.getView() ).show();
                    tr.addClass('shown');
                }
            });
            
        }
    });
    
    
}


function reloadPlan(clearSts) {
    filtDateStart = $('#plan_filter_start_date').val();
    filtDateEnd = $('#plan_filter_end_date').val();
    filtStatus = $.map($('.cb-inv-checklist:checked').toArray(), function (o){return o.value;}).join(',');
    
    if (clearSts) {
        $("#inp-plan-magic-search").val( null );
    }
    oTbPlan.ajax.reload(null, false);
    //getPlan();
}

function allSelectionPlan( checked ) {
    if ( checked === "Y" ) {
        $("#list-plan input.cb_plan:not(:checked)").parent().click();
    } else {
        $("#list-plan input.cb_plan:checked").parent().click();
    }
    
}

function verifySelectionPlan() {
    if (!$("#list-plan input.cb_plan:checked").length) {
        alert( "<?php echo $GLOBALS['_lng157']; ?>!" );
        return false;
    }
    
    var confirmation = confirm( "<?php echo $GLOBALS['_lng155']; ?>" );
    if ( confirmation ) {
        var deliveryNoList = $.map( $("#list-plan input.cb_plan:checked").toArray(), function ( o ){ return o.value; } ).join( "," );

        $.ajax( {
            url: $path,
            data: {func: "verifyPlan", hduser: hduser, deliveryNo: deliveryNoList},
            type: "post",
            success: function ( respTxt ) {
                var response = eval( respTxt );
                
                var msgListY = $.map(response.filter(function (o){ return o.status == 'Y'; }), function (o){ return o.message; }).join('\n');
                var msgListN = $.map(response.filter(function (o){ return o.status == 'N'; }), function (o){ return o.message; }).join('\n');
                //var msgList = response.map(function (o){ return o.message; }).join('\n');
                //alert( msgListY );

                if (msgListY.length) {
                    alert( '____________ <?php echo $GLOBALS['_lng108']; ?> ____________\n'+ msgListY );
                }

                if (msgListN.length) {
                    alert( '____________ <?php echo $GLOBALS['_lng109']; ?> ____________\n'+ msgListN );
                }

                reloadPlan(false);
            }
        } );
    }
}

function unverifySelectionPlan() {
    if (!$("#list-plan input.cb_plan:checked").length) {
        alert( "<?php echo $GLOBALS['_lng157']; ?>!" );
        return false;
    }
    
    var confirmation = confirm( "<?php echo $GLOBALS['_lng156']; ?>" );
    if ( confirmation ) {
        checkBeforeUnverifyPlan(function(){
            var deliveryNoList = $.map( $("#list-plan input.cb_plan:checked").toArray(), function ( o ){ return o.value; } ).join( "," );
            $.ajax( {
                url: $path,
                data: {func: "unverifyPlan", hduser: hduser, deliveryNo: deliveryNoList},
                type: "post",
                success: function ( respTxt ) {
                    var response = eval( respTxt );

                    var msgListY = $.map(response.filter(function (o){ return o.status == 'Y'; }), function (o){ return o.message; }).join('\n');
                    var msgListN = $.map(response.filter(function (o){ return o.status == 'N'; }), function (o){ return o.message; }).join('\n');
                    //var msgList = response.map(function (o){ return o.message; }).join('\n');
                    //alert( msgListY );

                    if (msgListY.length) {
                        alert( '____________ <?php echo $GLOBALS['_lng108']; ?> ____________\n'+ msgListY );
                    }

                    if (msgListN.length) {
                        alert( '____________ <?php echo $GLOBALS['_lng109']; ?> ____________\n'+ msgListN );
                    }

                    reloadPlan(false);
                }
            } );
        });
    }
}

function checkBeforeUnverifyPlan(callback) {
    var deliveryNoList = $.map( $("#list-plan input.cb_plan:checked").toArray(), function ( o ){ return o.value; } ).join( "," );
    $.ajax( {
        url: $path,
        data: {func: "checkBeforeUnverifyPlan", hduser: hduser, deliveryNo: deliveryNoList},
        type: "post",
        success: function ( respTxt ) {
            var response = eval( respTxt );

            var msgListN = $.map(response.filter(function (o){ return o.status == 'N'; }), function (o){ return o.message; }).join('\n');
            
            if (msgListN.length) {
                try {
                    var confirmation = confirm( "แผนเหล่านี้ได้รับการ Sync ข้อมูลแล้ว ยืนยันที่จะ Unverify หรือไม่?\n" + msgListN );
                    if ( confirmation ) {
                        if (typeof callback == "function") {
                            callback();
                        }
                    }
                } catch(err) {
                    console.error(err);
                }
            } else {
                callback();
            }
        }
    } );
}

function confirmSelectionPlan() {
    if (!$("#list-plan input.cb_plan:checked").length) {
        alert( "<?php echo $GLOBALS['_lng157']; ?>!" );
        return false;
    }
    
    var confirmation = confirm( "<?php echo $GLOBALS['_lng158']; ?>" );
    if ( confirmation ) {
        var deliveryNoList = $.map( $("#list-plan input.cb_plan:checked").toArray(), function ( o ){ return o.value; } ).join( "," );
        $.ajax( {
            url: $path,
            data: {func: "confirmPlan", hduser: hduser, deliveryNo: deliveryNoList},
            type: "post",
            success: function ( respTxt ) {
                var response = eval( respTxt );

                var msgListY = $.map(response.filter(function (o){ return o.status == 'Y'; }), function (o){ return o.message; }).join('\n');
                var msgListN = $.map(response.filter(function (o){ return o.status == 'N'; }), function (o){ return o.message; }).join('\n');
                //var msgList = response.map(function (o){ return o.message; }).join('\n');
                //alert( msgListY );

                if (msgListY.length) {
                    alert( '____________ <?php echo $GLOBALS['_lng108']; ?> ____________\n'+ msgListY );
                }

                if (msgListN.length) {
                    alert( '____________ <?php echo $GLOBALS['_lng109']; ?> ____________\n'+ msgListN );
                }

                reloadPlan(false);
            }
        } );
    }
}

function unconfirmSelectionPlan() {
    if (!$("#list-plan input.cb_plan:checked").length) {
        alert( "<?php echo $GLOBALS['_lng157']; ?>!" );
        return false;
    }
    
    var confirmation = confirm( "<?php echo $GLOBALS['_lng159']; ?>" );
    if ( confirmation ) {
        var deliveryNoList = $.map( $("#list-plan input.cb_plan:checked").toArray(), function ( o ){ return o.value; } ).join( "," );

        $.ajax( {
            url: $path,
            data: {func: "unconfirmPlan", hduser: hduser, deliveryNo: deliveryNoList},
            type: "post",
            success: function ( respTxt ) {
                var response = eval( respTxt );
                
                var msgListY = $.map(response.filter(function (o){ return o.status == 'Y'; }), function (o){ return o.message; }).join('\n');
                var msgListN = $.map(response.filter(function (o){ return o.status == 'N'; }), function (o){ return o.message; }).join('\n');
                //var msgList = response.map(function (o){ return o.message; }).join('\n');
                //alert( msgListY );

                if (msgListY.length) {
                    alert( '____________ <?php echo $GLOBALS['_lng108']; ?> ____________\n'+ msgListY );
                }

                if (msgListN.length) {
                    alert( '____________ <?php echo $GLOBALS['_lng109']; ?> ____________\n'+ msgListN );
                }

                reloadPlan(false);
            }
        } );
    }
}

function printDeliveryDocument(delivery_no) {
    var lang = '<?php echo strtolower(substr($_SESSION['TMS_LANGUAGE'], 0, 3));?>';
    window.open("application/pages/planning_report/delivery_doc.php?lang="+ lang +"&delivery_no=" + delivery_no, "_blank", "toolbar=no,menubar=no,location=no,scrollbars=yes,resizable=no,top=50, left=400,width=950,height=750");
}

function openEditPlanItem(key_id) {
    $.ajax( {
        url: $path,
        data: {func: "getPlanItem", hduser: hduser, keyId: key_id},
        type: "post",
        success: function ( respTxt ) {
            var response = eval( respTxt );
            
            if (!response.length) {
                alert('!');
                return false;
            }
            
            createEditPlanItem(key_id, response);
        }
    } );
}

function createEditPlanItem(key_id, data) {
    var str_builder = '';
    $.each(data, function (i,o) {
        var order_status = Number(o.order_status);
        var order_status_tag;
        
        if (order_status === 99) {
            order_status_tag = '<span class="tag bg-blue"><?php echo $GLOBALS['_lng164']; ?></span>';

        } else if (order_status === 1) {
            order_status_tag = '<span class="tag bg-orange"><?php echo $GLOBALS['_lng165']; ?></span>';
        
        } else if (order_status === 2) {
            order_status_tag = '<span class="tag bg-purple"><?php echo $GLOBALS['_lng166']; ?></span>';
            
        } else if (order_status === 3) {
            order_status_tag = '<span class="tag bg-orange"><?php echo $GLOBALS['_lng167']; ?></span>';

        } else if (order_status === 100) {
            order_status_tag = '<span class="tag bg-green"><?php echo $GLOBALS['_lng101']; ?></span>';
        }
        
        if (order_status == 100) {
            str_builder += '<tr class="order_closed">'
                    +'<td>'+ (i+1) +'</td>'
                    +'<td>'+ o.request_date +'</td>'
                    +'<td>'+ o.order_no +'</td>'
                    +'<td>'+ order_status_tag +'</td>'
                    +'<td>'+ o.line_no +'</td>'
                    +'<td>'+ o.item_type_name +'</td>'
                    +'<td>'+ o.item_name +'</td>'
                    +'<td>'+ number_format(o.item_weight, 2, 2) +'</td>'
                    +'<td>'+ number_format(o.item_qty, 0, 0) +'</td>'
                +'</tr>';
            return;
        }
        
        str_builder += '<tr class="order_delivering">'
                +'<td>'+ (i+1) +'</td>'
                +'<td>'+ o.request_date +'</td>'
                +'<td>'+ o.order_no +'<input type="hidden" class="txt_plan_order_no" value="'+ o.order_no +'" /></td>'
                +'<td>'+ order_status_tag +'</td>'
                +'<td>'+ o.line_no +'<input type="hidden" class="txt_plan_line_no" value="'+ o.line_no +'" /></td>'
                +'<td>'+ o.item_type_name +'</td>'
                +'<td>'+ o.item_name +'</td>'
                +'<td>'+ number_format(o.item_weight, 2, 2) +'</td>'
                +'<td><input type="text" class="form-control txt_plan_item_qty" value="'+ o.item_qty +'" /></td>'
            +'</tr>';
    });
    $('#tb_plan_item>tbody').html(str_builder);
    
    if ($('.txt_plan_item_qty').length) {
       $(".txt_plan_item_qty").keypress(function(event){
            if(event.which != 8 && isNaN(String.fromCharCode(event.which))){
                event.preventDefault(); //stop character from entering input
            }
        });
        
        $('#btn_edit_plan_item').show();
    } else {
        $('#btn_edit_plan_item').hide();
    }
    
    $('#modal_plan_item .modal-header .modal-title').html('<?php echo $GLOBALS['_lng244'];?>:&nbsp;&nbsp;<?php echo $GLOBALS['_lng72'];?>'+ data['0'].delivery_no);
    $('#modal_plan_item').data({'kId': key_id, 'delNo': data['0'].delivery_no});
    $('#modal_plan_item').modal('show');
}

function saveEditPlanItem() {
    var modData = $('#modal_plan_item').data();
    var key_id = modData.kId;
    var delivery_no = modData.delNo;
    
    var order_item_data = '';
    $.each($("#tb_plan_item>tbody>tr.order_delivering").toArray(), function (i,o) {
        var item_qty = Number($(".txt_plan_item_qty",o).val());
        order_item_data += ','+ $(".txt_plan_order_no",o).val() +'###'+ $(".txt_plan_line_no",o).val() +'###'+ item_qty;
        
        if (isNaN(item_qty) || item_qty < 1) {
            order_item_data = false;
            return false;
        }
    });
    
    if (order_item_data === false) {
        alert('!!!!');
        return false;
    }
    
    order_item_data = order_item_data.substr(1, order_item_data.length);
    
    var confirmation = confirm( "<?php echo $GLOBALS['_lng399']; ?>" );
    if ( confirmation ) {
        $.ajax( {
            url: $path,
            data: {func: "saveEditPlanItem", hduser: hduser, deliveryNo: delivery_no, orderItemData: order_item_data},
            type: "post",
            success: function ( respTxt ) {
                var response = eval( respTxt )['0'];
                
                if ( response.status == "Y" ) {
                    alert( response.message );
                    openEditPlanItem(key_id);
                    
                    reloadPlan(false);
                } else {
                    alert( response.message );
                }
            }
        } );
    }
}

function openEditVehicle(delivery_id, vehicle_id) {
	$("#tb_edit_vehicle").DataTable().destroy();
    $.ajax({
        url: $path,
        data: {func: "getEditVehicle", hduser: hduser
                , deliveryId: delivery_id, vehicleId: vehicle_id
                , dateStart: function(){return filtDateStart;}
                , dateEnd: function(){return filtDateEnd;}
            },
        type: "post",
        success: function ( respTxt ) {
            var data = eval( respTxt );
            
            if (!data.length) {
                return;
            }
            
            var str_builder = '';
            $.each(data, function(i,v){
                str_builder += '<tr>';
                str_builder += '<td class="text-center"><input type="radio" class="iCheck" name="rad_edit_vehicle" value="'+ v.vehicle_id +'" '+ (+v.checked == 0? '': 'checked') +' /></td>';
                str_builder += '<td>'+ v.vehicle_name +'</td>';
				str_builder += '<td>'+ v.driver_name +'</td>';
                str_builder += '<td>'+ v.last_plan_time +'</td>';
                str_builder += '</tr>';
            });
            
            $('#btn_save_edit_vehicle').attr('onclick', 'saveEditVehicle(\''+ delivery_id +'\', \''+ vehicle_id +'\')');
            $('#tb_edit_vehicle > tbody').html(str_builder);
            
            $("input[type='radio'].iCheck").iCheck({
                checkboxClass: 'icheckbox_flat-blue',
                radioClass   : 'iradio_flat-blue'
            });
            createDatatable();
            $('#modal_edit_vehicle').modal('show');
        }
    });
}

function createDatatable(){
    $("#tb_edit_vehicle").DataTable({
            language: {
                    "emptyTable": "<?php echo $GLOBALS['_lng61'];?>",
                    "info": "<?php echo $GLOBALS['_lng53'];?>",
                    "infoEmpty": "<?php echo $GLOBALS['_lng56'];?>",
                    "infoFiltered": "<?php echo $GLOBALS['_lng55'];?>",
                    "infoPostFix": "",
                    "thousands": ",",
                    "lengthMenu": "<?php echo $GLOBALS['_lng54'];?>",
                    "processing": "<?php echo $GLOBALS['_lng51'];?>",
                    "search": "<?php echo $GLOBALS['_lng50'];?>",
                    "zeroRecords": "<?php echo $GLOBALS['_lng52'];?>",
                    "paginate": {
                            "first": "",
                            "last": "",
                            "next": "<?php echo $GLOBALS['_lng59'];?>",
                            "previous": "<?php echo $GLOBALS['_lng60'];?>"
                    }
            },

    });// End DataTable
}

function saveEditVehicle(delivery_id, vehicle_id) {
    var edit_vehicle_id = $('input:radio[name="rad_edit_vehicle"]:checked').val();
	
	$('#modal_edit_vehicle').off('hidden.bs.modal').on('hidden.bs.modal', function () {
		$('#modal_edit_vehicle').off('hidden.bs.modal');
		reloadPlan(true);
	});
	
    $.ajax({
        url: $path,
        data: {func: "saveEditVehicle", hduser: hduser, deliveryId: delivery_id, vehicleId: edit_vehicle_id},
        type: "post",
        success: function ( respTxt ) {
            var data = eval( respTxt );
            
            if (!data.length) {
                return;
            }
            
            if (data['0'].status == 'Y') {
                alert(data['0'].message);
                openEditVehicle(delivery_id, edit_vehicle_id);
            } else {
                alert(data['0'].message);
                openEditVehicle(delivery_id, vehicle_id);
            }
        }
    });
}

function viewMap(delivery_no){
    $('.modal-title-map').text(delivery_no);
    $.ajax( {
        url: $path,
        data: {func: "getRoute", hduser: hduser, deliveryNo: delivery_no},
        type: "post",
        success: function ( respTxt ) {
            var data = eval( respTxt );
            var station = [];
            title_drop = [];
            $.each(data, function (i, v) {
                //station.push({latitude:v.station_latitude,longitude:v.station_longtitude,shipto_seq:v.route_seq, name:v.station_name, name:v.station_code});
                station.push({latitude:v.station_latitude,longitude:v.station_longtitude,shipto_seq:v.route_seq, name:v.station_code});
				//title_drop.push({name:v.station_name});
            });
            deleteMarkers();
            drawGoogleRoute(station, map);
            /*setTimeout(function () {
                setMapCenter(13.673335518350793, 100.60689544677734, 8);
            }, 2000);*/
            
            //$('#map').removeAttr('style');
            $('#modal_view_map').modal();
            //setMapCenter(13.673335518350793,100.60689544677734,6);
        }
    } );
    
    
}

function initMap() {
   /* map = new google.maps.Map(document.getElementById('map'), {
            center: {lat: 13.6767685, lng: 100.60134},
            zoom: 6
    });*/
    var myLatlng = new google.maps.LatLng(13.673335518350793,100.60689544677734);
    var myOptions = {
      zoom: 4,
      center: myLatlng,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    }
    map = new google.maps.Map(document.getElementById("map"), myOptions);
    
    
}

function addMarker(location, title, label, map) {
    var m = new google.maps.Marker({position: location, title: title, label: {text:label,color:"white"}});
    markers.push(m);
}
function setMapCenter(latitude,longitude,zoom){
    try{
            map.setZoom(zoom); 
            map.setCenter(new google.maps.LatLng(latitude, longitude));
    }catch(err){} 
}
var modal_map;
function initMadalMap() {
    modal_map = new google.maps.Map(document.getElementById('modal-map'), {
            center: {lat: 13.6767685, lng: 100.60134},
            zoom: 4
    });
    directionsService = new google.maps.DirectionsService;
    directionsDisplay = new google.maps.DirectionsRenderer;
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


function drawGoogleRoute(data,map_){
    
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
                
                var GroupStationName = oData.filter(function (o){
                        return Number(o.latitude) === aWaypointLat && Number(oData[cntPlace].longitude) === aWaypointLon;
                }).map(function (o){ return o.shipto_seq + '. ' +o.name; }).sort().join('<br>');
				
				var GroupStationCode = oData.filter(function (o){
                        return Number(o.latitude) === aWaypointLat && Number(oData[cntPlace].longitude) === aWaypointLon;
                }).map(function (o){ return o.shipto_seq + '. ' +o.name; }).sort().join('<br>');
                
                title_drop.push({name:GroupStationName});

                var WaypointMarker = new google.maps.Marker({
                        position: aWaypoint.location
                        , map: map_
                        , draggable: false
                        , animation: false
                        , label: {text:GroupWaypointTitle, color:'#fff', fontSize:"14px"}
                                        //, icon: path+ 'img/truck_32.png'
                                        , title: GroupStationCode
                                });
                markers.push(WaypointMarker);
                groupMarkerFromWaypoints.push({latitude: aWaypointLat, longitude: aWaypointLon});
                var contentString = '<div id="content">'
                        +'<div id="siteNotice"></div>'
                        +'<div id="bodyContent"><p>'+ GroupStationName +'</p></div>'
                        +'</div>';

                    var infowindow = new google.maps.InfoWindow({
                      content: contentString
                    });
                    //infowindow.open(map_, WaypointMarker);
                    
               /* WaypointMarker.addListener('click', function() {
                    //setInfoWindow(divMapId, markers[divMapId].length, markerContent);
                    var contentString = '<div id="content">'
                        +'<div id="siteNotice"></div>'
                        +'<div id="bodyContent"><p>'+ title_drop[(markers.length) - 1].name +'</p></div>'
                        +'</div>';

                    var infowindow = new google.maps.InfoWindow({
                      content: contentString
                    });
                    infowindow.open(map_, this);
                });*/
				
                

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


function openEditDeliveryDate(delivery_id) {
	var tbData = oTbPlan.data().toArray();
	var rowData = tbData.find(function(o){return o.delivery_id == delivery_id;});
	
	$('#btn_save_edit_delivery_date').attr('onclick', 'saveEditDeliveryDate(\''+ delivery_id +'\')');
	//var newDate = new Date();
    //var dateFormat = newDate.yyyymmdd();
	$('#txt_delivery_date').val(rowData.delivery_date).datepicker();
    $('#modal_edit_delivery_date').modal('show');
}

function saveEditDeliveryDate(delivery_id) {
	var newDeliveryDate = $('#txt_delivery_date').val();
	
	let conf = confirm("<?php echo $GLOBALS['_lng399']; ?>");
	if (!conf) {
		return false;
	}
	
	$.ajax({
        url: $path,
        data: {func: "saveEditDeliveryDate", hduser: hduser, deliveryId: delivery_id, deliveryDate: newDeliveryDate},
        type: "post",
        success: function ( respTxt ) {
            var data = eval( respTxt );
            
            if (!data.length) {
                return;
            }
            
            if (data['0'].status == 'Y') {
                alert(data['0'].message);
                $('#modal_edit_delivery_date').modal('hide');
				reloadPlan(true);
            } else {
                alert(data['0'].message);
                openEditDeliveryDate(delivery_id);
            }
        }
    });
}

</script>