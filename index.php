<?php
if(!defined('ROOT')) exit('No direct script access allowed');

loadModule("pages");

if(checkVendor("ace")) {
    loadVendor("ace");
} elseif(checkModule("cmsEditor")) {
    loadModuleLib("cmsEditor","embed");
} else {
    echo "<h1 align=center>Dependent Module/Vendor Not Found</h1>";
    return;
}

printPageComponent(false,[
		"toolbar"=>[
		    "refreshPage"=>["icon"=>"<i class='fa fa-refresh'></i>","tips"=>"Reload Page"],
			"createNew"=>["icon"=>"<i class='fa fa-plus'></i>","tips"=>"Create a new policy"],
// 			['type'=>"bar"],
// 			"saveFile"=>["icon"=>"<i class='fa fa-save'></i>","tips"=>"Save current file"],
			
			"infoPolicy"=>["title"=>"RefCode","align"=>"right","icon"=>"<i class='fa fa-eye'></i>","class"=>"toolbtnOnEditor hidden"],
			
			"formatPolicy"=>["title"=>"Format","align"=>"right","icon"=>"<i class='fa fa-code'></i>","class"=>"toolbtnOnEditor hidden"],
			"savePolicy"=>["title"=>"Save","align"=>"right","icon"=>"<i class='fa fa-save'></i>","class"=>"toolbtnOnEditor hidden"],
			"blockPolicy"=>["title"=>"","icon"=>"<i class='fa fa-trash'></i>","align"=>"right","tips"=>"Block/Unblock current policy","class"=>"toolbtnOnEditor hidden"],
            //"langDropdown"=>["title"=>"Language Selector","align"=>"right","type"=>"dropdown","options"=>[]],
		],
		"sidebar"=>"pageSidebar",
		"contentArea"=>"pageContentArea"
	]);

echo _css("securityPolicies");
echo _js("securityPolicies");

function pageSidebar() {
	return "<div id='componentTree' class='componentTree list-group list-group-root well'></div>";
}
function pageContentArea() {
	return "
	    <div id='noEditor'><h2 class='text-center'>No policy loaded</h2></div>
		<div id='editorScript' class='editorArea' ext='javascript'></div>
	";
}
?>
<style>
.list-group-item {
    cursor: pointer;
}
.editorArea {
    position: absolute;
    top: 0px;
    bottom: 0px;
    right: 0;left: 0;
}
</style>
<script>
var editor = null;
var currentRefid = "";
$(function() {
    $("#componentTree").delegate(".list-group-item[data-refid]", "click", function() {
        $("#componentTree .active").removeClass("active");
        
        refid = $(this).data("refid");
        $(this).addClass("active");
        loadPolicy(refid);
    });
    
    editor=ace.edit("editorScript");
    loadEditorSettings();
    setupEditorConfig(editor,"json");
    
    $("#editorScript").hide();
    
    listPolicies();
});
function refreshPage() {
    window.location.reload();
}
function listPolicies() {
    $("#componentTree").html("<div class='ajaxloading ajaxloading4'></div>");
    processAJAXQuery(_service("securityPolicies","listPolicies"), function(data) {
        if(data.Data.length<=0) {
            $("#componentTree").html("<h5 class='text-center'>No policies found</h5>");
        } else {
            $("#componentTree").html("<ul class='list-group'></ul>");
            $.each(data.Data, function(a,b) {
                if(b.blocked=='true') {
                    $("#componentTree>ul").append('<li class="list-group-item list-group-item-danger blocked" title="Blocked Policy" data-refid="'+b.scope_hash+'">'+b.scope_title+'</li>');
                } else {
                    $("#componentTree>ul").append('<li class="list-group-item" data-refid="'+b.scope_hash+'">'+b.scope_title+'</li>');
                }
            })
            
            if(currentRefid && currentRefid.length>0) {
                $("#componentTree>ul .list-group-item[data-refid='"+currentRefid+"']").addClass("active");
            }
        }
    },"json");
}
function createNew() {
    lgksPrompt("Please give Scope Name <br><citie style='font-size:10px;'>This is just for your reference</citie>","New Scope!", function(ans) {
        if(ans) {
            processAJAXPostQuery(_service("securityPolicies","createNewPolicy"), "scopename="+ans, function(data) {
                lgksToast(data.Data.msg);
                if(data.Data.status=="error") {
                    
                } else {
                    listPolicies();
                }
                
            },"json");
        }
    });
}

function loadPolicy(refid) {
    processAJAXQuery(_service("securityPolicies","fetchPolicy","raw","&refid="+refid), function(data) {
        if(data.length>0) {
            editor.setValue(data);
            $("#noEditor").hide();
            $("#editorScript").show();
            editor.setReadOnly(false);
            $(".toolbtnOnEditor").removeClass("hidden");
        } else {
            closeEditor();
        }
    },"raw");
}

function savePolicy() {
    if($("#componentTree .list-group-item[data-refid].active").length<=0) {
        $(".toolbtnOnEditor").addClass("hidden");
        editor.setReadOnly(true);
        lgksToast("Error finding selected policy");
        return;
    }
    
    refid = $("#componentTree .list-group-item[data-refid].active").data("refid");
    processAJAXPostQuery(_service("securityPolicies","updatePolicy"), "refid="+refid+"&content="+encodeURIComponent(editor.getValue()), function(data) {
                lgksToast(data.Data.msg);
                if(data.Data.status=="error") {
                    
                }
                editor.setReadOnly(false);
            },"json");
}

function formatPolicy() {
    processAJAXPostQuery(_service("securityPolicies","format","raw"), "&content="+encodeURIComponent(editor.getValue()), function(data) {
                editor.setValue(data);
            },"raw");
}

function infoPolicy() {
    if($("#componentTree .list-group-item[data-refid].active").length<=0) {
        $(".toolbtnOnEditor").addClass("hidden");
        editor.setReadOnly(true);
        lgksToast("Error finding selected policy");
        return;
    }
    
    processAJAXQuery(_service("securityPolicies","fetchRefcode","raw","&refid="+refid), function(data) {
        if(data.length>0) {
            lgksAlert("<h3 class='text-center'>"+data+"</h3>");
        } else {
            lgksAlert("Error finding Reference Code");
        }
    },"raw");
}

function blockPolicy() {
    if($("#componentTree .list-group-item[data-refid].active").length<=0) {
        $(".toolbtnOnEditor").addClass("hidden");
        editor.setReadOnly(true);
        lgksToast("Error finding selected policy");
        return;
    }
    
    if($("#componentTree .list-group-item[data-refid].active").hasClass("blocked")) {
        currentRefid = $("#componentTree .list-group-item[data-refid].active").data("refid");
        processAJAXPostQuery(_service("securityPolicies","activatePolicy"), "refid="+currentRefid, function(data) {
                    lgksToast(data.Data.msg);
                    if(data.Data.status=="error") {
                        
                    } else {
                        listPolicies();
                    }
                },"json");
    } else {
        currentRefid = $("#componentTree .list-group-item[data-refid].active").data("refid");
        processAJAXPostQuery(_service("securityPolicies","deactivatePolicy"), "refid="+currentRefid, function(data) {
                    lgksToast(data.Data.msg);
                    if(data.Data.status=="error") {
                        
                    } else {
                        listPolicies();
                    }
                },"json");
    }
}

function closeEditor() {
    $("#editorScript").hide();
    $("#noEditor").show();
    editor.setReadOnly(true);
    lgksToast("Error finding policy for selection");
    $(".toolbtnOnEditor").addClass("hidden");
}
</script>