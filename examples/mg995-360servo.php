<?php

require_once '../PCA9685.php';

$i2c_bus = 1;
$i2c_address = 0x40;
$servo = new PCA9685($i2c_bus, $i2c_address);

// 60Hz
$servo->setFrequency(60);

// Servo connected to port 0
$channel = 0;


// 235ms = 90 degree
// 470ms = 180 degree
$delay = 470;


// Clockwise
$step = 200;

// Counterclockwise
#$step = 400;

// send two commands to the servo:
// 1 - start between 0 and $step pluses
// delay $delay ms.
// 2 - stop
$servo->setPWMPair($channel, 0, $step, $step, $step + 100, $delay / 1000);
