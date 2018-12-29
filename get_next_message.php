<?php 
 $agent = $_GET["agent"];
 $list = glob('agent/'.$agent.'-*.chat');
 if(count($list) <=0 )
        echo "None";
 echo file_get_contents($list[0]);
?>
