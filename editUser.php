<?php

    $conn = new mysqli("localhost", "root", "COP4331COP", "shark-paradise");
	if ($conn->connect_error) 
	{
	    returnWithError( $conn->connect_error );
    } 

?>