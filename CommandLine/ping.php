<?php

//No point starting just yet
sleep(28800);

$i = 0;
while ($i < 540)
{
	exec("ping -n 1 -w 2500 172.20.1.31", $output);
	echo date("r", time()), ", ", $output[2], "\r\n";
	sleep(60);
	$i++;
}

?>
