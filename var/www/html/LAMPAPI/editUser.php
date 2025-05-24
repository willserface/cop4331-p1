<?php

    $conn = new mysqli("localhost", "Swimmer", "Swiml", "shark-paradise");
	if ($conn->connect_error) 
	{
	    returnWithError( $conn->connect_error );
    } 

?>
