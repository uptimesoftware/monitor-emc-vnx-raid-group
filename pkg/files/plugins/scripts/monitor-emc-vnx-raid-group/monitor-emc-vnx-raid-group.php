<?php

$TIMESTAMP=date("Y-m-d H:i:s");
$OPTIONS = getopt("m:");
$ARRAY=array();
$ARRAYPROPERTIES=array();
$POOLS=array();
$RGS=array();

if ($OPTIONS[m] == "test") {
    $TESTSET = PrimaryArray_VNX5300;
    $PFILENAME="$TESTSET.storagepool.xml";
    $RFILENAME="$TESTSET.rg.xml";
    $ROUTPUT="vnxmonitor.$TESTSET.rg.OUT.xml";
    $XMLOUT="vnxmonitor.$TESTSET.OUT.xml";
    $FULLRCOMMAND="type $RFILENAME > $ROUTPUT";
    }
else {
    $STORAGE_PROC_HOSTNAME=getenv('UPTIME_STORAGE_PROC_HOSTNAME');
    $USERNAME=getenv('UPTIME_USERNAME');
    $PASSWORD=getenv('UPTIME_PASSWORD');
	$NAVIPATH=getenv('UPTIME_NAVIPATH');

    $NAVIPATH = make_sure_path_has_double_quotes($NAVIPATH);
    
    $ROUTPUT="vnxmonitor.$STORAGE_PROC_HOSTNAME.rg.OUT.xml";
    $RTESTOUT="vnxmonitor.$STORAGE_PROC_HOSTNAME.rg.TESTOUT.xml";
    $XMLOUT="vnxmonitor.$STORAGE_PROC_HOSTNAME.OUT.xml";
    $RCOMMAND="$NAVIPATH -User $USERNAME -Password $PASSWORD -Scope 0 -h $STORAGE_PROC_HOSTNAME -XML getrg";
    $FULLRCOMMAND="$RCOMMAND > $ROUTPUT";
    }  

if (file_exists($ROUTPUT)) {
    shell_exec("del $ROUTPUT");}

    
    shell_exec($FULLRCOMMAND);
    if (empty($ROUTPUT)) {
        print "Error obtaining XML file. /n";}
    
    $RXML = simplexml_load_file($ROUTPUT);
    $RPARAMS = $RXML->MESSAGE->SIMPLERSP->METHODRESPONSE->PARAMVALUE->VALUE[0];
    $RG=array();
    foreach($RPARAMS as $NODE) { 
        $ATTRIBUTE=$NODE->attributes()->NAME;
        $VALUE=$NODE->VALUE;
        if ($ATTRIBUTE=="RaidGroup ID") {
            $RGID=(float)$VALUE;
            $RG["RaidGroup_ID"] = (string)$RGID;
            $TRGS=$TRGS+1;
        } elseif ($ATTRIBUTE=="RaidGroup Type") {
            $RGTYPE=(string)$VALUE;
            $RG["RaidGroup_Type"] = (string)$RGTYPE;
            if ($RGTYPE=="hot_spare") {
                $BADRG=TRUE;
            } elseif ($RGTYPE!=="hot_spare") {
                $BADRG=FALSE;
            }
        } elseif ($ATTRIBUTE=="Raw Capacity (Blocks)") {
            $RRAWCAP=(float)$VALUE;
            $RG["Raw_Capacity_Blocks"] = (string)$RRAWCAP;
            $RRAWCAP_G=blocks2GBs($RRAWCAP);
            $RG["Raw_Capacity_GBs"] = (string)$RRAWCAP_G;
        } elseif ($ATTRIBUTE=="Logical Capacity (Blocks)") {
            $RLOGCAP=(float)$VALUE;
            $RG["Logical_Capacity_Blocks"] = (string)$RLOGCAP;
            $RLOGCAP_G=blocks2GBs($RLOGCAP);
            $RG["Logical_Capacity_GBs"] = (string)$RLOGCAP_G;
        } elseif ($ATTRIBUTE=="Free Capacity (Blocks,non-contiguous)") {
            $RFREECAP=(float)$VALUE;
            $RG["Free_Capacity_Blocks_non-contiguous"] = (string)$RFREECAP;
            $RFREECAP_G=blocks2GBs($RFREECAP);
            $RG["Free_Capacity_GBs"] = (string)$RFREECAP_G;
        } elseif ($ATTRIBUTE=="Legal RAID types") {
            $RGS["Raid_Group_$RGID"] = $RG;
            unset($RG);
            $CURRG=$CURRG+1;
            if ($CURRG = ($LASTRG + 1) AND ($BADRG != TRUE)) {
                $RTRAWCAP=$RTRAWCAP+$RRAWCAP;
                $RTRAWCAP_G=$RTRAWCAP_G+$RRAWCAP_G;
                $RTLOGCAP=$RTLOGCAP+$RLOGCAP;
                $RTLOGCAP_G=$RTLOGCAP_G+$RLOGCAP_G;
                $RTFREECAP=$RTFREECAP+$RFREECAP;
                $RTFREECAP_G=$RTFREECAP_G+$RFREECAP_G;
                $LASTRG=$LASTRG+1;
            }
        }
    }
    
    $RGS["Total_Raw_Capacity_Blocks"] = (string)$RTRAWCAP;
    $RGS["Total_Raw_Capacity_GBs"] = (string)$RTRAWCAP_G;
    $RGS["Total_Logical_Capacity_Blocks"] = (string)$RTLOGCAP;
    $RGS["Total_Logical_Capacity_GBs"] = (string)$RTLOGCAP_G;
    $RGS["Total_Free_Capacity_Blocks"] = (string)$RTFREECAP;
    $RGS["Total_Free_Capacity_GBs"] = (string)$RTFREECAP_G;
    
    $ARRAY["Raid_Groups"] = $RGS;
      
          

    //Function to convert block sizes into GB
    function blocks2GBs($blocks) {
        $gb = ($blocks * 512) / 1024 / 1024 / 1024 ;
        return round(($gb),2);
    }

    //help ensure the navipath has double quotes
    function make_sure_path_has_double_quotes($path)
    {
        if (preg_match('/^(["\']).*\1$/m', $path))
        {
            return $path;
        }
        else
        {
            return '"' . $path . '"';
        }
    }

    
    // Output all variable for up.time
    foreach($RGS as $cur_rg) {
        $rg_id = $cur_rg['RaidGroup_ID'];
        foreach ($cur_rg as $k => $v) {
            if ($k != "RaidGroup_ID") {
                echo $rg_id . "." . $k . " " . $v . "\n";
            }
        }

    }

?>