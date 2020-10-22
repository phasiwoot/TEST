<script>
/////////////////////////////////////////////////////////// PLAN UI FUNCTION //////////////////////////////////////////////////////////////////////////////////////////////
var PlanListView = (function (options) {
    var $this = this;
    $this.data;
    
    $this.opts = {
        column: [],
        afterSectionRender: false, //function(){}
        afterTableRowRender: false, //function(){}
        afterRender: false //function(){}
    };
    
    $this.__construct = function () {
        //Initial method
        $this.data = options.data;
        delete options.data;
        
        $.each(options, function (keyOpt, objectOpt) {
            $this.opts[keyOpt] = objectOpt;
        });
        
        if (typeof $.fn.iCheck !== 'undefined') {
            //$this.getView();
        } else {
            $.getScript('application/resources/plugins/iCheck/icheck.min.js', function() {
                //$this.getView();
            });
        }
    };
    
    $this.setData = function ($data) {
        return $this.data = $data;
    };
    
    $this.getData = function () {
        return $this.data;
    };
    
    $this.getView = function () {
        var data = $this.data;
        var nodeSection = $this.getPlanListView(Number(data.row_number)-1, data);
        return nodeSection;
    };
    
    $this.getPlanListView = function ($rowId, $data) {
        var order_status = Number($data.order_status);
        var order_status_tag = "";
        var checkboxRow = "";
        var btnRebuild = ''; //<div class="pull-right" style="margin-right:10px" onclick="rebuildBatch(\''+ $data.order_no +'\')"><button class="btn btn-xs bg-light-blue"><i class="fa fa-sm fa-refresh"></i> <?php echo $GLOBALS['_lng125']; ?></button></div>';
        var btnRow = "<div class='col-xs-2'>\n\
                        <div class='pull-right'>\n\
                            <button type='button' class='btn-inv-verify btn btn-warning btn-xs'><i class='fa fa-fw fa-check'></i></button>\n\
                            <button type='button' class='btn-inv-trash btn btn-danger btn-xs'><i class='fa fa-fw fa-trash'></i></button>\n\
                        </div>\n\
                    </div>";
            btnRow = "";
            
        if (order_status === 0) {
            checkboxRow = "<label class='block-inline'><input type='checkbox' class='flat-blue cb-inv-checklist' value='"+ $data.order_no +"'></label>&emsp;";
            order_status_tag = "&emsp;<span class='tag bg-blue'><?php echo $GLOBALS['_lng164']; ?></span>";

        } else if (order_status === 1) {
            checkboxRow = "<div style='display:inline-block;position:relative;'><label><input type='checkbox' class='flat-blue cb-inv-checklist' value='"+ $data.order_no +"'></label></div>&emsp;";
            order_status_tag = "&emsp;<span class='tag bg-orange'><?php echo $GLOBALS['_lng165']; ?></span>";
        
        } else if (order_status === 2) {
            checkboxRow = "<div style='display:inline-block;position:relative;'><label><input type='checkbox' class='flat-blue' disabled></label></div>&emsp;";
            order_status_tag = "&emsp;<span class='tag bg-purple'><?php echo $GLOBALS['_lng166']; ?></span>";
            btnRebuild = '';
            
        } else if (order_status === 3) {
            checkboxRow = "<div style='display:inline-block;position:relative;'><label><input type='checkbox' class='flat-blue' disabled></label></div>&emsp;";
            order_status_tag = "&emsp;<span class='tag bg-orange'><?php echo $GLOBALS['_lng167']; ?></span>";
            btnRebuild = '';

        } else if (order_status === 100) {
            checkboxRow = "<div style='display:inline-block;position:relative;'><label><input type='checkbox' class='flat-blue' disabled></label></div>&emsp;";
            order_status_tag = "&emsp;<span class='tag bg-green'><?php echo $GLOBALS['_lng101']; ?></span>";
            btnRebuild = '';
        }
        
        var nodeSection = document.createElement('section');
        nodeSection.classList.add('invoice');
        /*nodeSection.innerHTML = '\n\
            <div class="row">\n\
                <div class="col-xs-12">\n\
                    <h2 class="page-header">\n\
                        <div class="row">\n\
                            <div class="col-xs-12">\n\
                                '+ checkboxRow +'\n\
                                <i class="fa fa-file-text"></i> '+ ( $rowId+1 ) +' #'+ $data.order_no + order_status_tag +'\n\
                                <br/><small><?php echo $GLOBALS['_lng65']; ?>:&emsp;'+ $data.request_date +'&emsp;&emsp;<?php echo $GLOBALS['_lng102']; ?>:&emsp;'+ $data.create_date +'\n\
                                <div class="pull-right"><?php echo $GLOBALS['_lng122']; ?>:&emsp;'+ $data.create_by +'</small>\n\
                            </div>\n\
                            '+ btnRow +'\n\
                        </div>\n\
                    </h2>\n\
                </div>\n\
            </div>\n\
            <div class="row invoice-info">\n\
                <div class="col-sm-2 invoice-col">\n\
                    <?php echo $GLOBALS['_lng12']; ?><address><b>'+ $data.owner_name +'</b></address>\n\
                </div>\n\
                <div class="col-sm-4 invoice-col">\n\
                    <?php echo $GLOBALS['_lng71']; ?><address><b>'+ $data.src_name +'</b><br/><i>'+ $data.src_address +'</i></address>\n\
                </div>\n\
                <div class="col-sm-4 invoice-col">\n\
                    <?php echo $GLOBALS['_lng79']; ?><address><b>'+ $data.des_name +'</b><br/><i>'+ $data.des_address +'</i></address>\n\
                </div>\n\
                <div class="col-sm-2 invoice-col">'+ btnRebuild +'</div>\n\
            </div>';*/
            
            var item_list = $data.item_list;
            var nodeTableInDiv = $this.getPlanListTable();
            var nodeTableBody = $this.getPlanListTableBody(item_list);
            nodeTableInDiv.querySelector('table').appendChild(nodeTableBody);
            nodeSection.appendChild(nodeTableInDiv);
        
        if (typeof $this.opts.afterSectionRender === 'function') {
            $this.opts.afterSectionRender($rowId, $data, nodeSection);
        }
        
        return nodeSection;
    };
     
    // Create NODE div table
    $this.getPlanListTable = function () {
        var nodeDiv = document.createElement('div');
        nodeDiv.classList.add('row');
        nodeDiv.innerHTML = '<div class="col-xs-12 table-responsive"><table class="table table-striped">'+ $this.getPlanListTableHeader() +'</table></div>';
        return nodeDiv;
    };
    
    // Create HTML table header
    $this.getPlanListTableHeader = function () {
        var txtTh = $.map($this.opts.column, function (o){
                var tCell = document.createElement('th');
                tCell.innerHTML = o.title;
                tCell.style.width = o.width || '';
                return tCell.outerHTML;
                //return '<th>'+ o.title +'</th>';
            }).join('');
        return '<thead style="background-color: #E5E7E9;"><tr>'+ txtTh +'</tr></thead>';
    };
    
    $this.getPlanListTableBody = function ($data) {
        var tbody = document.createElement('tbody');
        //var dataBody = $this.data.filter(function (o){ return o.keyId === $keyId; });
        
        $.each($data, function (i, o) {
            var nodeTableBodyRow = $this.getPlanListTableBodyRow(i, o);
            tbody.appendChild(nodeTableBodyRow);
        });
        
        return tbody;
    };
    
    $this.getPlanListTableBodyRow = function ($rowId, $data) {
        var tRow = document.createElement('tr');
        
        $.each($this.opts.column, function (colId, column) {
            var nodeTableBodyCell = $this.getPlanListTableBodyCell($rowId, $data, colId, column);
            tRow.appendChild(nodeTableBodyCell);
        });
        
        if (typeof $this.opts.afterTableRowRender === 'function') {
            $this.opts.afterTableRowRender($rowId, $data, tRow);
        }
        return tRow;
    };
    
    $this.getPlanListTableBodyCell = function ($rowId, $data, $colId, $column) {
        //var fType = $column.type;
        var fName = $column.data;
        var fRender = $column.render;
        
        var displayText;
        if (fRender && typeof fRender === 'function') {
            displayText = $column.render($rowId, $data, $colId, $data[fName], $this);
        } else {
            displayText = $this.getPlanListTableBodyCellDataDisplay($rowId, $data, $colId, $data[fName], $this);
        }
        
        var tCell = document.createElement('td');
            tCell.innerHTML = displayText;
        return tCell;
    };
    
    $this.getPlanListTableBodyCellDataDisplay = function ($rowId, $rowData, $colId, $colData, $meta) {
        var fType = $meta.opts.column[$colId].type;
        var displayText = $colData;
        var displayWithFormat;
        
        if (fType === 'string') {
            displayWithFormat = displayText;
        } else if (fType === 'number') {
            displayWithFormat = parseInt(Number(displayText)).toLocaleString();
        } else if (fType === 'decimal') {
            displayWithFormat = parseFloat(Number(displayText)).toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 3});
        } else {
            displayWithFormat = displayText;
        }
        return displayWithFormat;
    };
    
    $this.__construct();
    return $this;
});
/////////////////////////////////////////////////////////// PLAN UI FUNCTION //////////////////////////////////////////////////////////////////////////////////////////////
</script>