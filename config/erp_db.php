<?php
// config/erp_db.php
// SAP ERP Connection (Oracle ECCERP)
function getErpConnection() {
    $dspUser = "DSP";
    $dspPWD  = "dsp";
    $dspDB   = "ECCERP";
    $dspLang = "TH8ISCII";

    $conn = @oci_connect($dspUser, $dspPWD, $dspDB, $dspLang);
    if (!$conn) {
        return false;
    }
    return $conn;
}
?>