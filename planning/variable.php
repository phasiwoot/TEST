<script>
var orderDateSt = null
    , orderDateEn = null
    , orderDateType = null
    , orderSource = null
    , orderContractor = null
    , orderSearch = null
    , orderStatus = null;
var deliveryDate = null
    , deliveryContractor = null
    , deliveryVehicleType = null;
var ajaxReqOrderGroup = null
    , ajaxReqVehicleFleet = null
    , ajaxReqDeliveryFleet = null
    , ajaxReqDeliveryFleetRoute = null
    , ajaxReqVehicleSuggest = null;
    
var orderGroupList = [
        {title: '<?php echo $GLOBALS['_lng418'];?>', key: 'consignment_no', value: 'consignment_no', is_default: 1}
        //{title: '<?php echo $GLOBALS['_lng449'];?>', key: 'owner_id', value: 'owner_name', is_default: 0}
        , {title: '<?php echo $GLOBALS['_lng12'];?>', key: 'contractor_name', value: 'contractor_name', is_default: 0}
        , {title: '<?php echo $GLOBALS['_lng71'];?>', key: 'src_id', value: 'src_name', is_default: 0}
        , {title: '<?php echo $GLOBALS['_lng426'];?>', key: 'des_id', value: 'des_name', is_default: 0}
        , {title: '<?php echo $GLOBALS['_lng7'];?>', key: 'customer_id', value: 'customer_name', is_default: 0}
        , {title: '<?php echo $GLOBALS['_lng4'];?>', key: 'order_id', value: 'order_no', is_default: 0}
        , {title: '<?php echo $GLOBALS['_lng492'];?>', key: 'address_pat', value: 'address_pat', is_default: 0}
        , {title: '<?php echo $GLOBALS['_lng493'];?>', key: 'address_pa', value: 'address_pa', is_default: 0}
    ];
var pickOrderGroup = null;
var delayAfterAjaxReq = 600;

var map = null;
var markers = [];
var directionsService = null;
var directionsDisplay = null;
</script>