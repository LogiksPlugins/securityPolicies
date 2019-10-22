<?php
if(!defined('ROOT')) exit('No direct script access allowed');

handleActionMethodCalls();

function _service_listPolicies() {
    $data = _db(true)->_selectQ(_dbTable("rolescope",true),"scope_title,scope_type,blocked,md5(id) as scope_hash")->_GET();
    return $data;
}

function _service_fetchPolicy() {
    if(!isset($_REQUEST['refid'])) {
        return "";
    }
    $data = _db(true)->_selectQ(_dbTable("rolescope",true),"scope_params",["md5(id)"=>$_REQUEST['refid']])->_GET();
    
    if(isset($data[0])) {
        if(strlen($data[0]['scope_params'])<=0) $data[0]['scope_params'] = "{}";
        return $data[0]['scope_params'];
    }
    
    return "";
}

function _service_fetchRefcode() {
    if(!isset($_REQUEST['refid'])) {
        return "";
    }
    $data = _db(true)->_selectQ(_dbTable("rolescope",true),"scope_id",["md5(id)"=>$_REQUEST['refid']])->_GET();
    
    if(isset($data[0])) {
        if(strlen($data[0]['scope_id'])<=0) $data[0]['scope_id'] = "{}";
        return $data[0]['scope_id'];
    }
    
    return "";
}

function _service_createNewPolicy() {
    if(!isset($_POST['scopename'])) {
        return ["status"=>"error","msg"=>"Error finding scope name"];
    }
    if(!isset($_POST['privilegeid'])) {
        $_POST['privilegeid'] = 0;
    }
    if(!isset($_POST['scopetype'])) {
        $_POST['scopetype'] = "generic";
    }
    
    $policyBody = "{}";
    
    //Use template to derivce the policyBody
    
    $date = date("Y-m-d H:i:s");
    if(defined("CMS_SITENAME")) {
        $query =_db(true)->_insertQ1(_dbTable("rolescope",true),[
            "guid"=>"*",
            "privilegeid"=>$_POST['privilegeid'],
            
            "scope_title"=>$_POST['scopename'],
            "scope_id"=>uniqid(),
            "scope_type"=>$_POST['scopetype'],
            "scope_params"=>$policyBody,
            "remarks"=>CMS_SITENAME,
            
            "created_by"=>$_SESSION['SESS_USER_ID'],
            "created_on"=>$date,
            "edited_by"=>$_SESSION['SESS_USER_ID'],
            "edited_on"=>$date,
        ]);
    } else {
        $query =_db(true)->_insertQ1(_dbTable("rolescope",true),[
            "guid"=>$_SESSION['SESS_GUID'],
            "privilegeid"=>$_POST['privilegeid'],
            
            "scope_title"=>$_POST['scopename'],
            "scope_id"=>uniqid(),
            "scope_type"=>$_POST['scopetype'],
            "scope_params"=>$policyBody,
            "remarks"=>CMS_SITENAME,
            
            "created_by"=>$_SESSION['SESS_USER_ID'],
            "created_on"=>$date,
            "edited_by"=>$_SESSION['SESS_USER_ID'],
            "edited_on"=>$date,
        ]);        
    }

    $ans = $query->_RUN();
    
    if($ans) {
        return ["status"=>"success","msg"=>"Policy created successfully"];
    } else {
        return ["status"=>"error","msg"=>"Error creating policy"];
    }
    
}

function _service_updatePolicy() {
    if(!isset($_POST['refid']) && strlen($_POST['refid'])>0) {
        return ["status"=>"error","msg"=>"Error finding policy reference"];
    }
    if(!isset($_POST['content'])) {
        return ["status"=>"error","msg"=>"Error finding policy body"];
    }
    
    $policyBody = $_POST['content'];
    
    $policyArr = json_decode($policyBody,true);

    if(!is_array($policyArr)) {
        return ["status"=>"error","msg"=>"Error validating policy body<br>May be some brackets are open"];
    }
    
    $policyBody = json_encode($policyArr,JSON_PRETTY_PRINT);
    
    $date = date("Y-m-d H:i:s");
    $query =_db(true)->_updateQ(_dbTable("rolescope",true),[
            "scope_params"=>$policyBody,
            
            "edited_by"=>$_SESSION['SESS_USER_ID'],
            "edited_on"=>$date,
        ],[
            "md5(id)"=>$_POST['refid']
        ]);
    
    $ans = $query->_RUN();
    
    if($ans) {
        return ["status"=>"success","msg"=>"Policy updated successfully"];
    } else {
        return ["status"=>"error","msg"=>"Error updating policy"];
    }
}

function _service_deactivatePolicy() {
    if(!isset($_POST['refid']) && strlen($_POST['refid'])>0) {
        return ["status"=>"error","msg"=>"Error finding policy reference"];
    }
    
    $date = date("Y-m-d H:i:s");
    $query =_db(true)->_updateQ(_dbTable("rolescope",true),[
            "blocked"=>"true",
            
            "edited_by"=>$_SESSION['SESS_USER_ID'],
            "edited_on"=>$date,
        ],[
            "md5(id)"=>$_POST['refid']
        ]);
    
    $ans = $query->_RUN();
    
    if($ans) {
        return ["status"=>"success","msg"=>"Policy blocked successfully"];
    } else {
        return ["status"=>"error","msg"=>"Error blocking policy"];
    }
}

function _service_activatePolicy() {
    if(!isset($_POST['refid']) && strlen($_POST['refid'])>0) {
        return ["status"=>"error","msg"=>"Error finding policy reference"];
    }
    
    $date = date("Y-m-d H:i:s");
    $query =_db(true)->_updateQ(_dbTable("rolescope",true),[
            "blocked"=>"false",
            
            "edited_by"=>$_SESSION['SESS_USER_ID'],
            "edited_on"=>$date,
        ],[
            "md5(id)"=>$_POST['refid']
        ]);
    
    $ans = $query->_RUN();
    
    if($ans) {
        return ["status"=>"success","msg"=>"Policy activated successfully"];
    } else {
        return ["status"=>"error","msg"=>"Error activating policy"];
    }
}

function _service_format() {
    if(isset($_POST['content'])) {
        $policyArr = json_decode($policyBody,true);

        if(!is_array($policyArr)) {
            return $_POST['content'];
        }
        
        $policyBody = json_encode($policyArr,JSON_PRETTY_PRINT);
        
        return $policyBody;
    }
    
    return "";
}
?>