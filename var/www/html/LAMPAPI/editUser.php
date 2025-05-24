<?php

    $conn = new mysqli("localhost", "Swimmer", "Swim1", "shark-paradise");
	if ($conn->connect_error) 
	{
	    returnWithError( $conn->connect_error );
    } 

?>
