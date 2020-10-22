<?PHP
class Delivery_Write_Log {
    var $user_id;
    var $delivery_no;
    var $order_no;
    var $line_no;
    var $result_status; // Y or N
    var $result_message;
    
    public function __construct($uid, $dno, $ono, $lno, $sts, $msg) {
        $this->user_id = $uid;
        $this->delivery_no = $dno;
        $this->order_no = $ono;
        $this->line_no = $lno;
        $this->result_status = $sts;
        $this->result_message = $this->avoid_single_quotes($msg);
        
        $this->writer();
    }

    public function writer() {
        dbConnection("exec [dbo].[proccess_delivery_write_log] N'EDIT', ". $this->user_id ."
            , N'". $this->delivery_no ."', NULL, NULL, NULL
            , N'". $this->order_no ."', N'". $this->line_no ."', NULL, NULL, NULL
            , N'". $this->result_status ."', N'". $this->result_message ."'
            , NULL");
    }
    
    public function avoid_single_quotes($content) {
        return preg_replace("/'/", "''", $content);
    }
}
?>