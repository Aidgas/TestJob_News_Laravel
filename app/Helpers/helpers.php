<?php

function makePasswordMd5($password) {
    return md5('@#!@EDSFVTR' . $password. '457^*(!@');
}

function generateApiKey($length=80, $list="0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ") {
    mt_srand((double)microtime()*1000000);
    $newstring="";

    if($length > 0){
        while(strlen($newstring) < $length){
            $newstring .= $list[ mt_rand(0, strlen( $list) - 1 ) ];
        }
    }
    return $newstring; 
}

function g_get_new_scale_img_size($w, $h, $max_w, $max_h)
{
   if($w > $max_w)
   {
      $h = (int)$max_w*$h/$w;
      $w = $max_w;         
   }
   
   if($h > $max_h)
   {
      $w = (int)$max_h*$w/$h;
      $h = $max_h;         
   }
   return array($w, $h);
}